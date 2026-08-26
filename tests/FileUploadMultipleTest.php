<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FileUploadMultiple;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FileUploadMultiple.
 */
final class FileUploadMultipleTest extends TestCase
{
    /**
     * 1) The field's type defaults to "file" (inherited from InputFile).
     */
    public function testConstructorSetsFileType(): void
    {
        $field = new FileUploadMultiple('uploads');

        $this->assertSame('file', $field->getAttribute('type'));
    }

    /**
     * 2) Multiple-file selection stays enabled, per InputFile's own
     * default (setMultiple() defaults to true in its constructor, and
     * FileUploadMultiple does not override it).
     */
    public function testConstructorKeepsMultipleEnabled(): void
    {
        $field = new FileUploadMultiple('uploads');

        $this->assertTrue($field->getMultiple());
    }

    /**
     * 3) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new FileUploadMultiple('uploads');

        $this->assertSame('Upload multiple files', $field->getLabel()->getText());
    }

    /**
     * 4) Rendering produces output containing the file input with the
     * correct type, and the array-style name (multiple-file uploads
     * submit as an array). The full output also includes additional
     * framework markup (a trigger button, file-list container), so this
     * checks for the input's presence rather than an exact starting tag.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new FileUploadMultiple('uploads');

        $out = $field->renderFileUploadMultiple();

        $this->assertStringContainsString('type="file"', $out);
        $this->assertStringContainsString('name="uploads[]"', $out);
        $this->assertStringContainsString('multiple', $out);
    }
}