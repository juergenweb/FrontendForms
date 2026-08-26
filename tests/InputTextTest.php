<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputText.
 */
final class InputTextTest extends TestCase
{
    // --- construction (inherited from Input) ---

    /**
     * 1) The field's type defaults to "text".
     */
    public function testConstructorSetsTextType(): void
    {
        $field = new InputText('name');

        $this->assertSame('text', $field->getAttribute('type'));
    }

    /**
     * 2) The element's tag is "input".
     */
    public function testConstructorSetsInputTag(): void
    {
        $field = new InputText('name');

        $this->assertSame('input', $field->getTag());
    }

    // --- ___renderInputText() ---

    /**
     * 3) Rendering produces a self-closing input tag with a trailing
     * newline (as renderInput() appends PHP_EOL).
     */
    public function testRenderProducesSelfClosingInputTag(): void
    {
        $field = new InputText('name');

        $out = $field->renderInputText();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringEndsWith(PHP_EOL, $out);
    }

    /**
     * 4) The field's name/type attributes appear in the rendered output.
     */
    public function testRenderIncludesNameAndTypeAttributes(): void
    {
        $field = new InputText('name');

        $out = $field->renderInputText();

        $this->assertStringContainsString('type="text"', $out);
        $this->assertStringContainsString('name="name"', $out);
    }

    /**
     * 5) A value set on the field appears in the rendered output.
     */
    public function testRenderIncludesValueAttribute(): void
    {
        $field = new InputText('name');
        $field->setAttribute('value', 'John Doe');

        $out = $field->renderInputText();

        $this->assertStringContainsString('value="John Doe"', $out);
    }
}
