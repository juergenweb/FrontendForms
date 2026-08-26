<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom ZIP validation rules.
 *
 * The actual validation logic is implemented inside
 * the ZipLogic class.
 */
class ZipRules extends BaseRules
{

    /**
     * Factory used to build the ZipLogic instance.
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
     * Register all ZIP validation rules with Valitron.
     *
     * @throws RuntimeException If ZipLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(ZipLogic::class);

        $this->registerRules(
            [
                // Checks if the maximum number of files inside a ZIP archive is not exceeded.
                'maxFilesInZIPFolder' => [
                    'notExceedMaxNumberOfFilesInZIPFolder',
                    $this->_(
                        'contains at least one ZIP archive with more than the allowed maximum of %s files.'
                    ),
                ],

                // Checks if the minimum number of files inside a ZIP archive is reached.
                'minFilesInZIPFolder' => [
                    'overMinFilesInZIPFolder',
                    $this->_(
                        'contains a ZIP archive with fewer than the required minimum of %s files.'
                    ),
                ],

                // Checks that the total uncompressed size of files inside a ZIP archive does not exceed the allowed limit.
                'maxTotalFileSizeZipUncompressed' => [
                    'notExceededTotalFileSizeZipUncompressed',
                    $this->_(
                        'contains a ZIP archive whose uncompressed files exceed the maximum total file size of %s.'
                    ),
                ],

                // Checks that all required file names are present inside the ZIP archive.
                'requiredFileNamesInZip' => [
                    'requiredFileNamesInZipArePresent',
                    $this->_(
                        'does not contain all required files inside the ZIP archive.'
                    ),
                ],

                // Checks if the number of uploaded ZIP archives exceeds the allowed maximum.
                'maxNumberOfZipFolders' => [
                    'notExceedMaxNumberOfZipFolders',
                    $this->_(
                        'contains more than the allowed maximum of %s ZIP archives.'
                    ),
                ],

                // Validates ZIP archive hierarchy depth.
                'maxDepthOfZipFolders' => [
                    'notExceedMaxDepthOfZipFolders',
                    $this->_(
                        'contains a ZIP archive with more nested folder levels than the allowed maximum of %s.'
                    ),
                ],

                // Validates allowed file extensions inside ZIP archives.
                'allowedFileTypesInZipFolder' => [
                    'containsOnlyAllowedExtensionsInZipFolder',
                    $this->_(
                        'contains at least one file of a disallowed type.'
                    ),
                ],

                // Alias for allowedFileTypesInZipFolder.
                'allowedFileExtensionsInZipFolder' => [
                    'containsOnlyAllowedExtensionsInZipFolder',
                    $this->_(
                        'contains at least one file whose extension is not allowed.'
                    ),
                ],

                // Validates that no forbidden file extensions are present inside ZIP archives.
                'notAllowedFileTypesInZipFolder' => [
                    'containsNoForbiddenFileExtensionsInZipFolder',
                    $this->_(
                        'contains at least one forbidden file extension.'
                    ),
                ],

                // Alias for notAllowedFileTypesInZipFolder.
                'forbiddenFileExtensionsInZipFolder' => [
                    'containsNoForbiddenFileExtensionsInZipFolder',
                    $this->_(
                        'contains at least one forbidden file extension.'
                    ),
                ],

                // Validates the maximum allowed file size of files inside ZIP archives.
                'maxAllowedFileSizeOfFileInZipFolder' => [
                    'isWithinMaxAllowedFileSizeInZipFolder',
                    $this->_(
                        'contains at least one file larger than the allowed maximum file size of %s.'
                    ),
                ],
            ],
            $service
        );
    }
}
