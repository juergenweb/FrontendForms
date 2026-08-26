<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;
use RuntimeException;

class FileHelper extends BaseHelper
{
    private const MULTI_EXTENSIONS = [
        'tar.gz',
        'tar.bz2',
        'tar.xz',
    ];

    public const ZIP_MIME_TYPES = [
        'application/zip',
        'application/x-zip-compressed',
    ];

    public MimeHelper $mimeHelper;
    public ImageHelper $imageHelper;
    public ZipHelper $zipHelper;

    protected array $storedFiles = [];

    /**
     * Creates a new FileHelper instance with its required collaborators.
     */
    public function __construct(
        ?MimeHelper $mimeHelper = null,
        ?ImageHelper $imageHelper = null,
        ?ZipHelper $zipHelper = null
    ) {
        $this->mimeHelper = $mimeHelper ?? new MimeHelper();
        $this->imageHelper = $imageHelper ?? new ImageHelper();
        $this->zipHelper = $zipHelper ?? new ZipHelper();
    }

    /**
     * Return a normalized file extension.
     */
    public function getExtension(string $filename): ?string
    {
        $basename = strtolower(basename(trim($filename)));

        if ($basename === '') {
            return null;
        }

        if ($basename[0] === '.' && substr_count($basename, '.') === 1) {
            return null;
        }

        foreach (self::MULTI_EXTENSIONS as $ext) {
            if (str_ends_with($basename, '.' . $ext)) {
                return $ext;
            }
        }

        $extension = pathinfo($basename, PATHINFO_EXTENSION);

        if (
            !is_string($extension)
            || $extension === ''
            || !ctype_alnum($extension)
        ) {
            return null;
        }

        return $extension;
    }

    /**
     * Return the validated file size from an uploaded file array.
     */
    public function getFileSize(array $file): int
    {
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
            return 0;
        }

        if (empty($file['name']) || !is_string($file['name'])) {
            return 0;
        }

        if (!isset($file['size']) || !is_numeric($file['size'])) {
            return 0;
        }

