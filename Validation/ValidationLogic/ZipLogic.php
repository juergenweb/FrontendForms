<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * ZIP archive validation logic.
 *
 * Provides validation rules for uploaded ZIP files,
 * including structure, size, depth, and content constraints.
 */
class ZipLogic extends FileLogic
{
    /**
     * Helper used for ZIP archive analysis.
     */
    private ZipHelper $zipHelper;

    /**
     * Create a new ZIP validation logic instance.
     *
     * @param FileHelper $fileHelper Helper dependency for file upload analysis.
     * @param MimeHelper $mimeHelper Helper dependency for MIME type detection.
     * @param ZipHelper  $zipHelper  Helper dependency for ZIP archive analysis.
     */
    public function __construct(FileHelper $fileHelper, MimeHelper $mimeHelper, ZipHelper $zipHelper)
    {
        parent::__construct($fileHelper, $mimeHelper);

        $this->zipHelper = $zipHelper;
    }

    /**
     * Validate that the number of files inside each uploaded ZIP archive does not
     * exceed the configured maximum. Pass "deepScan" as $params[1] to count files
     * inside nested ZIPs as well; otherwise only root-level files are counted.
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] max file count (positive int), [1] optional "deepScan".
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the file count is within the allowed maximum.
     */
    public function notExceedMaxNumberOfFilesInZIPFolder(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $maxFilesNumber = BaseHelper::getPositiveInt(
            $this->resolveStringParam(
                $params,
                'allowed number of files inside a ZIP archive as integer'
            ),
            'maxFilesInZIPFolder'
        );

        $statKey = $this->zipHelper->deepScanFiles($params)
            ? 'max_files_count_zip_extracted'
            : 'max_files_count_zip_none_extracted';

        return $this->getUploadFieldStatistics($field, $statKey) <= $maxFilesNumber;
    }

    /**
     * Validate that the number of files inside each uploaded ZIP archive meets
     * the configured minimum. Pass "deepScan" as $params[1] to count files inside
     * nested ZIPs as well. Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] min file count (positive int), [1] optional "deepScan".
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the file count meets or exceeds the required minimum.
     */
    public function overMinFilesInZIPFolder(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $minFilesNumber = BaseHelper::getPositiveInt(
            $this->resolveStringParam(
                $params,
                'allowed number of files inside a ZIP archive as integer'
            ),
            'minFilesInZIPFolder'
        );

        if ($this->getUploadFieldStatistics($field, 'zip_count_root') === 0) {
            return true;
        }

        $statKey = $this->zipHelper->deepScanFiles($params)
            ? 'max_files_count_zip_extracted'
            : 'max_files_count_zip_none_extracted';

        return $this->getUploadFieldStatistics($field, $statKey) >= $minFilesNumber;
    }

    /**
     * Validate that the total uncompressed size of each uploaded ZIP archive
     * does not exceed the configured limit (e.g. "50M", "1G").
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] max total uncompressed size as a human-readable string.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the total uncompressed size is within the allowed limit.
     */
    public function notExceededTotalFileSizeZipUncompressed(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $rawParam = $this->resolveStringParam(
            $params,
            'allowed filesize of all files in total uncompressed.'
        );

        $maxSize = FileHelper::sizeToBytes($rawParam[0] ?? null);

        return $this->getUploadFieldStatistics(
                $field,
                'largest_zip_file_size_root_uncompressed'
            ) <= $maxSize;
    }

