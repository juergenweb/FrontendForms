<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\ZipHelper;
use FrontendForms\FileHelper;
use FrontendForms\MimeHelper;
use FrontendForms\ZipLogic;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Unit tests for ZipLogic validation methods.
 *
 * Covers: file count limits, total size, required file names,
 * ZIP count, folder depth, allowed/forbidden extensions,
 * and maximum individual file size inside ZIP archives.
 */
final class ZipLogicTest extends TestCase
{
    /**
     * Reset the upload superglobal after each test.
     */
    protected function tearDown(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];
    }

    /**
     * Build a ZipLogic instance with real FileHelper, MimeHelper, and
     * ZipHelper dependencies, shared across all tests to avoid repeating
     * the same setup in every test method.
     */
    private function createLogic(): ZipLogic
    {
        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();
        $zipHelper = new ZipHelper();

        return new ZipLogic(
            $fileHelper,
            $mimeHelper,
            $zipHelper
        );
    }

    /**
     * Computes the total uncompressed size of a ZIP archive's root-level
     * entries only (matching FileHelper's "size_unzipped" statistic,
     * documented as "root ZIPs only, depth=0" - a nested ZIP counts as a
     * single opaque entry via its own uncompressed size, not recursively
     * unpacked). Used to derive test limits dynamically from whatever the
     * fixture file actually contains, instead of a hardcoded byte count
     * that would silently go stale whenever the fixture changes.
     */
    /**
     * Calls FileLogic's own protected getUploadFieldStatistics() via
     * reflection to read the exact statistic value the production
     * validation code itself computes for the given field/key - this
     * guarantees the test's expected value always matches whatever the
     * real scanning/filtering logic produces, instead of reimplementing
     * that logic (and risking it drifting out of sync) in the test suite.
     * $_FILES[$field] must already be populated before calling this.
     */
    private function getRealUploadStat(ZipLogic $logic, string $field, string $statKey): mixed
    {
        $reflection = new \ReflectionMethod($logic, 'getUploadFieldStatistics');
        $reflection->setAccessible(true);

        return $reflection->invoke($logic, $field, $statKey);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxFilesInZIPFolder
    | Method: notExceedMaxNumberOfFilesInZIPFolder
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures validation passes when no ZIP files are uploaded.
     */
    public function testNoUploadedFilesMaxReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['10'],
                []
            )
        );
    }

    /**
     * Ensures ZIP files below limit pass validation.
     */
    public function testZipBelowLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['100'],
                []
            )
        );
    }

    /**
     * Ensures ZIP files exactly at limit pass validation.
     */
    public function testZipEqualsLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['9999'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP file count exceeds limit.
     */
    public function testZipAboveLimitReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files are evaluated correctly.
     */
    public function testMultipleZipFilesRespected(): void
    {
        $_FILES['upload'] = [
            'name' => ['a.zip', 'b.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['1000'],
                []
            )
        );
    }

    /**
     * Ensures missing ZIP metadata is handled safely.
     */
    public function testMissingZipKeyIsSafe(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                ['9999'],
                []
            )
        );
    }

    /**
     * Ensures zero limit throws exception.
     */
    public function testZeroLimitFilesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->notExceedMaxNumberOfFilesInZIPFolder(
            'upload',
            null,
            ['0'],
            []
        );
    }

    /**
     * Ensures NULL limit throws exception.
     */
    public function testNullLimitFilesThrowsException(): void
    {

        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->notExceedMaxNumberOfFilesInZIPFolder(
            'upload',
            null,
            [null],
            []
        );
    }

    /**
     * Ensures whitespace values are accepted.
     */
    public function testWhitespaceLimitMaxAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfFilesInZIPFolder(
                'upload',
                null,
                [' 10 '],
                []
            )
        );
    }

    /**
     * Ensures negative limit throws exception.
     */
    public function testNegativeLimitMaxThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->notExceedMaxNumberOfFilesInZIPFolder(
            'upload',
            null,
            ['-5'],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: minFilesInZIPFolder
    | Method: overMinFilesInZIPFolder
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures validation passes when no files are uploaded.
     */
    public function testNoUploadedFilesMinReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                [10],
                []
            )
        );
    }

    /**
     * Ensures ZIP files above minimum pass validation.
     */
    public function testZipAboveMinLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures ZIP files exactly at minimum pass validation.
     */
    public function testZipEqualMinLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP file count is below minimum.
     */
    public function testZipBelowMinLimitReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                ['9999'],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files are evaluated against minimum.
     */
    public function testMultipleZipFilesAllMustRespectMin(): void
    {
        $_FILES['upload'] = [
            'name' => ['a.zip', 'b.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures missing file count is treated as zero and fails minimum check.
     */
    public function testEmptyZipFileIsTreatedAsZeroAndFails(): void
    {

        $_FILES['upload'] = [
            'name' => ['empty.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/empty.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/empty.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                [2],
                []
            )
        );
    }

    /**
     * Ensures zero minimum throws exception.
     */
    public function testZeroLimitThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->overMinFilesInZIPFolder(
            'upload',
            null,
            ['0'],
            []
        );
    }

    /**
     * Ensures NULL minimum throws exception.
     */
    public function testNullLimitThrowsException(): void
    {

        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->overMinFilesInZIPFolder(
            'upload',
            null,
            [null],
            []
        );
    }

    /**
     * Ensures whitespace minimum values are accepted.
     */
    public function testWhitespaceLimitMinAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->overMinFilesInZIPFolder(
                'upload',
                null,
                [' 1 '],
                []
            )
        );
    }

    /**
     * Ensures negative minimum throws exception.
     */
    public function testNegativeLimitMinThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $logic->overMinFilesInZIPFolder(
            'upload',
            null,
            ['-5'],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxTotalFileSizeZipUncompressed
    | Method: maxTotalFileSizeZipUncompressed
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures total uncompressed size below limit is accepted.
     */
    public function testTotalUncompressedSizeBelowLimitAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures total uncompressed size equal to limit is accepted.
     */
    public function testTotalUncompressedSizeEqualLimitAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $totalUncompressed = $this->getRealUploadStat($logic, 'upload', 'largest_zip_file_size_root_uncompressed');

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                [$totalUncompressed . 'B'],
                []
            )
        );
    }

    /**
     * Ensures total uncompressed size above limit is rejected.
     */
    public function testTotalUncompressedSizeAboveLimitRejected(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $totalUncompressed = $this->getRealUploadStat($logic, 'upload', 'largest_zip_file_size_root_uncompressed');

        $this->assertFalse(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                [($totalUncompressed - 1) . 'B'],
                []
            )
        );
    }

    /**
     * Ensures whitespace values are accepted.
     */
    public function testWhitespaceLimitAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                [' 10MB '],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files below limit are accepted.
     */
    public function testMultipleZipFilesBelowLimitAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures one ZIP file exceeding limit causes rejection.
     */
    public function testOneZipFileExceedingLimitRejected(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $totalUncompressed = $this->getRealUploadStat($logic, 'upload', 'largest_zip_file_size_root_uncompressed');

        $this->assertFalse(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                [($totalUncompressed - 1) . 'B'],
                []
            )
        );
    }

    /**
     * Ensures no ZIP files are accepted.
     */
    public function testNoZipFilesAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => []
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['1MB'],
                []
            )
        );
    }

    /**
     * Ensures kilobyte limits are supported.
     */
    public function testKilobyteLimitSupported(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $totalUncompressed = $this->getRealUploadStat($logic, 'upload', 'largest_zip_file_size_root_uncompressed');

        $this->assertFalse(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                [($totalUncompressed - 1) . 'B'],
                []
            )
        );
    }

    /**
     * Ensures megabyte limits are supported.
     */
    public function testMegabyteLimitSupported(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures gigabyte limits are supported.
     */
    public function testGigabyteLimitSupported(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['1GB'],
                []
            )
        );
    }

    /**
     * Ensures very large limits are handled correctly.
     */
    public function testVeryLargeLimitHandledCorrectly(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceededTotalFileSizeZipUncompressed(
                'upload',
                null,
                ['100GB'],
                []
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: requiredFileNamesInZip
    | Method: requiredFileNamesInZipArePresent
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures required file names are found in a single ZIP file.
     */
    public function testRequiredFileNamesFoundInSingleZip(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                [['900kb.jpg', 'apple-touch-icon-57-precomposed.png']],
                []
            )
        );
    }

    /**
     * Ensures required file names are found across multiple ZIP files.
     */
    public function testRequiredFileNamesFoundAcrossMultipleZips(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['sample-a4.pdf'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when a required file is missing.
     */
    public function testFailsWhenRequiredFileIsMissing(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['non-existing-file.pdf'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when multiple required files are missing.
     */
    public function testFailsWhenMultipleRequiredFilesAreMissing(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['missing1.pdf', 'missing2.png'],
                []
            )
        );
    }

    /**
     * Ensures validation passes when required files are split across nested ZIPs.
     */
    public function testRequiredFilesFoundInNestedZipStructure(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['sample-a4.pdf'],
                []
            )
        );
    }

    /**
     * Ensures validation returns false when ZIP is empty.
     */
    public function testFailsWhenZipIsEmpty(): void
    {
        $_FILES['upload'] = [
            'name' => ['empty.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/empty.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/empty.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['sample-a4.pdf'],
                []
            )
        );
    }

    /**
     * Ensures validation returns true when no required files are provided.
     */
    public function testThrowsExceptionWhenNoRequiredFilesProvided(): void
    {

        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->requiredFileNamesInZipArePresent(
            'upload',
            null,
            [],
            []
        );

    }

    /**
     * Ensures whitespace in required file names is handled correctly.
     */
    public function testWhitespaceInRequiredFileNamesHandledCorrectly(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                [' sample-a4.pdf '],
                []
            )
        );
    }

    /**
     * Ensures validation short-circuits when all required files are found early.
     */
    public function testShortCircuitsWhenAllRequiredFilesFoundEarly(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->requiredFileNamesInZipArePresent(
                'upload',
                null,
                ['sample-a4.pdf', 'sample-multilingual-text.pdf'],
                []
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxNumberOfZipFolders
    | Method:notExceedMaxNumberOfZipFolders
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures validation passes when no files are uploaded.
     */
    public function testNoUploadedZipFilesReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures validation passes when no ZIP files are present.
     */
    public function testNoZipFilesReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures ZIP count below limit passes validation.
     */
    public function testZipCountBelowLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures ZIP count equal to limit passes validation.
     */
    public function testZipCountEqualsLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP count exceeds limit.
     */
    public function testZipCountAboveLimitReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures only ZIP files are counted in mixed uploads.
     */
    public function testOnlyZipFilesAreCounted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample.jpg', 'sample-a4.zip'],
            'type' => ['application/zip', 'image/jpeg', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures exception is thrown for zero ZIP limit.
     */
    public function testZeroLimitZipThrowsException(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->notExceedMaxNumberOfZipFolders(
            'upload',
            null,
            ['0'],
            []
        );
    }

    /**
     * Ensures exception is thrown for NULL ZIP limit.
     */
    public function testNullLimitZipThrowsException(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->notExceedMaxNumberOfZipFolders(
            'upload',
            null,
            [null],
            []
        );
    }

    /**
     * Ensures whitespace numeric values are accepted.
     */
    public function testAcceptsWhitespaceAroundInteger(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxNumberOfZipFolders(
                'upload',
                null,
                [' 5 '],
                []
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxDepthOfZipFolders
    | Method:notExceedMaxDepthOfZipFolders
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures ZIP depth below the configured limit is accepted.
     */
    public function testZipDepthBelowLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['3'],
                []
            )
        );
    }

    /**
     * Ensures ZIP depth equal to the configured limit is accepted.
     */
    public function testZipDepthEqualsLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures ZIP depth above the configured limit is rejected.
     */
    public function testZipDepthAboveLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures that Exception will be thrown if limit is zero.
     */
    public function testZeroDepthZipWithZeroLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->notExceedMaxDepthOfZipFolders(
            'upload',
            null,
            ['0'],
            []
        );
    }

    /**
     * Ensures ZIP archives with depth zero are accepted below a higher limit.
     */
    public function testZeroDepthZipBelowLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['5'],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP archives are accepted when all depths are within the limit.
     */
    public function testMultipleZipDepthsWithinLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP archives are rejected when one depth exceeds the limit.
     */
    public function testMultipleZipDepthsOneExceedsLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['1'],
                []
            )
        );
    }

    /**
     * Ensures only ZIP files are considered when validating archive depth.
     */
    public function testOnlyZipFilesAreUsedForDepthValidation(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample.jpg', 'sample-a4.zip'],
            'type' => ['application/zip', 'image/jpeg', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                ['2'],
                []
            )
        );
    }

    /**
     * Ensures exception is thrown when depth limit is NULL.
     */
    public function testNullDepthLimitThrowsException(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->notExceedMaxDepthOfZipFolders(
            'upload',
            null,
            [null],
            []
        );
    }

    /**
     * Ensures whitespace around numeric depth limits is accepted.
     */
    public function testAcceptsWhitespaceAroundDepthLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->notExceedMaxDepthOfZipFolders(
                'upload',
                null,
                [' 2 '],
                []
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: allowedFileExtensionsInZipFolder
    | Method: containsOnlyAllowedExtensionsInZipFolder
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures ZIP archive is accepted when all contained extensions are allowed.
     */
    public function testZipContainsOnlyAllowedExtensions(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['zip', 'png', 'pdf','jpg']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP extension is not allowed.
     */
    public function testZipExtensionNotAllowed(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['png', 'pdf']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP extension is not allowed.
     */
    public function testPdfExtensionAllowedInDeepScan(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['pdf','png','zip','jpg'], 'deepScan'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when PNG extension is not allowed.
     */
    public function testPngExtensionNotAllowed(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['zip', 'pdf']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when PDF extension is not allowed.
     */
    public function testPdfExtensionNotAllowed(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['zip', 'png']],
                []
            )
        );
    }

    /**
     * Ensures ZIP archive containing only PDF files is accepted.
     */
    public function testPdfOnlyZipAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['pdf']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when PDF files are not allowed.
     */
    public function testPdfOnlyZipRejected(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['png']],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP archives are accepted when all contained extensions are allowed.
     */
    public function testMultipleZipArchivesContainOnlyAllowedExtensions(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['zip', 'png', 'pdf','jpg']],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP archives are rejected when one archive contains a disallowed extension.
     */
    public function testMultipleZipArchivesContainDisallowedExtension(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['pdf']],
                []
            )
        );
    }

    /**
     * Ensures only ZIP uploads are considered during extension validation.
     */
    public function testOnlyZipFilesAreCheckedForAllowedExtensions(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', '800kb.gif', 'sample-a4.zip'],
            'type' => ['application/zip', 'image/gif', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/800kb.gif',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/800kb.gif'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsOnlyAllowedExtensionsInZipFolder(
                'upload',
                null,
                [['zip', 'png', 'pdf', 'jpg']],
                []
            )
        );
    }

    /**
     * Ensures exception is thrown when allowed extensions are NULL.
     */
    public function testNullAllowedExtensionsThrowsException(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->containsOnlyAllowedExtensionsInZipFolder(
            'upload',
            null,
            [null],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: notAllowedFileExtensionsInZipFolder
    | Method: containsNoForbiddenFileExtensionsInZipFolder
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures ZIP archive is accepted when no forbidden extensions are present.
     */
    public function testZipContainsNoForbiddenExtensionsReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['jpg', 'gif']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP contains a forbidden extension (zip).
     */
    public function testZipContainsForbiddenZipExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['zip']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP contains a forbidden extension (png).
     */
    public function testZipContainsForbiddenPngExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['png']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when ZIP contains a forbidden extension (pdf) in nested ZIP
     */
    public function testZipContainsForbiddenPdfExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['pdf'], 'deepScan'],
                []
            )
        );
    }

    /**
     * Ensures ZIP with only safe extensions passes validation.
     */
    public function testZipWithSafeExtensionsReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['exe', 'bat']],
                []
            )
        );
    }

    /**
     * Ensures ZIP containing only PDF files passes when PDF is not forbidden.
     */
    public function testPdfOnlyZipReturnsTrueWhenPdfNotForbidden(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['png', 'zip']],
                []
            )
        );
    }

    /**
     * Ensures validation fails when PDF is explicitly forbidden.
     */
    public function testPdfOnlyZipReturnsFalseWhenPdfIsForbidden(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['pdf']],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files are validated and one forbidden extension causes failure.
     */
    public function testMultipleZipFilesOneContainsForbiddenExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['zip']],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files pass when no forbidden extensions exist.
     */
    public function testMultipleZipFilesNoForbiddenExtensionsReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['exe', 'bat', 'rar']],
                []
            )
        );
    }

    /**
     * Ensures only ZIP files are evaluated and non-ZIP uploads are ignored.
     */
    public function testOnlyZipFilesAreCheckedForForbiddenExtensions(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample.jpg', 'sample-a4.zip'],
            'type' => ['application/zip', 'image/jpeg', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertFalse(
            $logic->containsNoForbiddenFileExtensionsInZipFolder(
                'upload',
                null,
                [['zip']],
                []
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxAllowedFileSizeOfFileInZipFolder
    | Method: isWithinMaxAllowedFileSizeInZipFolder
    |--------------------------------------------------------------------------

    */

    /**
     * Ensures ZIP file is accepted when all files are within max allowed file size.
     */
    public function testZipFilesWithinMaxAllowedFileSizeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures ZIP file is rejected when largest file exceeds allowed size.
     */
    public function testZipFileExceedsMaxAllowedFileSizeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $largestEntry = $this->getRealUploadStat($logic, 'upload', 'largest_file_size_in_zip');

        $this->assertFalse(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                [($largestEntry - 1) . 'B'],
                []
            )
        );
    }

    /**
     * Ensures ZIP file exactly at limit size is accepted.
     */
    public function testZipFileExactlyAtMaxAllowedFileSizeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                ['5MB'],
                []
            )
        );
    }

    /**
     * Ensures multiple ZIP files pass when all largest files are within limit.
     */
    public function testMultipleZipFilesWithinLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample-a4.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample-a4.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures validation fails when one ZIP file exceeds max allowed size.
     */
    public function testMultipleZipFilesOneExceedsLimitReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample-a4.zip'],
            'type' => ['application/zip', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $largestEntry = $this->getRealUploadStat($logic, 'upload', 'largest_file_size_in_zip');

        $this->assertFalse(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                [($largestEntry - 1) . 'B'],
                []
            )
        );
    }

    /**
     * Ensures only ZIP files are evaluated for file size validation.
     */
    public function testOnlyZipFilesAreEvaluatedForFileSizeLimit(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip', 'sample.jpg', 'sample-a4.zip'],
            'type' => ['application/zip', 'image/jpeg', 'application/zip'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.zip',
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample-a4.zip'
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.zip'),
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample-a4.zip')
            ]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                ['10MB'],
                []
            )
        );
    }

    /**
     * Ensures exception is thrown when max file size parameter is NULL.
     */
    public function testNullMaxFileSizeThrowsException(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.zip')]
        ];

        $this->expectException(InvalidArgumentException::class);

        $logic = $this->createLogic();

        $logic->isWithinMaxAllowedFileSizeInZipFolder(
            'upload',
            null,
            [null],
            []
        );
    }

    /**
     * Ensures whitespace around file size parameter is accepted.
     */
    public function testWhitespaceAroundFileSizeParameterIsAccepted(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample-a4.zip'],
            'type' => ['application/zip'],
            'tmp_name' => [__DIR__ . '/fixtures/sample-a4.zip'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample-a4.zip')]
        ];

        $logic = $this->createLogic();

        $this->assertTrue(
            $logic->isWithinMaxAllowedFileSizeInZipFolder(
                'upload',
                null,
                [' 10MB '],
                []
            )
        );
    }

}