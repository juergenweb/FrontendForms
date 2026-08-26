<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputFile.
 *
 * Covers normalizeSizeUnit() (a pure static string-processing method, no
 * ProcessWire dependency), construction, setMultiple()/getMultiple(),
 * showTotalFileSize()/getShowTotalFileSize(), and the core rendering
 * behaviour of ___renderInputFile(). The framework-specific renderUikit3()/
 * renderBulma1() branches are not covered here, since which one runs
 * depends on the live test environment's configured framework (same
 * reasoning as the renderer tests elsewhere in this session).
 *
 * All expected outputs for normalizeSizeUnit() were confirmed by running
 * the actual algorithm standalone with the exact same inputs before
 * writing the assertions.
 */
final class InputFileTest extends TestCase
{
    /**
     * 1) A number with a single-letter unit is normalized to "number UNITB"
     * (space-separated, unit uppercased, "B" appended).
     */
    public function testNormalizesSingleLetterUnit(): void
    {
        $this->assertSame('8 MB', InputFile::normalizeSizeUnit('8M'));
        $this->assertSame('2 GB', InputFile::normalizeSizeUnit('2G'));
        $this->assertSame('512 KB', InputFile::normalizeSizeUnit('512K'));
    }

    /**
     * 2) A bare number with no unit at all is treated as bytes and gets "B"
     * appended directly, WITHOUT a space - unlike the single-letter-unit
     * case above. This asymmetry is easy to miss, so it's pinned down here.
     */
    public function testBareNumberWithoutUnitHasNoSpaceBeforeB(): void
    {
        $this->assertSame('100B', InputFile::normalizeSizeUnit('100'));
        $this->assertSame('8B', InputFile::normalizeSizeUnit('8'));
    }

    /**
     * 3) Decimal numbers are preserved as-is.
     */
    public function testPreservesDecimalNumbers(): void
    {
        $this->assertSame('1.5 MB', InputFile::normalizeSizeUnit('1.5M'));
    }

    /**
     * 4) The unit is matched case-insensitively and always normalized to
     * upper case in the output.
     */
    public function testUnitIsCaseInsensitive(): void
    {
        $this->assertSame('8 MB', InputFile::normalizeSizeUnit('8m'));
    }

    /**
     * 5) Leading/trailing whitespace around the whole value is trimmed.
     */
    public function testTrimsWhitespace(): void
    {
        $this->assertSame('8 MB', InputFile::normalizeSizeUnit(' 8M '));
    }

    /**
     * 6) A value that already has an explicit "B" suffix (e.g. "8MB") is
     * accepted and normalized the same way as "8M".
     */
    public function testAcceptsValueWithExplicitBSuffix(): void
    {
        $this->assertSame('8 MB', InputFile::normalizeSizeUnit('8MB'));
    }

    /**
     * 7) Values that don't match the expected number(+unit) format throw an
     * InvalidArgumentException - including non-numeric strings, a unit
     * before the number, and negative numbers (not supported by the regex).
     */
    public function testInvalidValuesThrowInvalidArgumentException(): void
    {
        foreach (['abc', 'M8', '-5M', ''] as $invalid) {
            try {
                InputFile::normalizeSizeUnit($invalid);
                $this->fail('Expected InvalidArgumentException for value "' . $invalid . '"');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('Invalid size for input value', $e->getMessage());
            }
        }
    }

    // --- construction ---

    /**
     * 8) The field's type is "file".
     */
    public function testConstructorSetsFileType(): void
    {
        $field = new InputFile('upload');

        $this->assertSame('file', $field->getAttribute('type'));
    }

    /**
     * 9) Multiple file selection is enabled by default.
     */
    public function testConstructorEnablesMultipleByDefault(): void
    {
        $field = new InputFile('upload');

        $this->assertTrue($field->getMultiple());
        $this->assertSame('multiple', $field->getAttribute('multiple'));
    }

    /**
     * 10) A non-empty, translated label is set on construction.
     */
    public function testConstructorSetsNonEmptyLabel(): void
    {
        $field = new InputFile('upload');

        $this->assertNotSame('', $field->getLabel()->getText());
    }

    // --- setMultiple() / getMultiple() ---

