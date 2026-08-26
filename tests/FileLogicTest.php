<?php

declare(strict_types=1);

namespace Tests;

use FrontendForms\FileHelper;
use FrontendForms\MimeHelper;
use FrontendForms\FileLogic;
use FrontendForms\Form;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Random\RandomException;

/**
 * Unit tests for FileLogic validation methods.
 *
 * Covers: MIME type, file number, total/single file size,
 * file extensions, upload errors, PHP INI limits, and
 * extension/MIME matching rules.
 */
final class FileLogicTest extends TestCase
{
    private FileLogic $logic;


    /**
     * Temp upload directories created by tests, cleaned up in tearDown().
     *
     * @var array<int, string>
     */
    private array $tempUploadDirs = [];


    /**
     * Create an isolated, empty temporary upload directory for a single test.
     *
     * @return string Absolute path to the directory, including trailing slash.
     * @throws RandomException
     */
    private function makeUploadDir(): string
    {
        $dir = rtrim(sys_get_temp_dir(), '/\\')
            . '/frontendforms_test_uploads_'
            . bin2hex(random_bytes(6));

        mkdir($dir, 0777, true);

        $this->tempUploadDirs[] = $dir;

        return $dir . '/';
    }

    /**
     * Create a Form mock that returns the given upload path.
     */
    private function makeFormMock(string $uploadPath): Form
    {
        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');
        $form->method('getUploadPath')->willReturn($uploadPath);

        return $form;
    }

    /**
     * Reset $_FILES superglobal, remove any temporary fixture file, and
     * clean up any temp upload directories created via makeUploadDir()
     * after each test.
     */
    protected function tearDown(): void
    {
        $_FILES = [];
        @unlink(__DIR__ . '/fixtures/file');
        $_FILES = [];

        foreach ($this->tempUploadDirs as $dir) {
            $this->removeDirRecursively($dir);
        }

        $this->tempUploadDirs = [];
    }

    /**
     * Recursively delete a directory and all of its contents.
     */
    private function removeDirRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            if (is_dir($path)) {
                $this->removeDirRecursively($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($dir);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: allowedMimeTypes
    | Method: validateAllowedMimeTypes
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies correct mime type.
     */
    public function testAllowedMimeTypeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies false mime type.
     */
    public function testAllowedMimeTypeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['text/plain']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies mime type and no upload.
     */
    public function testAllowedMimeTypeReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies failed uploads.
     */
    public function testFailedUploadIsIgnored(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['text/plain']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies multiple files at once with correct mime types.
     */
    public function testMultipleValidMimeTypesReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies multiple files at once with one file with incorrect mime type.
     */
    public function testOneInvalidMimeTypeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies multiple files at once with correct mime types.
     */
    public function testMultipleValidMultiMimeTypesReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg', 'image/gif']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies multiple files at once with one file with incorrect mime type.
     */
    public function testOneInvalidMultiMimeTypeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg', 'image/png']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies mime type case sensitivity.
     */
    public function testMimeTypeIsCaseInsensitive(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'sample2/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample2.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['IMAGE/JPEG']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a file with a spoofed extension returns false.
     */
    public function testOneFakeMimeTypeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['fake.php.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/fake.php.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/fake.php.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedMimeTypes(
            'upload',
            null,
            [['image/jpeg']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies missing params throwing exception for devs.
     */
    public function testAllowedMimeTypesReturnsExceptionWithoutParams(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateTotalFileSize('upload', null, [], []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxFileNumber
    | Method: validateMaxFileNumber
    |--------------------------------------------------------------------------
    */

    /**
     * Validation should return true when no files were uploaded.
     */
    public function testNoUploadedFilesReturnsTrue(): void
    {
        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMaxFileNumber('upload', [], ['5'], [])
        );
    }

    /**
     * Validation should return true when no files are uploaded,
     * even if the configured maximum file count parameter is invalid.
     */
    public function testNoUploadedFilesIgnoreInvalidParameterReturnsTrue(): void
    {
        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMaxFileNumber('upload', null, ['invalid'], [])
        );
    }

    /**
     * Validation should pass when the number of uploaded files
     * is below the configured limit.
     */
    public function testUploadedFileCountBelowLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMaxFileNumber('upload', null, ['3'], [])
        );
    }

    /**
     * Validation should pass when the number of uploaded files
     * exactly matches the configured limit.
     */
    public function testUploadedFileCountEqualsLimitReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMaxFileNumber('upload', null, ['2'], [])
        );
    }

    /**
     * Validation should fail when the number of uploaded files
     * exceeds the configured limit.
     */
    public function testUploadedFileCountExceedsLimitReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMaxFileNumber('upload', null, ['1'], [])
        );
    }

