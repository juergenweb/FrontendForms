<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;
use ZipArchive;
use InvalidArgumentException;

/**
 * Helper class for secure ZIP archive analysis.
 *
 * Safely extracts and analyzes ZIP archive contents while protecting against
 * ZIP bombs, path traversal attacks, symlink abuse, oversized files,
 * and recursive archive nesting.
 */
class ZipHelper extends BaseHelper
{
    /** Maximum total extracted bytes across all ZIP entries (ZIP bomb protection). */
    private const MAX_TOTAL_BYTES = 100 * 1024 * 1024;

    /** Maximum allowed size per individual ZIP entry. */
    private const MAX_ENTRY_SIZE = 25 * 1024 * 1024;

    /** Maximum recursive ZIP nesting depth. */
    public const MAX_DEPTH = 4;

    /** Maximum allowed compression ratio per entry (ZIP bomb protection). */
    private const MAX_RATIO = 20;

    /** Maximum number of entries allowed in a single ZIP archive. */
    private const MAX_FILES = 1000;

    /** Maximum ZIP processing time in seconds. */
    private const MAX_SECONDS = 2.0;

    /** Stream chunk size used while reading ZIP entries. */
    private const STREAM_CHUNK = 8192;

    /** Maximum allowed size of the input ZIP file before opening it. */
    private const MAX_ZIP_INPUT_SIZE = 50 * 1024 * 1024;

