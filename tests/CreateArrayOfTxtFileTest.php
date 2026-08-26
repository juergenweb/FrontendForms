<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;

/**
 * Unit tests for FrontendForms::createArrayOfTxtFile(), the (now public
 * and static) helper used to read a plain-text file (e.g. passwords.txt,
 * stopwords.txt) into an array of lines.
 */
final class CreateArrayOfTxtFileTest extends TestCase
{
    /** @var string[] Temp files created during the test, removed in tearDown(). */
    private array $tempFiles = [];

    private function makeTempFile(string $content): string
    {
        $path = sys_get_temp_dir() . '/frontendforms-txtfile-' . uniqid() . '.txt';
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    /**
     * 1) A normal Unix-style (\n) text file is read into a clean array of
     * lines, with no trailing empty entry despite the file ending in a
     * newline (the previously fixed bug).
     */
    public function testReadsUnixStyleFileWithoutTrailingEmptyEntry(): void
    {
        $path = $this->makeTempFile("password123\nqwerty\nadmin\n");

        $result = FrontendForms::createArrayOfTxtFile($path);

        $this->assertSame(['password123', 'qwerty', 'admin'], $result);
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: a Windows CRLF-saved file does
     * not leave a stray \r attached to each entry.
     */
    public function testReadsWindowsCrlfFileWithoutStrayCarriageReturns(): void
    {
        $path = $this->makeTempFile("password123\r\nqwerty\r\nadmin\r\n");

        $result = FrontendForms::createArrayOfTxtFile($path);

        $this->assertSame(['password123', 'qwerty', 'admin'], $result);
        foreach ($result as $entry) {
            $this->assertStringNotContainsString("\r", $entry);
        }
    }

    /**
     * 3) A non-existent file returns an empty array rather than throwing
     * or emitting an uncaught warning.
     */
    public function testNonExistentFileReturnsEmptyArray(): void
    {
        $path = sys_get_temp_dir() . '/frontendforms-does-not-exist-' . uniqid() . '.txt';

        $this->assertSame([], FrontendForms::createArrayOfTxtFile($path));
    }

    /**
     * 4) An empty file returns an empty array.
     */
    public function testEmptyFileReturnsEmptyArray(): void
    {
        $path = $this->makeTempFile('');

        $this->assertSame([], FrontendForms::createArrayOfTxtFile($path));
    }

    /**
     * 5) Blank lines in the middle of the file (not just a trailing one)
     * are also skipped, via FILE_SKIP_EMPTY_LINES.
     */
    public function testSkipsBlankLinesInTheMiddleOfTheFile(): void
    {
        $path = $this->makeTempFile("password123\n\n\nqwerty\n");

        $result = FrontendForms::createArrayOfTxtFile($path);

        $this->assertSame(['password123', 'qwerty'], $result);
    }
}