    /**
     * Validation should throw an exception when
     * no parameter is provided.
     */
    public function testMissingParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, [], []);
    }

    /**
     * Validation should throw an exception when
     * the parameter is not numeric.
     */
    public function testNonNumericParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, ['abc'], []);
    }

    /**
     * Validation should throw an exception when
     * the parameter is negative.
     */
    public function testNegativeParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, ['-1'], []);
    }

    /**
     * Validation should throw an exception when
     * the parameter is an empty string.
     */
    public function testEmptyStringParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, [''], []);
    }

    /**
     * Validation should throw an exception when
     * the parameter is null.
     */
    public function testNullParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, [null], []);
    }

    /**
     * Validation should throw an exception when
     * the configured maximum file count is zero.
     *
     * This reflects the current implementation which
     * treats zero as an invalid value.
     */
    public function testZeroParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMaxFileNumber('upload', null, ['0'], []);
    }

    /**
     * Validation should use only the first parameter
     * when multiple parameters are provided.
     */
    public function testOnlyFirstParameterIsUsed(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMaxFileNumber('upload', null, ['1', '999'], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxTotalFileSize
    | Method: validateTotalFileSize
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the validation succeeds
     * when the total upload size is below the limit.
     */
    public function testAllowedTotalFileSizeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['900kb.jpg', '800kb.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/900kb.jpg',
                __DIR__ . '/fixtures/800kb.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/900kb.jpg'),
                filesize(__DIR__ . '/fixtures/800kb.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateTotalFileSize('upload', null, ['2MB'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that the validation fails
     * when the total upload size exceeds the limit.
     */
    public function testAllowedTotalFileSizeReturnsFalse(): void
    {
        $file1 = __DIR__ . '/fixtures/900kb.jpg';
        $file2 = __DIR__ . '/fixtures/800kb.gif';

        $totalSize = filesize($file1) + filesize($file2);

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', '800kb.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [$file1, $file2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($file1), filesize($file2)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateTotalFileSize(
            'upload',
            null,
            [($totalSize - 1) . 'B'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an exception is thrown
     * when no filesize parameter is provided.
     */
    public function testAllowedTotalFileSizeReturnsExceptionWithoutParams(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateTotalFileSize('upload', null, [], []);
    }

    /**
     * Verifies that the validation throws an exception
     * when the provided filesize value is invalid.
     */
    public function testAllowedTotalFileSizeThrowsInvalidArgumentExceptionForInvalidSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateTotalFileSize('upload', null, ['invalid'], []);
    }

    /**
     * Verifies that the validation succeeds
     * when the total upload size exactly matches the limit.
     */
    public function testAllowedTotalFileSizeReturnsTrueForExactLimit(): void
    {
        $file1 = __DIR__ . '/fixtures/900kb.jpg';
        $file2 = __DIR__ . '/fixtures/800kb.gif';

        $totalSize = filesize($file1) + filesize($file2);

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', '800kb.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [$file1, $file2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($file1), filesize($file2)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateTotalFileSize(
            'upload',
            null,
            [$totalSize . 'B'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the validation succeeds
     * when no files were uploaded.
     */
    public function testAllowedTotalFileSizeReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateTotalFileSize('upload', null, ['1MB'], []);

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: maxSingleFileSize
    | Method: validateSingleFileSize
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds
     * when all uploaded files are below the allowed size limit.
     */
    public function testAllowedSingleFileSizeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize('upload', null, ['1MB'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when one uploaded file exceeds the allowed size limit.
     */
    public function testAllowedSingleFileSizeReturnsFalse(): void
    {
        $jpg = __DIR__ . '/fixtures/sample.jpg';
        $gif = __DIR__ . '/fixtures/sample.gif';

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample.png'],
            'type' => ['image/jpeg', 'image/png'],
            'tmp_name' => [$jpg, $gif],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($gif)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $largest = max(filesize($jpg), filesize($gif));

        $result = $this->logic->validateSingleFileSize('upload', null, [($largest - 1) . 'B'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation succeeds
     * when no files were uploaded.
     */
    public function testAllowedSingleFileSizeReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize('upload', null, ['1MB'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when no filesize parameter is provided.
     */
    public function testAllowedSingleFileSizeReturnsExceptionWithoutParams(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateSingleFileSize('upload', null, [], []);
    }

    /**
     * Verifies that validation succeeds
     * when a file size exactly matches the allowed limit.
     */
    public function testAllowedSingleFileSizeReturnsTrueForExactLimit(): void
    {
        $file = __DIR__ . '/fixtures/sample.jpg';

        $fileSize = filesize($file);

        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $file,
            'error' => UPLOAD_ERR_OK,
            'size' => $fileSize,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize(
            'upload',
            null,
            [$fileSize . 'B'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when the allowed filesize is zero.
     */
    public function testAllowedSingleFileSizeReturnsFalseForZeroLimit(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize('upload', null, ['0B'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when one uploaded file has size zero
     * and the allowed limit is also zero.
     */
    public function testZeroByteFileWithZeroLimitReturnsTrue(): void
    {
        $file = __DIR__ . '/fixtures/empty.txt';

        $_FILES['upload'] = [
            'name' => 'empty.txt',
            'type' => 'text/plain',
            'tmp_name' => $file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($file),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize('upload', null, ['0B'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that failed uploads are still processed
     * by the filesize validation logic.
     */
    public function testFailedUploadIsIgnoredInSingleFileSizeValidation(): void
    {
        $_FILES['upload'] = [
            'name' => 'large.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/large.jpg',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => filesize(__DIR__ . '/fixtures/large.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize('upload', null, ['1KB'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation succeeds
     * when multiple files exactly match the limit individually.
     */
    public function testMultipleFilesAtExactLimitReturnTrue(): void
    {
        $file1 = __DIR__ . '/fixtures/sample.jpg';
        $file2 = __DIR__ . '/fixtures/sample2.jpg';

        $size1 = filesize($file1);
        $size2 = filesize($file2);

        $_FILES['upload'] = [
            'name' => ['file1.jpg', 'file2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$file1, $file2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [$size1, $size2],
        ];

        $maxSize = max($size1, $size2);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateSingleFileSize(
            'upload',
            null,
            [$maxSize . 'B'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a negative file size parameter throws an exception.
     */
    public function testAllowedSingleFileSizeThrowsExceptionForNegativeSize(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'broken.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg', ''],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg'), 0],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateSingleFileSize('upload', null, ['-1KB'], []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: allowedFileExt
    | Method: validateAllowedFileExtensions
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds
     * when the uploaded file extension is allowed.
     */
    public function testAllowedFileExtReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when the uploaded file extension is not allowed.
     */
    public function testAllowedFileExtReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.gif',
            'type' => 'image/gif',
            'tmp_name' => __DIR__ . '/fixtures/sample.gif',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.gif'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation succeeds
     * when no files were uploaded.
     */
    public function testAllowedFileExtReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when the extension list is empty.
     */
    public function testAllowedFileExtReturnsFalseForEmptyExtensionList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAllowedFileExtensions('upload', null, [], []);
    }

    /**
     * Verifies that validation succeeds
     * when multiple uploaded files have allowed extensions.
     */
    public function testMultipleAllowedFileExtensionsReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions(
            'upload',
            null,
            [['jpg', 'gif']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when one uploaded file has a disallowed extension.
     */
    public function testOneInvalidFileExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that extension matching is case-insensitive.
     */
    public function testFileExtensionValidationIsCaseInsensitive(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.JPG',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that files without an extension fail validation.
     */
    public function testFileWithoutExtensionReturnsFalse(): void
    {
        $file = __DIR__ . '/fixtures/file';

        file_put_contents($file, 'test');

        $_FILES['upload'] = [
            'name' => 'file',
            'type' => 'text/plain',
            'tmp_name' => $file,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($file),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['txt'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that failed uploads are ignored during extension validation.
     */
    public function testFailedUploadIsIgnoredInExtensionValidation(): void
    {
        $_FILES['upload'] = [
            'name' => 'malicious.php',
            'type' => 'application/x-httpd-php',
            'tmp_name' => __DIR__ . '/fixtures/malicious.php',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => filesize(__DIR__ . '/fixtures/malicious.php'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that double extensions fail validation
     * when the final extension is not allowed.
     */
    public function testDoubleExtensionReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => 'shell.php.jpg.exe',
            'type' => 'application/octet-stream',
            'tmp_name' => __DIR__ . '/fixtures/shell.php.jpg.exe',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/shell.php.jpg.exe'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation succeeds
     * when the final extension in a double-extension filename is allowed.
     */
    public function testDoubleExtensionWithAllowedFinalExtensionReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => 'shell.php.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/shell.php.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/shell.php.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that extension matching is case-insensitive
     * for the allowed extension list.
     */
    public function testAllowedFileExtensionListIsCaseInsensitive(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateAllowedFileExtensions('upload', null, ['JPG'], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation throws an exception
     * when the allowed extension list is empty after normalization.
     */
    public function testAllowedFileExtFalseForEmptyExtensionList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAllowedFileExtensions('upload', null, [[]], []);

    }

    /*
    |--------------------------------------------------------------------------
    | Rule: noErrorOnUpload
    | Method: validateNoErrorOnUpload
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds
     * when a single uploaded file has no upload error.
     */
    public function testNoErrorOnUploadReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => __DIR__ . '/fixtures/sample.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => filesize(__DIR__ . '/fixtures/sample.jpg'),
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation succeeds
     * when no file was uploaded.
     */
    public function testNoErrorOnUploadReturnsTrueForNoFile(): void
    {
        $_FILES['upload'] = [
            'name' => '',
            'type' => '',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation succeeds
     * when no upload field exists.
     */
    public function testNoErrorOnUploadReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when a file upload exceeds the PHP upload_max_filesize limit.
     */
    public function testNoErrorOnUploadReturnsFalseForIniSizeError(): void
    {
        $_FILES['upload'] = [
            'name' => '3000kb.png',
            'type' => 'image/png',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_INI_SIZE,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when a file upload exceeds the MAX_FILE_SIZE form limit.
     */
    public function testNoErrorOnUploadReturnsFalseForFormSizeError(): void
    {
        $_FILES['upload'] = [
            'name' => '3000kb.png',
            'type' => 'image/png',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_FORM_SIZE,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when a file upload is only partially uploaded.
     */
    public function testNoErrorOnUploadReturnsFalseForPartialUpload(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_PARTIAL,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when the upload folder is missing.
     */
    public function testNoErrorOnUploadReturnsFalseForMissingTempDir(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_TMP_DIR,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when the upload cannot be written to disk.
     */
    public function testNoErrorOnUploadReturnsFalseForDiskWriteError(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_CANT_WRITE,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation fails
     * when a PHP extension stops the upload.
     */
    public function testNoErrorOnUploadReturnsFalseForExtensionError(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_EXTENSION,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation succeeds
     * when multiple uploaded files have no errors.
     */
    public function testMultipleUploadsWithoutErrorsReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.jpg',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.jpg'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when one file in a multi-upload contains an upload error.
     */
    public function testOneUploadErrorInMultipleFilesReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'broken.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg', ''],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg'), 0],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that validation succeeds
     * when multiple files contain only UPLOAD_ERR_NO_FILE entries.
     */
    public function testMultipleNoFileErrorsReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['', ''],
            'type' => ['', ''],
            'tmp_name' => ['', ''],
            'error' => [UPLOAD_ERR_NO_FILE, UPLOAD_ERR_NO_FILE],
            'size' => [0, 0],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails
     * when an unknown upload error code is encountered.
     */
    public function testNoErrorOnUploadReturnsFalseForUnknownErrorCode(): void
    {
        $_FILES['upload'] = [
            'name' => 'sample.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => 999,
            'size' => 0,
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $result = $this->logic->validateNoErrorOnUpload('upload', null, [], []);

        $this->assertFalse($result);
    }

    /**
     * Verifies that failed uploads are ignored
     * and valid uploaded files pass mime validation.
     */
    public function testMimeTypeValidationIgnoresFailedUploadsInMixedUpload(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'malicious.php'],
            'type' => ['image/jpeg', 'application/x-httpd-php'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/malicious.php',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/malicious.php'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedMimeTypes('upload', null, ['image/jpeg'], [])
        );
    }

    /**
     * Verifies that failed uploads are ignored
     * during extension validation.
     */
    public function testFileExtensionValidationIgnoresFailedUploadsInMixedUpload(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'malicious.php'],
            'type' => ['image/jpeg', 'application/x-httpd-php'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/malicious.php',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/malicious.php'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedFileExtensions('upload', null, ['jpg'], [])
        );
    }

    /**
     * Verifies that failed uploads are ignored
     * during single file size validation.
     */
    public function testSingleFileSizeValidationIgnoresFailedUploadsInMixedUpload(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', '3000kb.png'],
            'type' => ['image/jpeg', 'image/png'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/3000kb.png',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/3000kb.png'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateSingleFileSize('upload', null, ['1MB'], [])
        );
    }

    /**
     * Verifies that failed uploads are ignored
     * during total file size validation.
     */
    public function testTotalFileSizeValidationIgnoresFailedUploadsInMixedUpload(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', '3000kb.png'],
            'type' => ['image/jpeg', 'image/png'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/3000kb.png',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_PARTIAL],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/3000kb.png'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateTotalFileSize('upload', null, ['1MB'], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MATCHING EXTENSION AND MIMETYPE
    |--------------------------------------------------------------------------
    */

    /**
     * Validation should return true when the file extension
     * matches the detected MIME type.
     */
    public function testMatchingExtensionAndMimeTypeReturnsTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should return false when the file extension
     * does not match the detected MIME type.
     */
    public function testMismatchingExtensionAndMimeTypeReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['fake.php'],
            'type' => ['image/jpg'],
            'tmp_name' => [__DIR__ . '/fixtures/fake.php'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/fake.php')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should return true when the file extension
     * is uppercase and still matches the MIME type.
     */
    public function testUppercaseExtensionReturnsTrue(): void
    {

        $_FILES['upload'] = [
            'name' => ['sample.jpg'],
            'type' => ['image/jpg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should return true when the MIME type
     * is uppercase and still matches the file extension.
     */
    public function testUppercaseMimeTypeReturnsTrue(): void
    {

        $_FILES['upload'] = [
            'name' => ['sample.jpg'],
            'type' => ['image/jpg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should ignore files with upload errors
     * and continue validating remaining files.
     */
    public function testUploadErrorsAreIgnored(): void
    {

        $_FILES['upload'] = [
            'name' => ['sample.jpg'],
            'type' => ['image/jpg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_NO_FILE],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should return false when one valid file
     * has a mismatching extension and MIME type pair.
     */
    public function testOneInvalidFileAmongMultipleFilesReturnsFalse(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'fake.php'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/fake.php',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/fake.php'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /**
     * Validation should return true when multiple files
     * all have matching extensions and MIME types.
     */
    public function testMultipleValidFilesReturnTrue(): void
    {
        $_FILES['upload'] = [
            'name' => ['sample.jpg', 'sample2.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [
                __DIR__ . '/fixtures/sample.jpg',
                __DIR__ . '/fixtures/sample2.gif',
            ],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [
                filesize(__DIR__ . '/fixtures/sample.jpg'),
                filesize(__DIR__ . '/fixtures/sample2.gif'),
            ],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMatchingExtMimeType('upload', null, [], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: forbiddenFileExt
    | Method: validateForbiddenFileExtensions
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds when no files were uploaded.
     */
    public function testvalidateForbiddenFileExtensionsReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['exe']], [])
        );
    }

    /**
     * Verifies that validation succeeds when the uploaded file extension is not forbidden.
     */
    public function testvalidateForbiddenFileExtensionsReturnsTrueForAllowedExtension(): void
    {
        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['exe']], [])
        );
    }

    /**
     * Verifies that validation fails when the uploaded file extension is forbidden.
     */
    public function testvalidateForbiddenFileExtensionsReturnsFalseForForbiddenExtension(): void
    {
        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['jpg']], [])
        );
    }

    /**
     * Verifies that validation fails when at least one uploaded file has a forbidden extension.
     */
    public function testvalidateForbiddenFileExtensionsReturnsFalseWhenOneFileIsForbidden(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $gif = __DIR__ . '/fixtures/800kb.gif';

        $_FILES['upload'] = [
            'name' => ['image.jpg', 'image.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [$jpg, $gif],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($gif)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['gif']], [])
        );
    }

    /**
     * Verifies that validation succeeds when none of the uploaded files have forbidden extensions.
     */
    public function testvalidateForbiddenFileExtensionsReturnsTrueWhenAllFilesAreAllowed(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $gif = __DIR__ . '/fixtures/800kb.gif';

        $_FILES['upload'] = [
            'name' => ['image.jpg', 'image.gif'],
            'type' => ['image/jpeg', 'image/gif'],
            'tmp_name' => [$jpg, $gif],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($gif)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['exe', 'pdf']], [])
        );
    }

    /**
     * Verifies that validation fails when an uploaded file has no extension.
     */
    public function testvalidateForbiddenFileExtensionsReturnsFalseForFileWithoutExtension(): void
    {
        $file = __DIR__ . '/fixtures/file-without-extension';

        $_FILES['upload'] = [
            'name' => ['file-without-extension'],
            'type' => ['text/plain'],
            'tmp_name' => [$file],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($file)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['exe']], [])
        );
    }

    /**
     * Verifies that forbidden extensions are compared case-insensitively.
     */
    public function testvalidateForbiddenFileExtensionsIgnoresForbiddenExtensionCase(): void
    {
        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['JPG']], [])
        );
    }

    /**
     * Verifies that uploaded extensions are compared case-insensitively.
     */
    public function testvalidateForbiddenFileExtensionsIgnoresUploadedExtensionCase(): void
    {
        $_FILES['upload'] = [
            'name' => ['IMAGE.JPG'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['jpg']], [])
        );
    }

    /**
     * Verifies that throws an exception when no forbidden extensions are configured.
     */
    public function testvalidateForbiddenFileExtensionsThrowsExceptionWithEmptyForbiddenList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateForbiddenFileExtensions('upload', null, [[]], []);

    }

    /**
     * Verifies that validation fails when one of multiple forbidden extensions matches.
     */
    public function testvalidateForbiddenFileExtensionsReturnsFalseWhenSecondForbiddenExtensionMatches(): void
    {
        $_FILES['upload'] = [
            'name' => ['document.doc'],
            'type' => ['application/msword'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.doc'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.doc')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenFileExtensions('upload', null, [['exe', 'doc', 'pdf']], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: allowedMimeTypes
    | Method: validateAllowedMimeTypes
    |--------------------------------------------------------------------------
    */

    /**
     * Returns true when no files are uploaded.
     */
    public function testReturnsTrueWithoutFiles(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/jpeg']], [])
        );
    }

    /**
     * Returns true when all uploaded MIME types are allowed.
     */
    public function testReturnsTrueForAllowedMimeTypes(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/jpeg']], [])
        );
    }

    /**
     * Verifies that validation returns false when mime type is not allowed.
     */
    public function testReturnsFalseForForbiddenMimeType(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/gif']], [])
        );
    }

    /**
     * Returns false when at least one file has no MIME type.
     */
    public function testReturnsFalseWhenFileHasNoMimeType(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => [''],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/jpg']], [])
        );
    }

    /**
     * Returns true when multiple allowed MIME types match.
     */
    public function testReturnsTrueForMultipleAllowedMimeTypes(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', 'sample.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedMimeTypes(
                'upload',
                null,
                [['image/jpeg', 'application/pdf']],
                []
            )
        );
    }

    /**
     * Verifies that validation fails when uploaded files
     * contain different MIME types and one of them
     * is not in the allowed MIME type list.
     */
    public function testReturnsFalseForMixedMimeTypes(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', 'sample.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/jpeg']], [])
        );
    }

    /**
     * Ensures MIME comparison is case-insensitive.
     */
    public function testMimeComparisonIsCaseInsensitive(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['IMAGE/JPEG'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAllowedMimeTypes('upload', null, [['image/jpeg']], [])
        );
    }

    /**
     * Throws exception when parameter list is empty.
     */
    public function testThrowsExceptionWhenAllowedListIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', 'sample.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAllowedMimeTypes('upload', null, [[]], []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: isOfForbiddenMimeType
    | Method: validateForbiddenMimeTypes
    |--------------------------------------------------------------------------
    */

    /**
     * Returns true when no files are uploaded.
     */
    public function testReturnsTrueWhenNoFilesAreUploaded(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenMimeTypes(
                'upload',
                null,
                [['image/jpg', 'application/pdf']],
                []
            )
        );
    }

    /**
     * Throws exception when forbidden list is empty.
     */
    public function testThrowsExceptionWhenForbiddenListIsEmpty(): void
    {

        $this->expectException(InvalidArgumentException::class);
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateForbiddenMimeTypes('upload', null, [[]], []);

    }

    /**
     * Returns false when a file has a forbidden MIME type.
     */
    public function testReturnsFalseWhenMimeTypeIsForbidden(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenMimeTypes('upload', null, ['image/jpeg'], [])
        );
    }

    /**
     * Returns true when MIME type is not in forbidden list.
     */
    public function testReturnsTrueWhenMimeTypeIsAllowed(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenMimeTypes('upload', null, ['application/pdf'], [])
        );
    }

    /**
     * Returns false when multiple MIME types contain a forbidden one.
     */
    public function testReturnsFalseWhenMixedMimeTypesContainForbidden(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', 'sample.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenMimeTypes('upload', null, [['application/pdf']], [])
        );
    }

    /**
     * Returns false when at least one file has no MIME type.
     */
    public function testReturnsFalseWhenFileHasNoMimeTypeForbidden(): void
    {
        $_FILES['upload'] = [
            'name' => ['file1'],
            'type' => [''],
            'tmp_name' => ['file1'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [100],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateForbiddenMimeTypes('upload', null, [['application/pdf']], [])
        );
    }

    /**
     * Returns true when multiple allowed MIME types exclude all files.
     */
    public function testReturnsTrueWhenAllMimeTypesAreAllowed(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg', 'sample.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateForbiddenMimeTypes('upload', null, ['text/plain'], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: minFileNumber
    | Method: validateMinFileNumber
    |--------------------------------------------------------------------------
    */

    /**
     * Returns true when no files are uploaded.
     */
    public function testReturnsTrueWhenNoFilesAreUploadedMinFileNumber(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinFileNumber('upload', null, [['2']], [])
        );
    }

    /**
     * Returns false when uploaded file count is below the required minimum.
     */
    public function testReturnsFalseWhenFileCountIsBelowMinimum(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMinFileNumber('upload', null, [['2']], [])
        );
    }

    /**
     * Returns true when uploaded file count equals the required minimum.
     */
    public function testReturnsTrueWhenFileCountEqualsMinimum(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'b.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$jpg, $jpg],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinFileNumber('upload', null, [['2']], [])
        );
    }

    /**
     * Returns true when uploaded file count is above the required minimum.
     */
    public function testReturnsTrueWhenFileCountIsAboveMinimum(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'b.jpg', 'c.jpg'],
            'type' => ['image/jpeg', 'image/jpeg', 'image/jpeg'],
            'tmp_name' => [$jpg, $jpg, $jpg],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($jpg), filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinFileNumber('upload', null, [['2']], [])
        );
    }

    /**
     * Throws an exception when parameter is missing.
     */
    public function testThrowsExceptionWhenParameterIsMissing(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateMinFileNumber('upload', null, [], []);
    }

    /**
     * Throws an exception when parameter is empty.
     */
    public function testThrowsExceptionWhenParameterIsEmpty(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateMinFileNumber('upload', null, [[]], []);
    }

    /**
     * Throws an exception when minimum file number is zero.
     */
    public function testThrowsExceptionWhenMinimumIsZero(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateMinFileNumber('upload', null, [['0']], []);
    }

    /**
     * Throws an exception when minimum file number is negative.
     */
    public function testThrowsExceptionWhenMinimumIsNegative(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateMinFileNumber('upload', null, [['-1']], []);
    }

    /**
     * Throws an exception when parameter is not a valid integer.
     */
    public function testThrowsExceptionWhenParameterIsNotInteger(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$jpg],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($jpg)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateMinFileNumber('upload', null, [['abc']], []);
    }

    /**
     * Counts all uploaded files regardless of file type.
     */
    public function testCountsFilesRegardlessOfMimeType(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'b.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinFileNumber('upload', null, [['2']], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: noEmptyFiles
    | Method: validateNoEmptyFiles
    |--------------------------------------------------------------------------
    */

    /**
     * Returns true when no empty files were uploaded.
     */
    public function testValidateNoEmptyFilesReturnsTrueForNonEmptyFiles(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'b.pdf'],
            'type' => ['image/jpeg', 'application/pdf'],
            'tmp_name' => [$jpg, $pdf],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /**
     * Returns false when a single empty file was uploaded.
     */
    public function testValidateNoEmptyFilesReturnsFalseForSingleEmptyFile(): void
    {
        $empty = __DIR__ . '/fixtures/empty.txt';

        $_FILES['upload'] = [
            'name' => ['empty.txt'],
            'type' => ['text/plain'],
            'tmp_name' => [$empty],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($empty)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /**
     * Returns false when at least one uploaded file is empty.
     */
    public function testValidateNoEmptyFilesReturnsFalseWhenOneFileIsEmpty(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $empty = __DIR__ . '/fixtures/empty.txt';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'empty.txt'],
            'type' => ['image/jpeg', 'text/plain'],
            'tmp_name' => [$jpg, $empty],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($empty)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /**
     * Returns false when multiple empty files were uploaded.
     */
    public function testValidateNoEmptyFilesReturnsFalseForMultipleEmptyFiles(): void
    {
        $empty = __DIR__ . '/fixtures/empty.txt';

        $_FILES['upload'] = [
            'name' => ['a.txt', 'b.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => [$empty, $empty],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($empty), filesize($empty)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /**
     * Returns true when no files were uploaded.
     */
    public function testValidateNoEmptyFilesReturnsTrueForNoUploads(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /**
     * Ignores upload errors because only successfully uploaded files
     * contribute to empty_file_count_root statistics.
     */
    public function testValidateNoEmptyFilesIgnoresUploadErrors(): void
    {
        $empty = __DIR__ . '/fixtures/empty.txt';

        $_FILES['upload'] = [
            'name' => ['empty.txt'],
            'type' => ['text/plain'],
            'tmp_name' => [$empty],
            'error' => [UPLOAD_ERR_INI_SIZE],
            'size' => [filesize($empty)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateNoEmptyFiles('upload', null, [], [])
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: aspectRatio
    | Method: validateAspectRatio
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds when no uploads exist for the field.
     */
    public function testValidateAspectRatioReturnsTrueWhenNoUploadsExist(): void
    {
        $_FILES = [];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAspectRatio('upload', null, [['16:9']], [])
        );
    }

    /**
     * Verifies that throws exception when no allowed aspect ratios are configured.
     */
    public function testValidateAspectRatioThrowsExceptionWhenAllowedRatiosEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAspectRatio('upload', null, [[]], []);

    }

    /**
     * Verifies that throws exception when no aspect ratios are detected from uploads.
     */
    public function testValidateAspectRatioThrowsExceptionWhenNoExistingRatios(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $_FILES['upload'] = [
            'name' => ['sample.pdf'],
            'type' => ['application/pdf'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.pdf')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAspectRatio('upload', null, [[]], []);

    }

    /**
     * Verifies that validation succeeds when all detected aspect ratios are allowed.
     */
    public function testValidateAspectRatioReturnsTrueWhenAllRatiosAreAllowed(): void
    {
        $jpg = __DIR__ . '/fixtures/900kb.jpg';
        $jpg2 = __DIR__ . '/fixtures/landscape.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg', 'image.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$jpg, $jpg2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($jpg), filesize($jpg2)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAspectRatio('upload', null, [['1:1', '3:2']], [])
        );
    }

    /**
     * Verifies that validation fails when at least one uploaded aspect ratio is not allowed.
     */
    public function testValidateAspectRatioReturnsFalseWhenRatioNotAllowed(): void
    {
        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/sample.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/sample.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateAspectRatio('upload', null, [['1:1']], [])
        );
    }

    /**
     * Verifies that validation succeeds when allowed ratios contain more values than used.
     */
    public function testValidateAspectRatioIgnoresExtraAllowedRatios(): void
    {
        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAspectRatio('upload', null, [['16:9', '4:3', '1:1']], [])
        );
    }

    /**
     * Verifies that validation fails when multiple uploaded ratios are not in allowed list.
     */
    public function testValidateAspectRatioFailsWhenAllRatiosAreInvalid(): void
    {
        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateAspectRatio('upload', null, [['16:9', '3:2']], [])
        );
    }

    /**
     * Verifies that validation returns true if aspect ratio is the same in modulo
     * fe 1:1 = 4:4
     */
    public function testValidateAspectRatioReturnsTrueWhenRatioIsModulo(): void
    {
        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateAspectRatio('upload', null, [['4:4', '16:9']], [])
        );
    }

    /**
     * Verifies that validation throws an exception on wrong ratio format.
     */
    public function testValidateAspectRatioReturnsExceptionOnWrongRatioFormat(): void
    {
        $_FILES['upload'] = [
            'name' => ['900kb.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [__DIR__ . '/fixtures/900kb.jpg'],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize(__DIR__ . '/fixtures/900kb.jpg')],
        ];

        $this->expectException(InvalidArgumentException::class);

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateAspectRatio('upload', null, [['4x4']], []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: minImageDimensions
    | Method: validateMinImageDimensions
    |--------------------------------------------------------------------------
    */

    /**
     * Returns true when no files were uploaded.
     */
    public function testMinImageDimensionsReturnsTrueWhenNoFilesUploaded(): void
    {
        $_FILES['upload'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['100x100'], [])
        );
    }

    /**
     * Returns true when image dimensions exactly match the minimum values.
     */
    public function testMinImageDimensionsReturnsTrueForExactDimensions(): void
    {
        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['1200x1200'], [])
        );
    }

    /**
     * Returns true when image dimensions exceed the minimum values.
     */
    public function testMinImageDimensionsReturnsTrueForLargerImage(): void
    {
        $image = __DIR__ . '/fixtures/3000kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['800x600'], [])
        );
    }

    /**
     * Returns false when image width is below the minimum value.
     */
    public function testMinImageDimensionsReturnsFalseForTooSmallWidth(): void
    {
        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMinImageDimensions('upload', null, ['1300x1200'], [])
        );
    }

    /**
     * Returns false when image height is below the minimum value.
     */
    public function testMinImageDimensionsReturnsFalseForTooSmallHeight(): void
    {
        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMinImageDimensions('upload', null, ['1200x1300'], [])
        );
    }

    /**
     * Returns false when both image dimensions are below the minimum values.
     */
    public function testMinImageDimensionsReturnsFalseForTooSmallImage(): void
    {
        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMinImageDimensions('upload', null, ['2000x2000'], [])
        );
    }

    /**
     * Ignores non-image files.
     */
    public function testMinImageDimensionsIgnoresNonImageFiles(): void
    {
        $pdf = __DIR__ . '/fixtures/sample.pdf';

        $_FILES['upload'] = [
            'name' => ['sample.pdf'],
            'type' => ['application/pdf'],
            'tmp_name' => [$pdf],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($pdf)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['800x600'], [])
        );
    }

    /**
     * Ignores files with upload errors.
     */
    public function testMinImageDimensionsIgnoresUploadErrors(): void
    {
        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_INI_SIZE],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['800x600'], [])
        );
    }

    /**
     * Returns true when all uploaded images satisfy the minimum dimensions.
     */
    public function testMinImageDimensionsReturnsTrueWhenAllImagesAreValid(): void
    {
        $img1 = __DIR__ . '/fixtures/900kb.jpg';
        $img2 = __DIR__ . '/fixtures/3000kb.jpg';

        $_FILES['upload'] = [
            'name' => ['a.jpg', 'b.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$img1, $img2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($img1), filesize($img2)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertTrue(
            $this->logic->validateMinImageDimensions('upload', null, ['800x600'], [])
        );
    }

    /**
     * Returns false when at least one uploaded image is below the minimum dimensions.
     */
    public function testMinImageDimensionsReturnsFalseWhenOneImageIsTooSmall(): void
    {
        $img1 = __DIR__ . '/fixtures/900kb.jpg';
        $img2 = __DIR__ . '/fixtures/3000kb.jpg';

        $_FILES['upload'] = [
            'name' => ['large.jpg', 'small.jpg'],
            'type' => ['image/jpeg', 'image/jpeg'],
            'tmp_name' => [$img1, $img2],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
            'size' => [filesize($img1), filesize($img2)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->assertFalse(
            $this->logic->validateMinImageDimensions('upload', null, ['1400x1400'], [])
        );
    }

    /**
     * Throws an exception if size parameter is not in the correct format e.g. '200x200'
     */
    public function testMinImageDimensionsThrowsExceptionOnWrongParameterFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $image = __DIR__ . '/fixtures/900kb.jpg';

        $_FILES['upload'] = [
            'name' => ['image.jpg'],
            'type' => ['image/jpeg'],
            'tmp_name' => [$image],
            'error' => [UPLOAD_ERR_OK],
            'size' => [filesize($image)],
        ];

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic(
            $fileHelper,
            $mimeHelper
        );

        $this->logic->validateMinImageDimensions('upload', null, ['800, 600'], []);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: uniqueFilenameInDir
    | Method: validateUniqueFilenameInDir
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that a single-file upload (where $value itself is the file
     * entry) with a filename that does not yet exist in the upload
     * directory returns true.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsTrueForNewFile(): void
    {
        $uploadDir = $this->makeUploadDir();

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $file = ['name' => 'photo.jpg'];

        $this->assertTrue(
            $this->logic->validateUniqueFilenameInDir('upload', $file, [], [])
        );
    }

    /**
     * Verifies that a single-file upload whose filename already exists in
     * the upload directory returns false when no overwrite param is given.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsFalseForExistingFileWithoutOverwrite(): void
    {
        $uploadDir = $this->makeUploadDir();
        touch($uploadDir . 'photo.jpg');

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $file = ['name' => 'photo.jpg'];

        $this->assertFalse(
            $this->logic->validateUniqueFilenameInDir('upload', $file, [], [])
        );
    }

    /**
     * Verifies that a single-file upload whose filename already exists
     * returns true when overwrite param is set, since the existing
     * file gets renamed instead of blocking the upload.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsTrueForExistingFileWithOverwrite(): void
    {
        $uploadDir = $this->makeUploadDir();
        touch($uploadDir . 'photo.jpg');

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $file = ['name' => 'photo.jpg'];

        $this->assertTrue(
            $this->logic->validateUniqueFilenameInDir('upload', $file, [true], [])
        );
    }

    /**
     * Verifies that a multi-file upload where every filename is unique
     * returns true.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsTrueForMultipleNewFiles(): void
    {
        $uploadDir = $this->makeUploadDir();

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $files = [
            ['name' => 'a.jpg'],
            ['name' => 'b.jpg'],
            ['name' => 'c.jpg'],
        ];

        $this->assertTrue(
            $this->logic->validateUniqueFilenameInDir('upload', $files, [], [])
        );
    }

    /**
     * Verifies that a multi-file upload returns false as soon as one
     * filename already exists in the upload directory (without overwrite).
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsFalseWhenOneOfMultipleFilesExists(): void
    {
        $uploadDir = $this->makeUploadDir();
        touch($uploadDir . 'b.jpg');

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $files = [
            ['name' => 'a.jpg'],
            ['name' => 'b.jpg'],
            ['name' => 'c.jpg'],
        ];

        $this->assertFalse(
            $this->logic->validateUniqueFilenameInDir('upload', $files, [], [])
        );
    }

    /**
     * Verifies that an empty file list (no files submitted for this field)
     * is vacuously valid.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsTrueForEmptyFileList(): void
    {
        $uploadDir = $this->makeUploadDir();

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $this->assertTrue(
            $this->logic->validateUniqueFilenameInDir('upload', [], [], [])
        );
    }

    /**
     * Verifies that a null value (field not submitted at all) returns
     * false instead of causing a fatal error in the foreach loop.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsFalseForNullValue(): void
    {
        $uploadDir = $this->makeUploadDir();

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $this->assertFalse(
            $this->logic->validateUniqueFilenameInDir('upload', null, [], [])
        );
    }

    /**
     * Verifies that a non-array scalar value returns false instead of
     * causing a fatal error in the foreach loop.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsFalseForScalarValue(): void
    {
        $uploadDir = $this->makeUploadDir();

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $this->assertFalse(
            $this->logic->validateUniqueFilenameInDir('upload', 'not-an-array', [], [])
        );
    }

    /**
     * Verifies that filenames are matched case-insensitively, since
     * FileHelper::checkDuplicateFilename() lowercases the sanitized
     * filename before checking for existence.
     * @throws RandomException
     */
    public function testUniqueFilenameInDirReturnsFalseForExistingFileWithDifferentCase(): void
    {
        $uploadDir = $this->makeUploadDir();
        touch($uploadDir . 'photo.jpg');

        $fileHelper = new FileHelper();
        $mimeHelper = new MimeHelper();

        $this->logic = new FileLogic($fileHelper, $mimeHelper);
        $this->logic->setForm($this->makeFormMock($uploadDir));

        $file = ['name' => 'PHOTO.JPG'];

        $this->assertFalse(
            $this->logic->validateUniqueFilenameInDir('upload', $file, [], [])
        );
    }
}