<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FileUploadSingle;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FileUploadSingle.
 */
final class FileUploadSingleTest extends TestCase
{
    /**
     * 1) The field's type defaults to "file" (inherited from InputFile).
     */
    public function testConstructorSetsFileType(): void
    {
        $field = new FileUploadSingle('upload');

        $this->assertSame('file', $field->getAttribute('type'));
    }

    /**
     * 2) Multiple-file selection is explicitly disabled (the "multiple"
     * attribute is removed entirely, per setMultiple(false)'s behaviour).
     */
    public function testConstructorDisablesMultiple(): void
    {
        $field = new FileUploadSingle('upload');

        $this->assertNull($field->getAttribute('multiple'));
    }

    /**
     * 3) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new FileUploadSingle('upload');

        $this->assertSame('Upload single file', $field->getLabel()->getText());
    }

    /**
     * 4) Rendering produces output containing the file input with the
     * correct type and name. The full output also includes additional
     * framework markup (a trigger button, file-list container), so this
     * checks for the input's presence rather than an exact starting tag.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new FileUploadSingle('upload');

        $out = $field->renderFileUploadSingle();

        $this->assertStringContainsString('type="file"', $out);
        $this->assertStringContainsString('name="upload"', $out);
    }
}