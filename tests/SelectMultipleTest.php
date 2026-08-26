<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SelectMultiple;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SelectMultiple.
 */
final class SelectMultipleTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The "multiple" attribute is set on construction.
     */
    public function testConstructorSetsMultipleAttribute(): void
    {
        $select = new SelectMultiple('mymultiselect');

        $this->assertSame('multiple', $select->getAttribute('multiple'));
    }

    /**
     * 2) The default "text" sanitizer is removed and replaced with
     * "arrayVal", since a multiple-select submits an array of values, not
     * a single string.
     */
    public function testConstructorSwitchesToArrayValSanitizer(): void
    {
        $select = new SelectMultiple('mymultiselect');

        $this->assertFalse($select->hasSanitizer('text'));
        $this->assertTrue($select->hasSanitizer('arrayVal'));
    }

    // --- ___renderSelectMultiple() / convertNameAttribute() ---

    /**
     * 3) Rendering appends "[]" to the "name" attribute so PHP collects all
     * selected values into an array on submission.
     */
    public function testRenderAddsBracketsToNameAttribute(): void
    {
        $select = new SelectMultiple('mymultiselect');
        $this->assertSame('mymultiselect', $select->getAttribute('name'));

        $select->renderSelectMultiple();

        $this->assertSame('mymultiselect[]', $select->getAttribute('name'));
    }

    /**
     * 4) If the "name" attribute already ends with "[]" (e.g. rendered
     * twice, or set manually beforehand), rendering does not duplicate the
     * brackets.
     */
    public function testRenderDoesNotDuplicateBracketsIfAlreadyPresent(): void
    {
        $select = new SelectMultiple('mymultiselect');
        $select->setAttribute('name', 'mymultiselect[]');

        $select->renderSelectMultiple();

        $this->assertSame('mymultiselect[]', $select->getAttribute('name'));
    }

    /**
     * 5) Calling the render method twice in a row is safe and doesn't
     * accumulate extra brackets.
     */
    public function testRenderTwiceDoesNotAccumulateBrackets(): void
    {
        $select = new SelectMultiple('mymultiselect');

        $select->renderSelectMultiple();
        $select->renderSelectMultiple();

        $this->assertSame('mymultiselect[]', $select->getAttribute('name'));
    }

    /**
     * 6) Rendering delegates to Select's own rendering, so the added
     * options still appear in the output.
     */
    public function testRenderDelegatesToSelectRendering(): void
    {
        $select = new SelectMultiple('mymultiselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');

        $out = $select->renderSelectMultiple();

        $this->assertStringContainsString('value="red"', $out);
        $this->assertStringContainsString('value="blue"', $out);
        $this->assertStringContainsString('multiple', $out);
    }
}