    /**
     * Validate that all required file names (including extension) are present
     * inside every uploaded ZIP archive. Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  One or more required file names (e.g. "README.txt").
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if all required file names are found in every ZIP.
     */
    public function requiredFileNamesInZipArePresent(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $requiredNames = array_flip(
            $this->resolveStringParam(
                $params,
                'names of files (including extension) inside a ZIP archive as array'
            )
        );

        foreach ($this->getAllZipFiles($field) as $zipFile) {
            if (($zipFile['files_count_zip_none_extracted'] ?? 0) === 0) {
                return false;
            }

            $fileNamesLookup = array_flip(
                array_map(
                    'basename',
                    $this->zipHelper->collectFileNames($zipFile)
                )
            );

            foreach ($requiredNames as $requiredName => $_) {
                if (!isset($fileNamesLookup[$requiredName])) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate that the folder nesting depth inside uploaded ZIP archives
     * does not exceed the configured maximum.
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] maximum allowed nesting depth (positive int).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the ZIP depth is within the allowed maximum.
     */
    public function notExceedMaxDepthOfZipFolders(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $maxDepthsNumber = BaseHelper::getPositiveInt(
            $this->resolveStringParam(
                $params,
                'allowed depth inside a ZIP archive as integer'
            ),
            'maxDepthOfZipFolders'
        );

        return $this->getUploadFieldStatistics($field, 'max_depth') <= $maxDepthsNumber;
    }

    /**
     * Validate that the number of uploaded ZIP files for the field does not
     * exceed the configured maximum.
     * Fields without any ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] maximum number of ZIP files (positive int).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the number of ZIP files is within the allowed maximum.
     */
    public function notExceedMaxNumberOfZipFolders(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $maxZipNumber = BaseHelper::getPositiveInt(
            $this->resolveStringParam(
                $params,
                'allowed number of ZIP files as integer'
            ),
            'maxNumberOfZipFolders'
        );

        return $this->getUploadFieldStatistics($field, 'zip_count_root') <= $maxZipNumber;
    }

    /**
     * Validate that every file extension found inside uploaded ZIP archives
     * is on the allowed list. Pass "deepScan" as $params[1] to check nested ZIPs too.
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  Allowed extensions (e.g. "jpg", "png"), [last] optional "deepScan".
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if all found extensions are within the allowed list.
     */
    public function containsOnlyAllowedExtensionsInZipFolder(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $allowedExtensions = array_map(
            'strtolower',
            $this->resolveStringParam(
                $params,
                'allowed extensions inside ZIP archive'
            )
        );

        $statKey = $this->zipHelper->deepScanFiles($params)
            ? 'extensions_zip_all_extracted'
            : 'extensions_zip_all';

        $existingExtensions = array_map(
            'strtolower',
            (array) $this->getUploadFieldStatistics($field, $statKey)
        );

        return empty(array_diff($existingExtensions, $allowedExtensions));
    }

    /**
     * Validate that no forbidden file extension appears inside uploaded ZIP archives.
     * Pass "deepScan" as $params[1] to check nested ZIPs too.
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  Forbidden extensions (e.g. "exe", "php"), [last] optional "deepScan".
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if no forbidden extension is found in the ZIP contents.
     */
    public function containsNoForbiddenFileExtensionsInZipFolder(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $forbiddenExtensions = array_map(
            'strtolower',
            $this->resolveStringParam(
                $params,
                'forbidden extensions inside ZIP archive'
            )
        );

        $statKey = $this->zipHelper->deepScanFiles($params)
            ? 'extensions_zip_all_extracted'
            : 'extensions_zip_all';

        $existingExtensions = array_map(
            'strtolower',
            (array) $this->getUploadFieldStatistics($field, $statKey)
        );

        return empty(array_intersect($forbiddenExtensions, $existingExtensions));
    }

    /**
     * Validate that no individual file inside uploaded ZIP archives exceeds
     * the configured size limit (e.g. "10M"). Pass "deepScan" as $params[1]
     * to check files in nested ZIPs too.
     * Fields without a ZIP upload are treated as valid.
     *
     * @param string $field   Name of the upload field being validated.
     * @param mixed  $_value  Current field value (unused).
     * @param array  $params  [0] max single-file size as human-readable string, [1] optional "deepScan".
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if no individual file exceeds the size limit.
     */
    public function isWithinMaxAllowedFileSizeInZipFolder(
        string $field,
        mixed $_value,
        array $params,
        array $_fields
    ): bool {
        $maxSize = FileHelper::sizeToBytes(
            $this->resolveStringParam(
                $params,
                'max file size inside ZIP archive'
            )[0] ?? null
        );

        $statKey = $this->zipHelper->deepScanFiles($params)
            ? 'largest_file_size_in_zip_extracted'
            : 'largest_file_size_in_zip';

        return $this->getUploadFieldStatistics($field, $statKey) <= $maxSize;
    }

    /**
     * Return all uploaded ZIP files for a given field, identified by MIME type.
     *
     * @param string $field Name of the upload field.
     *
     * @return array[] List of ZIP file entries from the upload statistics.
     */
    private function getAllZipFiles(string $field): array
    {
        $zipFiles = [];

        foreach ($this->getUploadedFiles($field) as $file) {
            $mimeType = $file['mime_type'] ?? $file['type'] ?? null;

            if (in_array($mimeType, FileHelper::ZIP_MIME_TYPES, true)) {
                $zipFiles[] = $file;
            }
        }

        return $zipFiles;
    }
}