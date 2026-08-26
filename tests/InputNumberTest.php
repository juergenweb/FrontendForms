<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputNumber;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputNumber.
 */
final class InputNumberTest extends TestCase
{
    /**
     * 1) The field's type defaults to "number".
     */
    public function testConstructorSetsNumberType(): void
    {
        $field = new InputNumber('quantity');

        $this->assertSame('number', $field->getAttribute('type'));
    }

    /**
     * 2) The "numeric" validation rule is automatically applied.
     */
    public function testConstructorAppliesNumericRule(): void
    {
        $field = new InputNumber('quantity');

        $this->assertArrayHasKey('numeric', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputNumber('quantity');

        $out = $field->renderInputNumber();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="number"', $out);
        $this->assertStringContainsString('name="quantity"', $out);
    }
}
