<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;

/**
 * Handles file validation logic.
 *
 * Available validators:
 * - phpIniUploadMaxFileSize
 * - phpIniPostMaxFileSize
 * - matchingExtMimeType
 * - noErrorOnUpload
 * - maxTotalFileSize
 * - maxSingleFileSize
 * - allowedFileExtensions
 * - forbiddenFileExtensions
 * - allowedMimeTypes
 * - forbiddenMimeTypes
 * - maxFileNumber
 * - minFileNumber
 * - aspectRatio
 * - minImageDimensions
 */
class FileLogic extends BaseLogic
{
    protected FileHelper $fileHelper;
    protected MimeHelper $mimeHelper;

    protected ?array $uploadData = null;

    /**
     * Create a new FileLogic instance and eagerly load/analyze the upload
     * data from $_FILES via the FileHelper.
     */
    public function __construct(
        FileHelper $fileHelper,
        MimeHelper $mimeHelper
    ) {
        $this->fileHelper = $fileHelper;
        $this->mimeHelper = $mimeHelper;
        $this->uploadData = $this->getUploadData();
    }

    /**
     * Validate the largest single uploaded file against the PHP
     * upload_max_filesize ini setting.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $_value Current field value (unused).
     * @param array  $_params Additional validator parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if no uploaded file exceeds upload_max_filesize.
     */
    public function validatePhpIniUploadMaxFileSize(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {

        return $this->validateSingleFileSize(
            $field,
            $_value,
            [(string) ini_get('upload_max_filesize')],
            $_fields
        );
    }

    /**
     * Validates PHP ini setting: post_max_size.
     */
    public function validatePhpIniPostMaxFileSize(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {
        return $this->validateTotalFileSize(
            $field,
            $_value,
            [(string) ini_get('post_max_size')],
            $_fields
        );
    }

    /**
     * Security check: ensures the file extension matches the MIME type
     * detected from the actual file content.
     *
     * A local MIME cache avoids repeated lookups for the same MIME type
     * within one call. Uses strict comparison in in_array().
     */
    public function validateMatchingExtMimeType(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {
        if ($this->hasNoUploads($field)) {
            return true;
        }

        $mimeCache = [];

        foreach ($this->getUploadedFiles($field) as $file) {
            if (!$this->fileHelper->isValidUpload($file)) {
                continue;
            }

            $extension = trim(strtolower((string) ($file['extension'] ?? '')));
            $mimeType = trim(strtolower((string) ($file['mime_type'] ?? '')));

            if ($extension === '' || $mimeType === '') {
                return false;
            }

            $mimeCache[$mimeType] ??= $this->mimeHelper->getAllValidExtensions($mimeType);

            if ($mimeCache[$mimeType] === []) {
                return false;
            }

            if (!in_array($extension, $mimeCache[$mimeType], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that no upload errors occurred.
     *
     * @param string $field   Name of the field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $_params Additional validator parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if no upload errors were recorded for this field.
     */
    public function validateNoErrorOnUpload(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {
        return empty(
        $this->getUploadFieldStatistics($field, 'upload_errors_all')
        );
    }

    /**
     * Validates the total upload file size against a configured maximum.
     */
    public function validateTotalFileSize(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        return $this->getUploadFieldStatistics($field, 'total_size_root')
            <= $this->resolveMaxSize($params);
    }

    /**
     * Validates that no single uploaded file exceeds the configured size limit.
     */
    public function validateSingleFileSize(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        return $this->getUploadFieldStatistics($field, 'largest_filesize_root')
            <= $this->resolveMaxSize($params);
    }

    /**
     * Validates allowed file extensions (root files only, not inside ZIPs).
     * If the extension list is empty, no restriction applies.
     */
    public function validateAllowedFileExtensions(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $allowedExtensions = $this->resolveStringParam($params, 'allowed extensions');

        $stats = $this->getUploadFieldStatistics($field);

        if (empty($stats) || ($stats['files_count_root'] ?? 0) === 0) {
            return true;
        }

        // A file without a detectable extension always fails.
        if (($stats['no_extension_root'] ?? 0) > 0) {
            return false;
        }

        $existingExt = $stats['extensions_root'] ?? [];

        if (empty($existingExt)) {
            return true;
        }

        $existingExtensions = array_map('strtolower', array_keys($existingExt));
        $allowedExtensions = array_map('strtolower', $allowedExtensions);

        return array_diff($existingExtensions, $allowedExtensions) === [];
    }

    /**
     * Validates forbidden file extensions (root files only, not inside ZIPs).
     * If the extension list is empty, no restriction applies.
     */
    public function validateForbiddenFileExtensions(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $forbiddenExtensions = $this->resolveStringParam($params, 'forbidden extensions');

        if ($this->hasNoUploads($field)) {
            return true;
        }

        if ($this->getUploadFieldStatistics($field, 'no_extension_root') > 0) {
            return false;
        }

        $existingExtensions = array_map(
            'strtolower',
            array_keys($this->getUploadFieldStatistics($field, 'extensions_root'))
        );

        $forbiddenExtensions = array_map('strtolower', $forbiddenExtensions);

        return array_intersect($existingExtensions, $forbiddenExtensions) === [];
    }

    /**
     * Validates allowed MIME types (root files only).
     * An empty allowed list means no restriction applies.
     */
    public function validateAllowedMimeTypes(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $allowedMimeTypes = array_map(
            'strtolower',
            $this->resolveStringParam($params, 'Array containing allowed mime types')
        );

        if (empty($allowedMimeTypes)) {
            return true;
        }

        $stats = $this->getUploadFieldStatistics($field);

        if (($stats['files_count_root'] ?? 0) === 0) {
            return true;
        }

        if (($stats['no_mime_type_root'] ?? 0) > 0) {
            return false;
        }

        $existingMimeTypes = array_map(
            'strtolower',
            array_keys($stats['mime_types_root'] ?? [])
        );

        return array_diff($existingMimeTypes, $allowedMimeTypes) === [];
    }

    /**
     * Validates forbidden MIME types (root files only).
     * An empty forbidden list means no restriction applies.
     */
    public function validateForbiddenMimeTypes(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $forbiddenMimeTypes = array_map(
            'strtolower',
            $this->resolveStringParam($params, 'Array containing forbidden mime types')
        );

        if (empty($forbiddenMimeTypes)) {
            return true;
        }

        $stats = $this->getUploadFieldStatistics($field);

        if (empty($stats) || ($stats['files_count_root'] ?? 0) === 0) {
            return true;
        }

        if (($stats['no_mime_type_root'] ?? 0) > 0) {
            return false;
        }

        $existingMimeTypes = array_map(
            'strtolower',
            array_keys($stats['mime_types_root'] ?? [])
        );

        return array_intersect($existingMimeTypes, $forbiddenMimeTypes) === [];
    }

    /**
     * Validates the maximum number of uploaded files.
     */
    public function validateMaxFileNumber(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $fileCount = $this->getUploadFieldStatistics($field, 'files_count_root');

        if ($fileCount === 0) {
            return true;
        }

        $values = $this->resolveStringParam(
            $params,
            'allowed number of files as integer'
        );

        $maxFileNumber = BaseHelper::normalizeNonNegativeInt(
            $values[0] ?? null
        );

        if ($maxFileNumber === null) {
            throw new InvalidArgumentException(
                'Please enter a positive number as the maximum number of files.'
            );
        }

        return $fileCount <= $maxFileNumber;
    }

    /**
     * Validates the minimum number of uploaded files.
     */
    public function validateMinFileNumber(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $fileCount = $this->getUploadFieldStatistics($field, 'files_count_root');

        if ($fileCount === 0) {
            return true;
        }

        $values = $this->resolveStringParam(
            $params,
            'required minimum number of files as integer'
        );

        $minFileNumber = BaseHelper::normalizeNonNegativeInt(
            $values[0] ?? null
        );

        if ($minFileNumber === null) {
            throw new InvalidArgumentException(
                'Please enter a positive number as the minimum number of files.'
            );
        }

        return $fileCount >= $minFileNumber;
    }

    /**
     * Validates that at least one file was uploaded.
     */
    public function isFileUploaded(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {
        return !$this->hasNoUploads($field);
    }

    /**
     * Validates that no empty files have been uploaded.
     */
    public function validateNoEmptyFiles(
        string $field,
        mixed $_value,
        array $_params,
        array $_fields
    ): bool {
        return $this->getUploadFieldStatistics($field, 'empty_file_count_root') === 0;
    }

    /**
     * Validates allowed aspect ratios.
     */
    public function validateAspectRatio(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        if ($this->hasNoUploads($field)) {
            return true;
        }

        $allowedRatios = $this->resolveStringParam($params, 'allowed aspect ratios');

        if (empty($allowedRatios)) {
            return true;
        }

        $existingRatios = array_keys(
            $this->getUploadFieldStatistics($field, 'aspect_ratios_root')
        );

        if (empty($existingRatios)) {
            return true;
        }

        $normalizedAllowed = array_map(
            fn (string $ratio): string => $this->normalizeRatio($ratio, true),
            $allowedRatios
        );

        $normalizedExisting = array_map(
            fn (string $ratio): string => $this->normalizeRatio($ratio, false),
            $existingRatios
        );

        return array_diff($normalizedExisting, $normalizedAllowed) === [];
    }

    /**
     * Validates minimum image dimensions.
     */
    public function validateMinImageDimensions(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        if ($this->hasNoUploads($field)) {
            return true;
        }

        [$minWidth, $minHeight] = $this->resolveDimensionsParam($params);

        foreach ($this->getUploadedFiles($field) as $file) {
            if (!$this->fileHelper->isValidUpload($file) || empty($file['is_image'])) {
                continue;
            }

            $width = $file['width'] ?? null;
            $height = $file['height'] ?? null;

            if (!is_int($width) || !is_int($height)) {
                return false;
            }

            if ($width < $minWidth || $height < $minHeight) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate that all uploaded file(s) in the given field have a unique
     * filename within the form's upload directory.
     *
     * Supports both single-file uploads (where $value itself is the file
     * entry) and multi-file uploads (where $value is a list of file entries).
     * Delegates the actual duplicate check and any rename/overwrite handling
     * to FileHelper::checkDuplicateFilename().
     *
     * @param string $field   Name of the form field being validated.
     * @param mixed  $value   Raw upload data for the field (single file array
     *                         or list of file arrays).
     * @param array  $params  Validation parameters passed through to
     *                         checkDuplicateFilename() (e.g. overwrite flag).
     * @param array  $_fields All form field values (unused by this rule).
     *
     * @return bool True if all files have a unique filename (or were
     *              successfully renamed), false as soon as one file is
     *              rejected as a duplicate.
     */
    public function validateUniqueFilenameInDir(
        string $field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        if (!is_array($value)) {
            return false;
        }

        // normalize single-file upload ($value is itself the file entry)
        // vs. multi-file upload ($value is a list of file entries)
        $files = isset($value['name']) ? [$value] : $value;

        $uploadPath = $this->form->getUploadPath();

        foreach ($files as $file) {
            if (!$this->fileHelper->checkDuplicateFilename($file, $uploadPath, $params)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Reduces an aspect ratio string to the lowest terms.
     */
    protected function normalizeRatio(string $ratio, bool $strict): string
    {
        $trimmed = trim($ratio);

        if (!preg_match('/^(\d{1,15}):(\d{1,15})$/', $trimmed, $matches)) {
            if ($strict) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid aspect ratio "%s". Use format WIDTH:HEIGHT (e.g. 16:9).',
                        $ratio
                    )
                );
            }

            return $trimmed;
        }

        $width = (int) $matches[1];
        $height = (int) $matches[2];

        if ($width === 0 || $height === 0) {
            if ($strict) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Invalid aspect ratio "%s". Width and height must be greater than zero.',
                        $ratio
                    )
                );
            }

            return $trimmed;
        }

        $divisor = $this->gcd($width, $height);

        return intdiv($width, $divisor) . ':' . intdiv($height, $divisor);
    }

    /**
     * Calculates the greatest common divisor.
     */
    protected function gcd(int $a, int $b): int
    {
        return $b === 0 ? $a : $this->gcd($b, $a % $b);
    }

    /**
     * Resolves WIDTHxHEIGHT parameter.
     *
     * Accepts the dimensions either as a plain string (the intended,
     * documented usage - e.g. setRule('minImageDimensions', '400x200'))
     * or, for backward compatibility, already wrapped in an array (e.g.
     * ['400x200']) - both shapes are handled by resolveStringParam()
     * itself (via BaseLogic::normalizeStringArray()), so no extra
     * unwrapping/wrapping is needed here.
     * @return array{0:int,1:int}
     */
    protected function resolveDimensionsParam(array $params): array
    {
        $values = $this->resolveStringParam(
            $params,
            'minimum width and height as WIDTHxHEIGHT'
        );

        if (count($values) !== 1) {
            throw new InvalidArgumentException(
                'Please provide dimensions in WIDTHxHEIGHT format.'
            );
        }

        $value = strtolower(trim((string) $values[0]));

        if (!preg_match('/^(\d{1,6})x(\d{1,6})$/', $value, $matches)) {
            throw new InvalidArgumentException(
                'Please provide dimensions in WIDTHxHEIGHT format.'
            );
        }

        $width = (int) $matches[1];
        $height = (int) $matches[2];

        if ($width === 0 || $height === 0) {
            throw new InvalidArgumentException(
                'Width and height must be greater than zero.'
            );
        }

        return [$width, $height];
    }

    /**
     * Checks if no uploads exist.
     */
    protected function hasNoUploads(string $field): bool
    {
        return $this->getUploadFieldStatistics($field, 'files_count_root') === 0;
    }

    /**
     * Resolves max file size into bytes.
     */
    public function resolveMaxSize(array $params): ?int
    {
        $values = $this->resolveStringParam($params, 'allowed file size');

        $maxSize = $this->fileHelper->normalizeFileSize($values[0] ?? null);

        if ($maxSize === null || $maxSize < 0) {
            return null;
        }

        return $maxSize;
    }

    /**
     * Returns uploaded files.
     */
    protected function getUploadedFiles(string $field): array
    {
        return $this->uploadData['files'][$field] ?? [];
    }

    /**
     * Returns statistics for upload field.
     */
    protected function getUploadFieldStatistics(
        string $field,
        ?string $key = null
    ): mixed {
        $default = [
            'files_count_root' => 0,
            'files_count_all' => 0,
            'total_size_root' => 0,
            'zip_count_root' => 0,
            'zip_count_all' => 0,
            'image_count_root' => 0,
            'image_count_all' => 0,
            'empty_file_count_root' => 0,
            'empty_file_count_all' => 0,
            'largest_filesize_root' => 0,
            'smallest_filesize_root' => 0,
            'largest_file_size' => 0,
            'smallest_file_size_all' => 0,
            'largest_non_zip_file_size' => 0,
            'smallest_non_zip_file_size' => 0,
            'non_zip_size_root' => 0,
            'zip_size_root' => 0,
            'max_depth' => 0,
            'aspect_ratios_root' => [],
            'aspect_ratios_all' => [],
            'extensions_root' => [],
            'extensions_all' => [],
            'mime_types_root' => [],
            'mime_types_all' => [],
            'upload_errors_root' => [],
            'upload_errors_all' => [],
        ];

        $stats = $this->uploadData['stats']['fields'][$field] ?? $default;

        if ($key === null) {
            return $stats;
        }

        if (!array_key_exists($key, $stats)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Statistic key "%s" does not exist for upload field "%s".',
                    $key,
                    $field
                )
            );
        }

        return $stats[$key];
    }

    /**
     * Returns full upload data (lazy loaded).
     */
    public function getUploadData(): array
    {
        return $this->uploadData ??= $this->fileHelper->getUploadedFilesData($_FILES);
    }
}
