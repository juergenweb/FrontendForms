<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FileUploadHandler;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * Unit tests for FileUploadHandler::reArrayFiles().
 *
 * reArrayFiles() rearranges a multi-file $_FILES sub-array (one array per
 * property: name[], type[], tmp_name[], error[], size[]) into one array per
 * uploaded file. It is pure array logic with no ProcessWire dependency, so
 * a mock Form (never actually invoked by this method) is enough to build a
 * FileUploadHandler instance.
 *
 * All expected outputs below were confirmed by running the actual algorithm
 * standalone with the exact same inputs before writing the assertions.
 */
final class FileUploadHandlerTest extends TestCase
{
    private function createHandler(): FileUploadHandler
    {
        return new FileUploadHandler($this->createMock(Form::class));
    }

    /**
     * 1) A typical multi-file selection (two files) is rearranged into one
     * array per file, each keyed the same way as the original $_FILES entry.
     */
    public function testRearrangesTwoFilesIntoOneArrayPerFile(): void
    {
        $handler = $this->createHandler();

        $filePost = [
            'name' => ['a.txt', 'b.txt'],
            'type' => ['text/plain', 'text/plain'],
            'tmp_name' => ['/tmp/php1', '/tmp/php2'],
            'error' => [0, 0],
            'size' => [100, 200],
        ];

        $this->assertSame(
            [
                0 => ['name' => 'a.txt', 'type' => 'text/plain', 'tmp_name' => '/tmp/php1', 'error' => 0, 'size' => 100],
                1 => ['name' => 'b.txt', 'type' => 'text/plain', 'tmp_name' => '/tmp/php2', 'error' => 0, 'size' => 200],
            ],
            $handler->reArrayFiles($filePost)
        );
    }

    /**
     * 2) When "error" is the scalar UPLOAD_ERR_NO_FILE (4) - which PHP sets
     * when a multi-file input was submitted with nothing selected - the
     * method returns an empty array without touching the other keys.
     */
    public function testScalarErrorFourReturnsEmptyArray(): void
    {
        $handler = $this->createHandler();

        $filePost = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => 4,
            'size' => [],
        ];

        $this->assertSame([], $handler->reArrayFiles($filePost));
    }

    /**
     * 3) A mix of one successful and one failed upload slot is NOT filtered
     * out here - both slots are rearranged as-is, including the failed
     * slot's error code. Filtering by error code is the caller's
     * responsibility (see FileUploadHandler::storeUploadedFiles(), which
     * checks $file['error'] == 0 per entry after calling this method).
     */
    public function testMixedSuccessAndFailureSlotsAreBothIncluded(): void
    {
        $handler = $this->createHandler();

        $filePost = [
            'name' => ['a.txt', ''],
            'type' => ['text/plain', ''],
            'tmp_name' => ['/tmp/php1', ''],
            'error' => [0, 4],
            'size' => [100, 0],
        ];

        $result = $handler->reArrayFiles($filePost);

        $this->assertCount(2, $result);
        $this->assertSame(0, $result[0]['error']);
        $this->assertSame(4, $result[1]['error']);
    }

    /**
     * 4) An array-shaped but empty selection (error is an empty array, not
     * the scalar 4) results in zero iterations and an empty result - no
     * warning, no exception.
     */
    public function testEmptyArrayShapedSelectionReturnsEmptyArray(): void
    {
        $handler = $this->createHandler();

        $filePost = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => [],
        ];

        $this->assertSame([], $handler->reArrayFiles($filePost));
    }

    /**
     * 5) PRECONDITION DOCUMENTATION: reArrayFiles() must only be called with
     * a multi-file $_FILES sub-array. Passing a single-file-shaped sub-array
     * (where "name" etc. are scalars, not arrays) throws a TypeError from
     * count(), since count() no longer accepts scalars as of PHP 8. This is
     * not exercised as a "fix target" here - it documents a real constraint
     * callers must respect (the caller must check $element->getMultiple()
     * before calling this method, which the current code already does).
     */
    public function testSingleFileShapedInputThrowsTypeError(): void
    {
        $handler = $this->createHandler();

        $filePost = [
            'name' => 'single.txt',
            'type' => 'text/plain',
            'tmp_name' => '/tmp/php3',
            'error' => 0,
            'size' => 50,
        ];

        $this->expectException(TypeError::class);

        $handler->reArrayFiles($filePost);
    }
}