        return max((int) $file['size'], 0);
    }

    /**
     * Return the total size of multiple uploaded files.
     */
    public function getTotalUploadFileSize(array $files): int
    {
        $total = 0;

        foreach ($files as $file) {
            $total += $this->getFileSize($file);
        }

        return $total;
    }

    /**
     * Normalize a file size value into bytes.
     */
    public function normalizeFileSize(mixed $value): ?int
    {
        if (is_string($value) && $this->allValuesContainLetters($value)) {
            $value = self::sizeToBytes(trim($value));
        }

        if (!is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int >= 0 ? $int : null;
    }

    /**
     * Convert a human-readable size string into bytes.
     *
     * Accepts null and returns 0 (no limit / missing param).
     *
     * Examples: 10M, 512K, 1.5G
     */
    public static function sizeToBytes(?string $size): int
    {
        if ($size === null || trim($size) === '') {
            return 0;
        }

        $size = trim($size);

        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([kmgtp]?b?)?$/i', $size, $m)) {
            throw new InvalidArgumentException("Invalid size specification: $size");
        }

        $value = (float) $m[1];
        $unit = rtrim(strtolower($m[2] ?? ''), 'b');

        static $multipliers = [
            '' => 1,
            'k' => 1_024,
            'm' => 1_048_576,
            'g' => 1_073_741_824,
            't' => 1_099_511_627_776,
            'p' => 1_125_899_906_842_624,
        ];

        if (!isset($multipliers[$unit])) {
            throw new InvalidArgumentException("Unknown unit: $unit");
        }

        return (int) round($value * $multipliers[$unit]);
    }

    /**
     * Analyze all uploaded files and return metadata.
     */
    public function getUploadedFilesData(array $files): array
    {
        $result = [];

        $globalContext = $this->createEmptyContext();
        $fieldStats = [];

        foreach ($files as $fieldName => $field) {
            $result[$fieldName] = [];

            $fieldContext = $this->createEmptyContext();

            foreach ($this->normalizeFiles($field) as $file) {
                if (($file['error'] ?? null) === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $this->updateRootStats($globalContext, $file);
                $this->updateRootStats($fieldContext, $file);

                $ctx = $this->createEmptyContext();

                try {
                    $data = $this->analyzeFile(
                        path: $file['tmp_name'],
                        originalName: $file['name'],
                        size: (int) $file['size'],
                        error: $file['error'],
                        depth: 0,
                        ctx: $ctx
                    );
                } catch (RuntimeException $e) {
                    // analyzeFile() (via ZipHelper::analyzeZip()) throws
                    // when a ZIP archive takes too long to process (a
                    // safeguard against ZIP-bomb-like uploads). $ctx is
                    // passed by reference, so it already reflects whatever
                    // partial progress was made before the timeout - but
                    // that partial state alone isn't a reliable signal
                    // that limits were exceeded. Since a ZIP that times
                    // out is at least as suspicious as one that's
                    // confirmed to exceed a structural limit, fail closed
                    // here: force max_depth/zip_count past any reasonable
                    // configured maximum, so validation rules relying on
                    // these stats (e.g. notExceedMaxDepthOfZipFolders())
                    // correctly reject the upload instead of the
                    // exception propagating uncaught through them.
                    $ctx['max_depth'] = max($ctx['max_depth'] ?? 0, ZipHelper::MAX_DEPTH + 1);
                    $ctx['zip_count_all'] = ($ctx['zip_count_all'] ?? 0) + 1;
                    $data = [
                        'name' => $file['name'],
                        'error' => 'zip_processing_timeout',
                    ];
                }

                $this->mergeContext($globalContext, $ctx);
                $this->mergeContext($fieldContext, $ctx);

                $this->updateZipAggregateStats($globalContext, $data);
                $this->updateZipAggregateStats($fieldContext, $data);

                $result[$fieldName][] = $data;
            }

            $fieldStats[$fieldName] = $this->finalizeContext($fieldContext);
        }

        $result = [
            'files' => $result,
            'stats' => [
                'all' => $this->finalizeContext($globalContext),
                'fields' => $fieldStats,
            ],
        ];

        return $result;
    }

    /**
     * Normalize a raw $_FILES field entry (single or multi-file PHP upload
     * structure) into a flat array of per-file associative arrays.
     */
    private function normalizeFiles(array $field): array
    {
        if (!is_array($field['name'])) {
            return [[
                'name' => $field['name'],
                'tmp_name' => $field['tmp_name'],
                'size' => $field['size'],
                'error' => $field['error'],
            ]];
        }

        $keys = ['name', 'tmp_name', 'size', 'error'];
        $count = count($field['name']);
        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $entry = array_combine($keys, [
                $field['name'][$i],
                $field['tmp_name'][$i],
                $field['size'][$i],
                $field['error'][$i],
            ]);

            if ($entry !== false) {
                $out[] = $entry;
            }
        }

        return $out;
    }

    /**
     * Analyze a single file and extract metadata.
     *
     * Key naming:
     *   - 'size'          → compressed/actual file size (all files, all depths)
     *   - 'size_unzipped' → total uncompressed size of ZIP contents (root ZIPs only, depth=0)
     */
    protected function analyzeFile(
        string $path,
        string $originalName,
        int $size,
        int $error,
        int $depth,
        array &$ctx,
        ?string $content = null
    ): array {
        $ctx['upload_errors_all'] ??= [];

        if ($error !== UPLOAD_ERR_OK) {
            $ctx['upload_errors_all'][$error] =
                ($ctx['upload_errors_all'][$error] ?? 0) + 1;

            return [
                'name' => $originalName,
                'error' => $error,
            ];
        }

        if (!is_string($path) || $path === '') {
            return [];
        }

        $ctx['smallest_file_size_all'] ??= PHP_INT_MAX;
        $ctx['smallest_non_zip_file_size'] ??= PHP_INT_MAX;
        $ctx['smallest_zip_file_size'] ??= PHP_INT_MAX;
        $ctx['largest_file_size'] ??= 0;
        $ctx['largest_non_zip_file_size'] ??= 0;
        $ctx['largest_zip_file_size'] ??= 0;
        $ctx['max_depth'] = max($ctx['max_depth'] ?? 0, $depth);

        if ($size === 0) {
            $ctx['empty_file_count_all'] =
                ($ctx['empty_file_count_all'] ?? 0) + 1;
        }

        $mimeType = $content !== null
            ? $this->mimeHelper->getMimeType(content: $content)
            : $this->mimeHelper->getMimeType($path);

        $extension = $this->getExtension($originalName);

        if ($extension !== null) {
            $ctx['extensions_all'][$extension] =
                ($ctx['extensions_all'][$extension] ?? 0) + 1;
        } else {
            $ctx['no_extension_all'] =
                ($ctx['no_extension_all'] ?? 0) + 1;
        }

        $magicBytes = $this->mimeHelper->getMagicBytes($path, $content);

        $mimeType = $this->mimeHelper->normalizeMimeType(
            $mimeType,
            $path,
            $content,
            null,
            $magicBytes
        );

        $isZipMime = $mimeType !== null
            && in_array($mimeType, self::ZIP_MIME_TYPES, true);

        if (!empty($mimeType)) {
            $ctx['mime_types_all'][$mimeType] =
                ($ctx['mime_types_all'][$mimeType] ?? 0) + 1;
        } else {
            $ctx['no_mime_type_all'] =
                ($ctx['no_mime_type_all'] ?? 0) + 1;
        }

        if (!$isZipMime) {
            $ctx['files_count_all'] =
                ($ctx['files_count_all'] ?? 0) + 1;
        }

        if ($depth > 0) {
            $ctx['largest_file_size'] = max(
                $ctx['largest_file_size'],
                $size
            );

            $ctx['smallest_file_size_all'] = min(
                $ctx['smallest_file_size_all'],
                $size
            );

            if ($isZipMime) {
                $ctx['largest_zip_file_size'] = max(
                    $ctx['largest_zip_file_size'],
                    $size
                );

                $ctx['smallest_zip_file_size'] = min(
                    $ctx['smallest_zip_file_size'],
                    $size
                );
            } else {
                $ctx['largest_non_zip_file_size'] = max(
                    $ctx['largest_non_zip_file_size'],
                    $size
                );

                $ctx['smallest_non_zip_file_size'] = min(
                    $ctx['smallest_non_zip_file_size'],
                    $size
                );
            }
        }

        $isImage = $this->imageHelper->detectImage(
            $size,
            $mimeType,
            $content,
            $path
        );

        if ($isImage) {
            $ctx['image_count_all'] =
                ($ctx['image_count_all'] ?? 0) + 1;
        }

        $data = [
            'name' => $originalName,
            'tmp_name' => $content !== null ? null : $path,
            'error' => $error,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'size' => $size,
            'is_image' => $isImage,
            'magic_bytes' => $magicBytes,
        ];

        if ($isImage) {
            $imageData = $this->imageHelper->analyzeImage(
                $path,
                $mimeType,
                $size,
                $isImage
            );

            if ($imageData !== null) {
                foreach ($imageData as $k => $v) {
                    $data[$k] = $v;
                }

                $width = $imageData['width'] ?? null;
                $height = $imageData['height'] ?? null;

                if ($size > ($ctx['largest_image_all']['size'] ?? 0)) {
                    $ctx['largest_image_all'] = [
                        'size' => $size,
                        'path' => $path,
                        'mime_type' => $mimeType,
                        'width' => $width,
                        'height' => $height,
                    ];
                }

                if (!empty($width) && !empty($height)) {
                    $gcd = $this->gcd($width, $height);

                    if ($gcd > 0) {
                        $data['aspect_ratio'] =
                            ($width / $gcd) . ':' . ($height / $gcd);

                        $ctx['aspect_ratios_all'][$data['aspect_ratio']] =
                            ($ctx['aspect_ratios_all'][$data['aspect_ratio']] ?? 0) + 1;
                    } else {
                        $data['aspect_ratio'] = null;
                    }
                } else {
                    $data['aspect_ratio'] = null;
                }
            }
        }

        if (
            $mimeType !== null
            && in_array($mimeType, self::ZIP_MIME_TYPES, true)
            && $depth < ZipHelper::MAX_DEPTH
        ) {
            $ctx['zip_count_all']++;

            $files = $this->zipHelper->analyzeZip(
                zipSource: $content ?? $path,
                error: $error,
                depth: $depth + 1,
                ctx: $ctx,
                analyzeFileCallback: fn (
                    string $path,
                    string $originalName,
                    int $size,
                    int $error,
                    int $depth,
                    array &$ctx,
                    ?string $content = null
                ) => $this->analyzeFile(
                    $path,
                    $originalName,
                    $size,
                    $error,
                    $depth,
                    $ctx,
                    $content
                ),
                isRawContent: $content !== null
            );

            $data['all_extracted_file_count'] = $this->countLeafFiles($files);
            $data['depth'] = $this->calculateDepth($files);
            $data['files_count_zip_none_extracted'] = count($files);
            $data['files_count_zip_extracted'] = $data['all_extracted_file_count'];
            $data['extensions'] = array_values(array_unique($this->collectExtensions($files)));

            $mimeTypes = [];
            $this->collectMimeTypes($files, $mimeTypes);
            $data['mime_types'] = array_keys($mimeTypes);

            if ($depth === 0) {
                $data['failed_extraction_count'] = $ctx['failed_extraction_count_all'] ?? 0;
                $data['size_unzipped'] = $this->calculateTotalSize($files);

                $data['compression_ratio'] = $size > 0
                    ? round($data['size_unzipped'] / $size, 3)
                    : 0.0;

                $data['largest_file_size'] = $ctx['largest_file_size'];

                $data['smallest_file_size_all'] =
                    $ctx['smallest_file_size_all'] === PHP_INT_MAX ? 0 : $ctx['smallest_file_size_all'];

                $data['largest_non_zip_file_size'] = $ctx['largest_non_zip_file_size'];

                $data['smallest_non_zip_file_size'] =
                    $ctx['smallest_non_zip_file_size'] === PHP_INT_MAX ? 0 : $ctx['smallest_non_zip_file_size'];

                $data['largest_zip_file_size'] = $ctx['largest_zip_file_size'];

                $data['smallest_zip_file_size'] =
                    $ctx['smallest_zip_file_size'] === PHP_INT_MAX ? 0 : $ctx['smallest_zip_file_size'];

                $topLevelSizes = array_map(
                    static fn (array $f): int => $f['size'] ?? 0,
                    $files
                );

                $data['largest_file_in_zip'] = $topLevelSizes === [] ? 0 : max($topLevelSizes);
                $data['smallest_file_in_zip'] = $topLevelSizes === [] ? 0 : min($topLevelSizes);

                $leafSizes = $this->collectLeafFileSizes($files);

                $data['largest_file_in_zip_extracted'] = $leafSizes === [] ? 0 : max($leafSizes);
                $data['smallest_file_in_zip_extracted'] = $leafSizes === [] ? 0 : min($leafSizes);

                $data['file_extentsions_in_zip'] = array_values($this->collectTopLevelExtensions($files));
                $data['file_extentsions_in_zip_extracted'] = $data['extensions'];

                $data['total_zip_count'] = max(0, $ctx['zip_count_all'] - 1);
                $data['total_image_count'] = $ctx['image_count_all'];
                $data['largest_image_size'] = $ctx['largest_image_all']['size'] ?? 0;
                $data['total_empty_file_count'] = $ctx['empty_file_count_all'];
                $data['aspect_ratios'] = $ctx['aspect_ratios_all'];
            }

            $data['files'] = $files;
        }

        return $data;
    }

    /**
     * Recursively sum the sizes of all leaf files in a (possibly nested) ZIP tree.
     */
    private function calculateTotalSize(array $files): int
    {
        $total = 0;

        foreach ($files as $file) {
            $total += $file['size'] ?? 0;
        }

        return $total;
    }

    /**
     * Recursively determine the maximum nesting depth of a ZIP file tree.
     */
    private function calculateDepth(array $files): int
    {
        $max = 1;

        foreach ($files as $file) {
            if (!empty($file['files'])) {
                $max = max($max, 1 + $this->calculateDepth($file['files']));
            }
        }

        return $max;
    }

    /**
     * Recursively count all leaf (non-directory, non-nested-zip) files in a ZIP tree.
     */
    private function countLeafFiles(array $files): int
    {
        $count = 0;

        foreach ($files as $file) {
            if (!empty($file['files'])) {
                $count += $this->countLeafFiles($file['files']);
                continue;
            }

            $count++;
        }

        return $count;
    }

    /**
     * Recursively collect the sizes of all leaf files in a ZIP tree.
     *
     * @return int[]
     */
    private function collectLeafFileSizes(array $files): array
    {
        $sizes = [];

        foreach ($files as $file) {
            if (!empty($file['files'])) {
                foreach ($this->collectLeafFileSizes($file['files']) as $size) {
                    $sizes[] = $size;
                }
                continue;
            }

            $sizes[] = $file['size'] ?? 0;
        }

        return $sizes;
    }

    /**
     * Collect lowercase file extensions from the top level of a ZIP tree only.
     *
     * @return string[]
     */
    private function collectTopLevelExtensions(array $files): array
    {
        $extensions = [];

        foreach ($files as $file) {
            if (!empty($file['extension'])) {
                $extensions[] = strtolower($file['extension']);
            }
        }

        return $extensions;
    }

    /**
     * Recursively collect lowercase file extensions from an entire ZIP tree.
     *
     * @return string[]
     */
    private function collectExtensions(array $files): array
    {
        $extensions = [];

        foreach ($files as $file) {
            if (!empty($file['extension'])) {
                $extensions[] = strtolower($file['extension']);
            }

            if (!empty($file['files'])) {
                foreach ($this->collectExtensions($file['files']) as $ext) {
                    $extensions[] = $ext;
                }
            }
        }

        return $extensions;
    }

    /**
     * Recursively collect unique MIME types from a ZIP tree into $mimeTypes.
     */
    private function collectMimeTypes(array $files, array &$mimeTypes = []): void
    {
        foreach ($files as $file) {
            if (!empty($file['mime_type'])) {
                $mimeTypes[$file['mime_type']] = true;
            }

            if (!empty($file['files'])) {
                $this->collectMimeTypes($file['files'], $mimeTypes);
            }
        }
    }

    /**
     * Check whether an uploaded file entry has no PHP upload error
     * (including the case where no file was submitted at all).
     */
    public function isValidUpload(array $file): bool
    {
        return
            ($file['error'] ?? null) === UPLOAD_ERR_OK ||
            ($file['error'] ?? null) === UPLOAD_ERR_NO_FILE;
    }

    /**
     * Build an empty statistics-accumulator array used as the starting
     * point for both global and per-field upload statistics.
     */
    private function createEmptyContext(): array
    {
        return [
            'files_count_root' => 0,
            'files_count_all' => 0,
            'image_count_root' => 0,
            'image_count_all' => 0,
            'zip_count_root' => 0,
            'zip_count_all' => 0,
            'no_extension_root' => 0,
            'no_extension_all' => 0,
            'no_mime_type_root' => 0,
            'no_mime_type_all' => 0,
            'total_size_root' => 0,
            'zip_size_root' => 0,
            'non_zip_size_root' => 0,
            'root_files_without_extraction' => 0,
            'empty_file_count_all' => 0,
            'empty_file_count_root' => 0,
            'max_depth' => 0,
            'largest_filesize_root' => 0,
            'smallest_filesize_root' => PHP_INT_MAX,
            'largest_file_size' => 0,
            'smallest_file_size_all' => PHP_INT_MAX,
            'largest_non_zip_file_size' => 0,
            'smallest_non_zip_file_size' => PHP_INT_MAX,
            'largest_zip_file_size' => 0,
            'smallest_zip_file_size' => PHP_INT_MAX,
            'aspect_ratios_all' => [],
            'aspect_ratios_root' => [],
            'largest_image_all' => [
                'size' => 0,
                'path' => null,
                'mime_type' => null,
                'width' => null,
                'height' => null,
            ],
            'extensions_root' => [],
            'extensions_all' => [],
            'mime_types_root' => [],
            'mime_types_all' => [],
            'upload_errors_root' => [],
            'upload_errors_all' => [],
            'max_files_count_zip_none_extracted' => 0,
            'max_files_count_zip_extracted' => 0,
            'min_files_count_zip_none_extracted' => PHP_INT_MAX,
            'min_files_count_zip_extracted' => PHP_INT_MAX,
            'largest_zip_file_size_root' => 0,
            'smallest_zip_file_size_root' => PHP_INT_MAX,
            'largest_zip_file_size_root_uncompressed' => 0,
            'smallest_zip_file_size_root_uncompressed' => PHP_INT_MAX,
            'extensions_zip_all' => [],
            'extensions_zip_all_extracted' => [],
            'largest_file_size_in_zip' => 0,
            'largest_file_size_in_zip_extracted' => 0,
        ];
    }

    /**
     * Merge a per-file analysis context ($ctx) into an accumulating
     * global or per-field statistics context ($global), in place.
     */
    private function mergeContext(array &$global, array $ctx): void
    {
        $global['files_count_all'] += ($ctx['files_count_all'] ?? 0);
        $global['zip_count_all'] += ($ctx['zip_count_all'] ?? 0);
        $global['files_count_root'] += ($ctx['files_count_root'] ?? 0);
        $global['image_count_all'] += ($ctx['image_count_all'] ?? 0);
        $global['image_count_root'] += ($ctx['image_count_root'] ?? 0);
        $global['empty_file_count_all'] += ($ctx['empty_file_count_all'] ?? 0);
        $global['empty_file_count_root'] += ($ctx['empty_file_count_root'] ?? 0);
        $global['root_files_without_extraction'] += ($ctx['root_files_without_extraction'] ?? 0);
        $global['zip_count_root'] += ($ctx['zip_count_root'] ?? 0);
        $global['max_depth'] = max($global['max_depth'], $ctx['max_depth'] ?? 0);
        $global['no_extension_all'] += ($ctx['no_extension_all'] ?? 0);
        $global['no_extension_root'] += ($ctx['no_extension_root'] ?? 0);
        $global['no_mime_type_all'] += ($ctx['no_mime_type_all'] ?? 0);
        $global['no_mime_type_root'] += ($ctx['no_mime_type_root'] ?? 0);

        $global['largest_file_size'] = max(
            $global['largest_file_size'],
            $ctx['largest_file_size'] ?? 0
        );

        $global['largest_non_zip_file_size'] = max(
            $global['largest_non_zip_file_size'],
            $ctx['largest_non_zip_file_size'] ?? 0
        );

        $global['largest_zip_file_size'] = max(
            $global['largest_zip_file_size'],
            $ctx['largest_zip_file_size'] ?? 0
        );

        $global['smallest_file_size_all'] = min(
            $this->minSafe($global['smallest_file_size_all']),
            $this->minSafe($ctx['smallest_file_size_all'] ?? PHP_INT_MAX)
        );

        $global['smallest_non_zip_file_size'] = min(
            $this->minSafe($global['smallest_non_zip_file_size']),
            $this->minSafe($ctx['smallest_non_zip_file_size'] ?? PHP_INT_MAX)
        );

        $global['smallest_zip_file_size'] = min(
            $this->minSafe($global['smallest_zip_file_size']),
            $this->minSafe($ctx['smallest_zip_file_size'] ?? PHP_INT_MAX)
        );

        foreach (($ctx['aspect_ratios_all'] ?? []) as $ratio => $count) {
            $global['aspect_ratios_all'][$ratio] =
                ($global['aspect_ratios_all'][$ratio] ?? 0) + $count;
        }

        foreach (($ctx['aspect_ratios_root'] ?? []) as $ratio => $count) {
            $global['aspect_ratios_root'][$ratio] =
                ($global['aspect_ratios_root'][$ratio] ?? 0) + $count;
        }

        foreach (($ctx['extensions_all'] ?? []) as $ext => $count) {
            $global['extensions_all'][$ext] =
                ($global['extensions_all'][$ext] ?? 0) + $count;
        }

        foreach (($ctx['extensions_root'] ?? []) as $ext => $count) {
            $global['extensions_root'][$ext] =
                ($global['extensions_root'][$ext] ?? 0) + $count;
        }

        foreach (($ctx['mime_types_all'] ?? []) as $mime => $count) {
            $global['mime_types_all'][$mime] =
                ($global['mime_types_all'][$mime] ?? 0) + $count;
        }

        foreach (($ctx['mime_types_root'] ?? []) as $mime => $count) {
            $global['mime_types_root'][$mime] =
                ($global['mime_types_root'][$mime] ?? 0) + $count;
        }

        if (($ctx['largest_image_all']['size'] ?? 0) > ($global['largest_image_all']['size'] ?? 0)) {
            $global['largest_image_all'] = $ctx['largest_image_all'];
        }
    }

    /**
     * Roll ZIP-specific aggregate metrics (extracted file counts, sizes,
     * extensions) from an analyzed root ZIP entry into the given context.
     */
    private function updateZipAggregateStats(array &$context, array $data): void
    {
        if (isset($data['files_count_zip_none_extracted'])) {
            $context['max_files_count_zip_none_extracted'] = max(
                $context['max_files_count_zip_none_extracted'] ?? 0,
                $data['files_count_zip_none_extracted']
            );

            $context['min_files_count_zip_none_extracted'] = min(
                $context['min_files_count_zip_none_extracted'] ?? PHP_INT_MAX,
                $data['files_count_zip_none_extracted']
            );
        }

        if (isset($data['files_count_zip_extracted'])) {
            $context['max_files_count_zip_extracted'] = max(
                $context['max_files_count_zip_extracted'] ?? 0,
                $data['files_count_zip_extracted']
            );

            $context['min_files_count_zip_extracted'] = min(
                $context['min_files_count_zip_extracted'] ?? PHP_INT_MAX,
                $data['files_count_zip_extracted']
            );
        }

        if (isset($data['size_unzipped'])) {
            $context['largest_zip_file_size_root_uncompressed'] = max(
                $context['largest_zip_file_size_root_uncompressed'] ?? 0,
                $data['size_unzipped']
            );

            $context['smallest_zip_file_size_root_uncompressed'] = min(
                $context['smallest_zip_file_size_root_uncompressed'] ?? PHP_INT_MAX,
                $data['size_unzipped']
            );
        }

        if (isset($data['file_extentsions_in_zip'])) {
            foreach ($data['file_extentsions_in_zip'] as $ext) {
                $context['extensions_zip_all'][$ext] = true;
            }
        }

        if (isset($data['file_extentsions_in_zip_extracted'])) {
            foreach ($data['file_extentsions_in_zip_extracted'] as $ext) {
                $context['extensions_zip_all_extracted'][$ext] = true;
            }
        }

        if (isset($data['largest_file_in_zip'])) {
            $context['largest_file_size_in_zip'] = max(
                $context['largest_file_size_in_zip'] ?? 0,
                $data['largest_file_in_zip']
            );
        }

        if (isset($data['largest_file_in_zip_extracted'])) {
            $context['largest_file_size_in_zip_extracted'] = max(
                $context['largest_file_size_in_zip_extracted'] ?? 0,
                $data['largest_file_in_zip_extracted']
            );
        }
    }

    /**
     * Return $value unchanged unless it is still the PHP_INT_MAX sentinel
     * used to seed "minimum" accumulators, in which case it is passed through as-is.
     */
    private function minSafe(int $value): int
    {
        return $value === PHP_INT_MAX ? PHP_INT_MAX : $value;
    }

    /**
     * Convert an internal accumulator context into the final, public-facing
     * statistics array (filling in defaults for any unset PHP_INT_MAX sentinels).
     */
    private function finalizeContext(array $ctx): array
    {
        return [
            'files_count_root' => $ctx['files_count_root'] ?? 0,
            'files_count_all' => $ctx['files_count_all'] ?? 0,
            'image_count_root' => $ctx['image_count_root'] ?? 0,
            'image_count_all' => $ctx['image_count_all'] ?? 0,
            'zip_count_root' => $ctx['zip_count_root'] ?? 0,
            'zip_count_all' => $ctx['zip_count_all'] ?? 0,
            'no_extension_root' => $ctx['no_extension_root'] ?? 0,
            'no_extension_all' => $ctx['no_extension_all'] ?? 0,
            'no_mime_type_root' => $ctx['no_mime_type_root'] ?? 0,
            'no_mime_type_all' => $ctx['no_mime_type_all'] ?? 0,
            'total_size_root' => $ctx['total_size_root'] ?? 0,
            'zip_size_root' => $ctx['zip_size_root'] ?? 0,
            'non_zip_size_root' => $ctx['non_zip_size_root'] ?? 0,
            'largest_filesize_root' => $ctx['largest_filesize_root'] ?? 0,
            'smallest_filesize_root' =>
                ($ctx['smallest_filesize_root'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['smallest_filesize_root'],
            'smallest_file_size_all' =>
                ($ctx['smallest_file_size_all'] === PHP_INT_MAX) ? 0 : $ctx['smallest_file_size_all'],
            'largest_image_all' => $ctx['largest_image_all'] ?? [],
            'empty_file_count_root' => $ctx['empty_file_count_root'] ?? 0,
            'empty_file_count_all' => $ctx['empty_file_count_all'] ?? 0,
            'max_depth' => $ctx['max_depth'],
            'largest_file_size' => $ctx['largest_file_size'],
            'largest_non_zip_file_size' => $ctx['largest_non_zip_file_size'],
            'smallest_non_zip_file_size' =>
                $ctx['smallest_non_zip_file_size'] === PHP_INT_MAX ? 0 : $ctx['smallest_non_zip_file_size'],
            'largest_zip_file_size' => $ctx['largest_zip_file_size'] ?? 0,
            'smallest_zip_file_size' =>
                ($ctx['smallest_zip_file_size'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['smallest_zip_file_size'],
            'aspect_ratios_root' => $ctx['aspect_ratios_root'] ?? [],
            'aspect_ratios_all' => $ctx['aspect_ratios_all'] ?? [],
            'extensions_root' => $ctx['extensions_root'] ?? [],
            'extensions_all' => $ctx['extensions_all'] ?? [],
            'extensions_zip_all' => array_keys($ctx['extensions_zip_all'] ?? []),
            'extensions_zip_all_extracted' => array_keys($ctx['extensions_zip_all_extracted'] ?? []),
            'mime_types_root' => $ctx['mime_types_root'] ?? [],
            'mime_types_all' => $ctx['mime_types_all'] ?? [],
            'upload_errors_root' => $ctx['upload_errors_root'] ?? [],
            'upload_errors_all' => $ctx['upload_errors_all'] ?? [],
            'max_files_count_zip_none_extracted' => $ctx['max_files_count_zip_none_extracted'] ?? 0,
            'max_files_count_zip_extracted' => $ctx['max_files_count_zip_extracted'] ?? 0,
            'min_files_count_zip_none_extracted' =>
                ($ctx['min_files_count_zip_none_extracted'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['min_files_count_zip_none_extracted'],
            'min_files_count_zip_extracted' =>
                ($ctx['min_files_count_zip_extracted'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['min_files_count_zip_extracted'],
            'largest_zip_file_size_root' => $ctx['largest_zip_file_size_root'] ?? 0,
            'smallest_zip_file_size_root' =>
                ($ctx['smallest_zip_file_size_root'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['smallest_zip_file_size_root'],
            'largest_zip_file_size_root_uncompressed' =>
                $ctx['largest_zip_file_size_root_uncompressed'] ?? 0,
            'smallest_zip_file_size_root_uncompressed' =>
                ($ctx['smallest_zip_file_size_root_uncompressed'] ?? PHP_INT_MAX) === PHP_INT_MAX ? 0 : $ctx['smallest_zip_file_size_root_uncompressed'],
            'largest_file_size_in_zip' => $ctx['largest_file_size_in_zip'] ?? 0,
            'largest_file_size_in_zip_extracted' => $ctx['largest_file_size_in_zip_extracted'] ?? 0,
        ];
    }

    /**
     * Calculate the greatest common divisor of two integers (Euclidean algorithm).
     */
    private function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * Update root-level (depth-0) upload statistics in the given context
     * for a single raw uploaded file.
     */
    private function updateRootStats(array &$context, array $file): void
    {
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
            $error = (int) $file['error'];

            $context['upload_errors_all'][$error] =
                ($context['upload_errors_all'][$error] ?? 0) + 1;

            return;
        }

        $size = (int) $file['size'];

        $context['files_count_root']++;

        if ($size === 0) {
            $context['empty_file_count_root']++;
        }

        $context['largest_filesize_root'] = max(
            $context['largest_filesize_root'],
            $size
        );

        $context['smallest_filesize_root'] = min(
            $context['smallest_filesize_root'],
            $size
        );

        $tmpMime = $this->mimeHelper->getMimeType($file['tmp_name'] ?? '');

        $tmpMime = $this->mimeHelper->normalizeMimeType(
            $tmpMime,
            $file['tmp_name'] ?? '',
            null,
            null,
            $this->mimeHelper->getMagicBytes($file['tmp_name'] ?? '', null)
        );

        $isZip = $tmpMime !== null && in_array($tmpMime, self::ZIP_MIME_TYPES, true);

        $context['total_size_root'] += $size;

        if ($isZip) {
            $context['zip_count_root']++;
            $context['zip_size_root'] += $size;

            $context['largest_zip_file_size_root'] = max(
                $context['largest_zip_file_size_root'],
                $size
            );

            $context['smallest_zip_file_size_root'] = min(
                $context['smallest_zip_file_size_root'],
                $size
            );
        } else {
            $context['non_zip_size_root'] += $size;
        }

        $extension = $this->getExtension($file['name']);

        if ($extension !== null) {
            $context['extensions_root'][$extension] =
                ($context['extensions_root'][$extension] ?? 0) + 1;
        } else {
            $context['no_extension_root']++;
        }

        if (!empty($tmpMime)) {
            $context['mime_types_root'][$tmpMime] =
                ($context['mime_types_root'][$tmpMime] ?? 0) + 1;
        } else {
            $context['no_mime_type_root']++;
        }

        if (!$isZip) {
            $isImage = $this->imageHelper->detectImage(
                $size,
                $tmpMime,
                null,
                $file['tmp_name'] ?? ''
            );

            if ($isImage) {
                $context['image_count_root']++;

                $imageData = $this->imageHelper->analyzeImage(
                    $file['tmp_name'],
                    $tmpMime,
                    $size,
                    $isImage
                );

                $width = $imageData['width'] ?? null;
                $height = $imageData['height'] ?? null;

                if (!empty($width) && !empty($height)) {
                    $gcd = $this->gcd($width, $height);

                    $ratio = ($width / $gcd) . ':' . ($height / $gcd);

                    $context['aspect_ratios_root'][$ratio] =
                        ($context['aspect_ratios_root'][$ratio] ?? 0) + 1;
                }
            }
        }
    }

    /**
     * Check if a file with the same name exists in the destination directory
     * If param is set to true, the filename will be overwritten with a timestamp and the output is true
     * Otherwise output is false
     * @param array $value
     * @param string $uploadPath
     * @param $param
     * @return bool
     * @throws WireException
     */
    /**
     * Check for duplicate filename(s) in the upload path and optionally rename on conflict.
     *
     * @param array  $value      Single file array (['name' => ..., ...]) or array of file arrays
     * @param string $uploadPath Target path, will be normalized to end with a slash
     * @param mixed  $param      Optional array where $param[0] === true triggers overwrite/rename behavior
     * @return bool True if all files are usable (no unresolved duplicates), false otherwise
     */
    public function checkDuplicateFilename(array $value, string $uploadPath, $param): bool
    {
        // normalize upload path to always have a trailing slash
        $uploadPath = rtrim($uploadPath, '/\\') . '/';

        // normalize $param to a safe boolean flag
        $overwrite = is_array($param) && isset($param[0]) && $param[0] === true;

        // detect whether we received multiple files (array of arrays) or a single file
        $isMultiple = isset($value[0]) && is_array($value[0]);
        $files = $isMultiple ? $value : [$value];

        $allOk = true;

        foreach ($files as $file) {
            if (!isset($file['name'])) {
                $allOk = false;
                continue;
            }

            // sanitize + normalize filename (mb-safe lowercasing for umlauts etc.)
            $filename = $this->wire('sanitizer')->filename($file['name']);
            $filename = mb_strtolower($filename, 'UTF-8');

            $basename = pathinfo($filename, PATHINFO_FILENAME);
            $ext = pathinfo($filename, PATHINFO_EXTENSION);

            $targetPath = $uploadPath . $filename;
            $exists = $this->wire('files')->exists($targetPath);

            if ($exists && $overwrite) {
                // generate a unique filename, retry on the (unlikely) chance of a collision
                do {
                    $suffix = bin2hex(random_bytes(4));
                    $newFilename = $ext !== ''
                        ? $basename . '-' . $suffix . '.' . $ext
                        : $basename . '-' . $suffix;
                    $newFileNamePath = $uploadPath . $newFilename;
                } while ($this->wire('files')->exists($newFileNamePath));

                if ($this->wire('files')->rename($targetPath, $newFileNamePath)) {
                    $this->storedFiles[] = $newFileNamePath;
                } else {
                    // rename failed, treat as unresolved duplicate
                    $allOk = false;
                }
            } elseif ($exists) {
                // duplicate exists and overwrite not requested -> do NOT add to storedFiles
                $allOk = false;
            } else {
                // no conflict
                $this->storedFiles[] = $targetPath;
            }
        }

        return $allOk;
    }



}