    /**
     * 11) Disabling multiple selection removes the "multiple" attribute
     * and is reflected by getMultiple().
     */
    public function testSetMultipleFalseRemovesAttribute(): void
    {
        $field = new InputFile('upload');

        $field->setMultiple(false);

        $this->assertFalse($field->getMultiple());
        $this->assertNull($field->getAttribute('multiple'));
    }

    /**
     * 12) Re-enabling multiple selection restores the attribute.
     */
    public function testSetMultipleTrueRestoresAttribute(): void
    {
        $field = new InputFile('upload');
        $field->setMultiple(false);

        $field->setMultiple(true);

        $this->assertTrue($field->getMultiple());
        $this->assertSame('multiple', $field->getAttribute('multiple'));
    }

    /**
     * 13) setMultiple() returns $this, supporting fluent chaining.
     */
    public function testSetMultipleReturnsSelf(): void
    {
        $field = new InputFile('upload');

        $this->assertSame($field, $field->setMultiple(false));
    }

    // --- showTotalFileSize() / getShowTotalFileSize() ---

    /**
     * 14) The total file size display is disabled by default.
     */
    public function testShowTotalFileSizeIsFalseByDefault(): void
    {
        $field = new InputFile('upload');

        $this->assertFalse($field->getShowTotalFileSize());
    }

    /**
     * 15) showTotalFileSize() enables the total file size display.
     */
    public function testShowTotalFileSizeEnablesFlag(): void
    {
        $field = new InputFile('upload');

        $field->showTotalFileSize();

        $this->assertTrue($field->getShowTotalFileSize());
    }

    /**
     * 16) showTotalFileSize() returns $this, supporting fluent chaining.
     */
    public function testShowTotalFileSizeReturnsSelf(): void
    {
        $field = new InputFile('upload');

        $this->assertSame($field, $field->showTotalFileSize());
    }

    // --- ___renderInputFile() ---

    /**
     * 17) The rendered field's id gets a "-fileupload" suffix.
     */
    public function testRenderAppendsFileuploadSuffixToId(): void
    {
        $field = new InputFile('upload');

        $out = $field->renderInputFile();

        $this->assertStringContainsString('id="upload-fileupload"', $out);
    }

    /**
     * 18) With multiple selection enabled, the field's "name" attribute
     * gets a "[]" array suffix in the rendered output.
     */
    public function testRenderAddsArraySuffixToNameWhenMultiple(): void
    {
        $field = new InputFile('upload');

        $out = $field->renderInputFile();

        $this->assertStringContainsString('name="upload[]"', $out);
    }

    /**
     * 19) With multiple selection disabled, the "name" attribute has no
     * array suffix.
     */
    public function testRenderDoesNotAddArraySuffixToNameWhenNotMultiple(): void
    {
        $field = new InputFile('upload');
        $field->setMultiple(false);

        $out = $field->renderInputFile();

        $this->assertStringContainsString('name="upload"', $out);
        $this->assertStringNotContainsString('name="upload[]"', $out);
    }

    /**
     * 20) By default (showClearLink is true), the rendered output includes
     * the files-list clear-link markup.
     */
    public function testRenderIncludesFilesAreaByDefault(): void
    {
        $field = new InputFile('upload');

        $out = $field->renderInputFile();

        $this->assertStringContainsString('files-area', $out);
    }

    /**
     * 21) With showClearLink disabled, the files-list markup is omitted.
     */
    public function testRenderOmitsFilesAreaWhenDisabled(): void
    {
        $field = new InputFile('upload');
        $field->showClearLink(false);

        $out = $field->renderInputFile();

        $this->assertStringNotContainsString('files-area', $out);
    }

    /**
     * 22) With multiple selection and showTotalFileSize both enabled, the
     * total-file-size markup appears in the rendered output.
     */
    public function testRenderIncludesTotalFileSizeWhenEnabledAndMultiple(): void
    {
        $field = new InputFile('upload');
        $field->showTotalFileSize();

        $out = $field->renderInputFile();

        $this->assertStringContainsString('ff-total-file-size', $out);
    }

    /**
     * 23) Without showTotalFileSize enabled, the total-file-size markup is
     * omitted, even with multiple selection active.
     */
    public function testRenderOmitsTotalFileSizeByDefault(): void
    {
        $field = new InputFile('upload');

        $out = $field->renderInputFile();

        $this->assertStringNotContainsString('ff-total-file-size', $out);
    }
}