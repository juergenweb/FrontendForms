<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputRange;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputRange.
 */
final class InputRangeTest extends TestCase
{
    /**
     * 1) The field's type defaults to "range".
     */
    public function testConstructorSetsRangeType(): void
    {
        $field = new InputRange('volume');

        $this->assertSame('range', $field->getAttribute('type'));
    }

    /**
     * 2) The "numeric" validation rule is inherited from InputNumber.
     */
    public function testConstructorAppliesNumericRule(): void
    {
        $field = new InputRange('volume');

        $this->assertArrayHasKey('numeric', $field->getRules());
    }

    /**
     * 3) The default text-input CSS class is removed, and the dedicated
     * range class is present instead - confirmed via the field's own
     * class-attribute value, framework-agnostic.
     */
    public function testConstructorSwapsDefaultClassForRangeClass(): void
    {
        $field = new InputRange('volume');

        $classAttr = $field->getAttribute('class');
        $classList = is_array($classAttr) ? $classAttr : [$classAttr];
        $defaultInputClass = (string) $field->getCSSClass('inputClass');
        $rangeClass = (string) $field->getCSSClass('input_rangeClass');

        $this->assertContains($rangeClass, $classList);

        if ($defaultInputClass !== '') {
            $this->assertNotContains($defaultInputClass, $classList);
        }
    }

    /**
     * 4) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputRange('volume');

        $out = $field->renderInputRange();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="range"', $out);
        $this->assertStringContainsString('name="volume"', $out);
    }
}