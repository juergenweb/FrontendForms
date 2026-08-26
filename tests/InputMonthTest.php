<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputMonth;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputMonth.
 */
final class InputMonthTest extends TestCase
{
    /**
     * 1) The field's type defaults to "month".
     */
    public function testConstructorSetsMonthType(): void
    {
        $field = new InputMonth('billingmonth');

        $this->assertSame('month', $field->getAttribute('type'));
    }

    /**
     * 2) The "month" validation rule is automatically applied.
     */
    public function testConstructorAppliesMonthRule(): void
    {
        $field = new InputMonth('billingmonth');

        $this->assertArrayHasKey('month', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputMonth('billingmonth');

        $out = $field->renderInputMonth();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="month"', $out);
        $this->assertStringContainsString('name="billingmonth"', $out);
    }
}
