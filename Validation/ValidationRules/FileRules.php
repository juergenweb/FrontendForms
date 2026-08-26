<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom file validation rules.
 *
 * The actual validation logic is implemented inside
 * the FileLogic class.
 *
 * Validation rules:
 *
 * phpIniUploadMaxFileSize
 * phpIniPostMaxFileSize
 * allowedTotalFileSize / maxTotalFileSize
 * allowedFileSize / maxSingleFileSize
 * noErrorOnUpload
 * allowedMimeTypes
 * forbiddenMimeTypes
 * allowedFileExt
 * forbiddenFileExt
 * allowedFileNumber / maxFileNumber
 * minFileNumber
 * matchingExtMimeType
 * fileRequired
 * noEmptyFiles
 * minImageDimensions
 * aspectRatio
 */
class FileRules extends BaseRules
{

    /**
     * Factory used to build the FileLogic instance.
     */
    private LogicFactory $logicFactory;

    /**
     * Inject the LogicFactory used to create the validation service.
     *
     * @param LogicFactory $factory The logic factory instance.
     */
    public function setLogicFactory(LogicFactory $factory): void
    {
        $this->logicFactory = $factory;
    }

    /**
     * Register all file validation rules with Valitron.
     *
     * @throws RuntimeException If FileLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(FileLogic::class);

        $this->registerRules(
            [
                // Checks if upload_max_filesize (maximum file size of a single file) is not exceeded.
                'phpIniUploadMaxFileSize' => [
                    'validatePhpIniUploadMaxFileSize',
                    $this->_(
                        'contains files whose file size exceeds the maximum server upload file size of %s per file.'
                    ),
                ],

                // Checks if post_max_filesize (maximum total file size of all uploaded files) is not exceeded.
                'phpIniPostMaxFileSize' => [
                    'validatePhpIniPostMaxFileSize',
                    $this->_(
                        'contains files whose total file size exceeds the maximum allowed server upload file size of %s for all files together.'
                    ),
                ],

                // Checks that the total file size of all uploaded files is not exceeded.
                'allowedTotalFileSize' => [
                    'validateTotalFileSize',
                    $this->_('contains uploaded files whose total size exceeds %s.'),
                ],

                // Alias for allowedTotalFileSize.
                'maxTotalFileSize' => [
                    'validateTotalFileSize',
                    $this->_('contains files whose total upload size exceeds the maximum of %s.'),
                ],

                // Checks that the file size of a single file is not exceeded. Does not check files inside a ZIP archive.
                'allowedFileSize' => [
                    'validateSingleFileSize',
                    $this->_(
                        'contains at least one file that is larger than the allowed file size of %s.'
                    ),
                ],

                // Alias for allowedFileSize.
                'maxSingleFileSize' => [
                    'validateSingleFileSize',
                    $this->_(
                        'contains at least one file that is larger than the allowed file size of %s.'
                    ),
                ],

                // Verifies that no error occurred during the upload.
                'noErrorOnUpload' => [
                    'validateNoErrorOnUpload',
                    $this->_('encountered an error during the upload.'),
                ],

                // Verifies that all uploaded files have an allowed MIME type.
                'allowedMimeTypes' => [
                    'validateAllowedMimeTypes',
                    $this->_('contains at least one file that has an invalid MIME type.'),
                ],

                // Verifies that no uploaded file has a forbidden MIME type.
                'forbiddenMimeTypes' => [
                    'validateForbiddenMimeTypes',
                    $this->_('contains at least one file that has a forbidden MIME type.'),
                ],

                // Verifies that all uploaded files have an allowed file extension.
                'allowedFileExt' => [
                    'validateAllowedFileExtensions',
                    $this->_(
                        'contains at least one file that does not belong to the allowed file types: %s.'
                    ),
                ],

                // Verifies that no uploaded file has a forbidden file extension.
                'forbiddenFileExt' => [
                    'validateForbiddenFileExtensions',
                    $this->_(
                        'contains at least one file with a forbidden file extension: %s.'
                    ),
                ],

                // Verifies that no more files than allowed to are uploaded.
                'allowedFileNumber' => [
                    'validateMaxFileNumber',
                    $this->_('contains more than %s files.'),
                ],

                // Alias for allowedFileNumber.
                'maxFileNumber' => [
                    'validateMaxFileNumber',
                    $this->_('contains more than %s files.'),
                ],

                // Verifies that at least the required minimum number of files is uploaded.
                'minFileNumber' => [
                    'validateMinFileNumber',
                    $this->_('contains fewer than %s files.'),
                ],

                // Security check: verifies that MIME type, extension, and magic bytes match.
                'matchingExtMimeType' => [
                    'validateMatchingExtMimeType',
                    $this->_('contains at least one file whose upload was rejected.'),
                ],

                // Special required validator for file upload fields.
                'fileRequired' => [
                    'isFileUploaded',
                    $this->_('is required.'),
                ],

                // Rejects empty files (zero bytes) from the upload.
                'noEmptyFiles' => [
                    'validateNoEmptyFiles',
                    $this->_('must not contain empty files.'),
                ],

                // Validates that all uploaded images meet a minimum width and height.
                'minImageDimensions' => [
                    'validateMinImageDimensions',
                    $this->_(
                        'contains images whose dimensions are smaller than the minimum width and height of %s.'
                    ),
                ],

                // Validates that all uploaded images match one of the allowed aspect ratios.
                'aspectRatio' => [
                    'validateAspectRatio',
                    $this->_(
                        'contains at least one image whose aspect ratio is not among the allowed aspect ratios: {1}.'
                    ),
                ],

                // Check if the filename of an uploaded file is unique inside the upload directory
                'uniqueFilenameInDir' => [
                    'validateUniqueFilenameInDir',
                    $this->_(
                        'contains a file that shares its name with an existing file in the destination directory.'
                    ),
                ],

            ],
            $service
        );
    }
}