    /**
     * Create a new ZipHelper instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Analyze a ZIP archive and safely extract metadata from all entries.
     *
     * Opens the archive from a file path or raw byte string, iterates over
     * all entries, applies security checks (path traversal, compression ratio,
     * total byte limit, timeout), and invokes $analyzeFileCallback for each
     * valid extracted entry. Results from all callbacks are collected and returned.
     *
     * @param string   $zipSource           File path or raw ZIP bytes (see $isRawContent).
     * @param int      $error               $_FILES upload error code. Processing is skipped
     *                                      unless this equals UPLOAD_ERR_OK (0).
     * @param int      $depth               Recursion depth as received inside analyzeZip().
     *                                      Always called with the caller's depth+1, so
     *                                      depth=1 means entries of the root ZIP,
     *                                      depth=2+ means entries of nested ZIPs.
     * @param array    $ctx                 Shared analysis context passed by reference.
     *                                      Accumulates counters and size stats across
     *                                      all recursive calls.
     * @param callable $analyzeFileCallback Invoked with (tmpPath, name, size, error, depth, ctx)
     *                                      for each successfully extracted entry.
     * @param bool     $isRawContent        When true, $zipSource is treated as raw bytes
     *                                      rather than a file path.
     *
     * @return array Collected callback results, one entry per extracted file.
     *
     * @throws RuntimeException On upload error, depth exceeded, oversized input,
     *                          too many entries, extraction limit, or processing timeout.
     */
    public function analyzeZip(
        string $zipSource,
        int $error,
        int $depth,
        array &$ctx,
        callable $analyzeFileCallback,
        bool $isRawContent = false
    ): array {
        if ($error !== UPLOAD_ERR_OK) {
            return [];
        }

        if ($depth >= self::MAX_DEPTH) {
            throw new RuntimeException('Maximum ZIP depth exceeded');
        }

        $ctx['zip_bytes']                     ??= 0;
        $ctx['max_depth']                     ??= 0;
        $ctx['root_files_without_extraction'] ??= 0;
        $ctx['failed_extraction_count_all']   ??= 0;
        $ctx['largest_file_size']             ??= 0;
        $ctx['largest_non_zip_file_size']     ??= 0;
        $ctx['smallest_non_zip_file_size']    ??= PHP_INT_MAX;
        $ctx['empty_file_count']              ??= 0;
        $ctx['largest_image_all']             ??= [
            'size'      => 0,
            'path'      => null,
            'mime_type' => null,
            'width'     => null,
            'height'    => null,
        ];
        $ctx['aspect_ratios_all'] ??= [];

        $start  = microtime(true);
        $zip    = new ZipArchive();
        $tmpZip = null;

        if ($isRawContent) {
            if (strlen($zipSource) > self::MAX_ZIP_INPUT_SIZE) {
                throw new RuntimeException('ZIP content too large');
            }

            $tmpZip = tempnam(sys_get_temp_dir(), 'zip_');

            if ($tmpZip === false) {
                return [];
            }

            if (file_put_contents($tmpZip, $zipSource) === false) {
                @unlink($tmpZip);
                return [];
            }

            $open = $zip->open($tmpZip);
        } else {
            if (is_file($zipSource)) {
                $realSize = filesize($zipSource);

                if ($realSize === false || $realSize > self::MAX_ZIP_INPUT_SIZE) {
                    throw new RuntimeException('ZIP file too large');
                }
            }

            $open = $zip->open($zipSource);
        }

        if ($open !== true) {
            if ($tmpZip !== null) {
                @unlink($tmpZip);
            }

            return [];
        }

        $results = [];

        try {
            if ($zip->numFiles <= 0) {
                throw new RuntimeException('Invalid ZIP archive');
            }

            if ($zip->numFiles > self::MAX_FILES) {
                throw new RuntimeException('Too many ZIP entries');
            }

            $numFiles = $zip->numFiles;

            for ($i = 0; $i < $numFiles; $i++) {
                if (
                    $i % 50 === 0
                    && (microtime(true) - $start) > self::MAX_SECONDS
                ) {
                    throw new RuntimeException('ZIP processing timeout');
                }

                $stat = $zip->statIndex($i);

                if (!$stat || empty($stat['name'])) {
                    continue;
                }

                $name = $stat['name'];

                if (str_contains($name, '\\')) {
                    $name = str_replace('\\', '/', $name);
                }

                // Technical noise: ignore completely, no counters touched.
                $baseName = basename($name);

                if (
                    str_contains($name, '..')
                    || str_starts_with($name, '/')
                    || str_contains($name, "\0")
                    || str_starts_with($name, '__MACOSX/')
                    || str_starts_with($baseName, '._')
                    || $baseName === '.DS_Store'
                ) {
                    continue;
                }

                // Directory entries: also ignored, no counters touched.
                if (str_ends_with($name, '/')) {
                    continue;
                }

                if ($depth === 1) {
                    $ctx['root_files_without_extraction']++;
                }

                $uncompressed   = (int) ($stat['size'] ?? 0);
                $compressedSize = (int) ($stat['comp_size'] ?? 0);

                // Suspicious compression ratio (ZIP bomb) → counts as not extracted.
                if (
                    $uncompressed > 0
                    && (
                        $compressedSize === 0
                        || ($uncompressed / $compressedSize) > self::MAX_RATIO
                    )
                ) {
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                // Empty files are their own category, NOT counted in failed_extraction_count_all.
                if ($uncompressed === 0) {
                    ++$ctx['empty_file_count'];
                    continue;
                }

                // Too large → counts as not extracted.
                if ($uncompressed > self::MAX_ENTRY_SIZE) {
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                if (($ctx['zip_bytes'] + $uncompressed) > self::MAX_TOTAL_BYTES) {
                    throw new RuntimeException('ZIP total extraction size exceeded');
                }

                $stream = $zip->getStream($name);

                if ($stream === false) {
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                $tmpFile = tempnam(sys_get_temp_dir(), 'zipentry_');

                if ($tmpFile === false) {
                    fclose($stream);
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                $out = fopen($tmpFile, 'wb');

                if ($out === false) {
                    fclose($stream);
                    @unlink($tmpFile);
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                $writtenBytes = 0;
                $readError    = false;

                while (!feof($stream)) {
                    $chunk = fread($stream, self::STREAM_CHUNK);

                    if ($chunk === false) {
                        $readError = true;
                        break;
                    }

                    if ($chunk === '') {
                        continue;
                    }

                    fwrite($out, $chunk);
                    $writtenBytes += strlen($chunk);
                }

                fclose($stream);
                fclose($out);

                // Read error / incomplete write → counts as not extracted.
                if ($readError || $writtenBytes !== $uncompressed) {
                    @unlink($tmpFile);
                    $ctx['failed_extraction_count_all']++;
                    continue;
                }

                if ((microtime(true) - $start) > self::MAX_SECONDS) {
                    @unlink($tmpFile);
                    throw new RuntimeException('ZIP processing timeout');
                }

                $ctx['zip_bytes'] += $writtenBytes;

                try {
                    $results[] = $analyzeFileCallback(
                        $tmpFile,
                        $name,
                        $uncompressed,
                        0,
                        $depth,
                        $ctx
                    );
                } finally {
                    @unlink($tmpFile);
                }
            }
        } finally {
            $zip->close();

            if ($tmpZip !== null) {
                @unlink($tmpZip);
            }
        }

        return $results;
    }

    /**
     * Collect all file names from a parsed ZIP entry tree.
     *
     * Recursively traverses the nested 'files' arrays produced by analyzeFile()
     * and returns a flat list of all contained file names. The root entry itself
     * is excluded since it represents the ZIP archive, not a contained file.
     *
     * @param array $data   Parsed ZIP entry. May contain a nested 'files' key
     *                      with child entries.
     * @param bool  $isRoot Pass true for the top-level call to exclude the root
     *                      entry's own name. Defaults to true.
     *
     * @return string[] Flat list of all contained file names.
     */
    public function collectFileNames(array $data, bool $isRoot = true): array
    {
        $names = [];

        if (!$isRoot && isset($data['name'])) {
            $names[] = $data['name'];
        }

        if (isset($data['files']) && is_array($data['files'])) {
            foreach ($data['files'] as $file) {
                foreach ($this->collectFileNames($file, false) as $name) {
                    $names[] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * Determine whether deep scan mode is enabled based on the rule parameters.
     *
     * Deep scan is enabled by passing the string "deepScan" as $params[1].
     *
     * @param array $params Input parameters from the validation rule.
     *
     * @return bool True if deep scan is requested, otherwise false.
     *
     * @throws InvalidArgumentException If $params[1] is present but not a string.
     */
    public function deepScanFiles(array $params): bool
    {
        if (!isset($params[1])) {
            return false;
        }

        if (!is_string($params[1])) {
            throw new InvalidArgumentException(
                'Param 2 must be a string with the value "deepScan".'
            );
        }

        return $params[1] === 'deepScan';
    }
}