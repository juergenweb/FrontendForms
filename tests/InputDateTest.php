<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputDate;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputDate.
 */
final class InputDateTest extends TestCase
{
    /**
     * 1) The field's type defaults to "date".
     */
    public function testConstructorSetsDateType(): void
    {
        $field = new InputDate('birthday');

        $this->assertSame('date', $field->getAttribute('type'));
    }

    /**
     * 2) The "date" validation rule is automatically applied.
     */
    public function testConstructorAppliesDateRule(): void
    {
        $field = new InputDate('birthday');

        $this->assertArrayHasKey('date', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputDate('birthday');

        $out = $field->renderInputDate();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="date"', $out);
        $this->assertStringContainsString('name="birthday"', $out);
    }
}
