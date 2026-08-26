<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputColor;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputColor.
 */
final class InputColorTest extends TestCase
{
    /**
     * 1) The field's type defaults to "color".
     */
    public function testConstructorSetsColorType(): void
    {
        $field = new InputColor('favcolor');

        $this->assertSame('color', $field->getAttribute('type'));
    }

    /**
     * 2) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputColor('favcolor');

        $out = $field->renderInputColor();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="color"', $out);
        $this->assertStringContainsString('name="favcolor"', $out);
    }

    /**
     * 3) A value set on the field appears in the rendered output.
     */
    public function testRenderIncludesValueAttribute(): void
    {
        $field = new InputColor('favcolor');
        $field->setAttribute('value', '#FF0000');

        $out = $field->renderInputColor();

        $this->assertStringContainsString('value="#FF0000"', $out);
    }
}
