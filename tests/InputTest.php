<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Input;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Input.
 *
 * Most of what this base class provides is already exercised indirectly
 * through its many subclasses (InputText, InputCheckbox, InputRadio, ...),
 * but it's directly instantiable itself, so it's tested directly here too.
 */
final class InputTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag is "input".
     */
    public function testConstructorSetsInputTag(): void
    {
        $field = new Input('name');

        $this->assertSame('input', $field->getTag());
    }

    /**
     * 2) The type defaults to "text".
     */
    public function testConstructorSetsTextTypeByDefault(): void
    {
        $field = new Input('name');

        $this->assertSame('text', $field->getAttribute('type'));
    }

    /**
     * 3) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $field = new Input('name');

        $this->assertNotEmpty($field->getAttribute('class'));
    }

    // --- ___renderInput() ---

    /**
     * 4) Rendering produces a self-closing input tag, ending with a
     * trailing newline.
     */
    public function testRenderProducesSelfClosingTagWithTrailingNewline(): void
    {
        $field = new Input('name');

        $out = $field->renderInput();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringEndsWith(PHP_EOL, $out);
    }

    /**
     * 5) Changing the type attribute (as subclasses like InputCheckbox do)
     * is reflected in the rendered output.
     */
    public function testRenderReflectsChangedTypeAttribute(): void
    {
        $field = new Input('agree');
        $field->setAttribute('type', 'checkbox');

        $out = $field->renderInput();

        $this->assertStringContainsString('type="checkbox"', $out);
    }
}
