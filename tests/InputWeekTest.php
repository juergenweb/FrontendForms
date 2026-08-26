<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputWeek;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputWeek.
 */
final class InputWeekTest extends TestCase
{
    /**
     * 1) The field's type defaults to "week".
     */
    public function testConstructorSetsWeekType(): void
    {
        $field = new InputWeek('deliveryweek');

        $this->assertSame('week', $field->getAttribute('type'));
    }

    /**
     * 2) The "week" validation rule is automatically applied.
     */
    public function testConstructorAppliesWeekRule(): void
    {
        $field = new InputWeek('deliveryweek');

        $this->assertArrayHasKey('week', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputWeek('deliveryweek');

        $out = $field->renderInputWeek();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="week"', $out);
        $this->assertStringContainsString('name="deliveryweek"', $out);
    }
}
