<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Button;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Button.
 */
final class ButtonTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The button's tag, type, name and default value are set on
     * construction.
     */
    public function testConstructorSetsDefaults(): void
    {
        $button = new Button('mybutton');

        $this->assertSame('button', $button->getTag());
        $this->assertSame('submit', $button->getAttribute('type'));
        $this->assertSame('mybutton', $button->getAttribute('name'));
        $this->assertSame('Send', $button->getAttribute('value'));
    }

    /**
     * 2) Without an explicit name, the button defaults to "submit".
     */
    public function testConstructorDefaultsNameToSubmit(): void
    {
        $button = new Button();

        $this->assertSame('submit', $button->getAttribute('name'));
    }

    // --- getWrapper() ---

    /**
     * 3) getWrapper() returns null before any wrapper was created.
     */
    public function testGetWrapperIsNullByDefault(): void
    {
        $button = new Button('mybutton');

        $this->assertNull($button->getWrapper());
    }

    // --- __toString() ---

    /**
     * 4) Casting the button to a string produces the same markup as
     * calling render() directly.
     */
    public function testToStringMatchesRender(): void
    {
        $button = new Button('mybutton');

        $this->assertSame($button->render(), (string) $button);
    }

    // --- showAttributeValue() ---

    /**
     * 5) With showAttributeValue(true) (the constructor default), the
     * rendered button keeps a "value" attribute in addition to using it as
     * content.
     */
    public function testShowAttributeValueTrueKeepsValueAttribute(): void
    {
        $button = new Button('mybutton');
        $button->setAttribute('value', 'Submit form');

        $out = $button->render();

        $this->assertStringContainsString('value="Submit form"', $out);
        $this->assertStringContainsString('>Submit form<', $out);
    }

    /**
     * 6) With showAttributeValue(false), the "value" HTML attribute is
     * omitted from the rendered tag, even though the button's visible text
     * (content) still comes from it.
     */
    public function testShowAttributeValueFalseOmitsValueAttribute(): void
    {
        $button = new Button('mybutton');
        $button->setAttribute('value', 'Submit form');
        $button->showAttributeValue(false);

        $out = $button->render();

        $this->assertStringNotContainsString('value="Submit form"', $out);
        $this->assertStringContainsString('>Submit form<', $out);
    }

    // --- showNoContent() ---

    /**
     * 7) With an empty value/content and showNoContent(false) (the
     * constructor default), rendering produces an empty string.
     */
    public function testShowNoContentFalseHidesEmptyButton(): void
    {
        $button = new Button('mybutton');
        $button->setAttribute('value', '');

        $this->assertSame('', $button->render());
    }

    /**
     * 8) With an empty value/content but showNoContent(true), the button is
     * still rendered as an empty element.
     */
    public function testShowNoContentTrueStillRendersEmptyButton(): void
    {
        $button = new Button('mybutton');
        $button->setAttribute('value', '');
        $button->showNoContent(true);

        $out = $button->render();

        $this->assertStringContainsString('<button', $out);
        $this->assertStringContainsString('</button>', $out);
    }

    // --- ___render() wrapper handling ---

    /**
     * 9) When a wrapper was created via wrap(), the rendered button markup
     * ends up as the wrapper's content, surrounded by the wrapper's own tag.
     */
    public function testRenderWrapsButtonWhenWrapperExists(): void
    {
        $button = new Button('mybutton');
        $button->wrap()->setTag('div')->setAttribute('class', 'button-wrap');

        $out = $button->render();

        $this->assertStringContainsString('<div class="button-wrap">', $out);
        $this->assertStringContainsString('<button', $out);
        $this->assertStringContainsString('</div>', $out);
    }

    /**
     * 10) Without a wrapper, rendering produces just the plain <button> tag.
     */
    public function testRenderWithoutWrapperProducesPlainButtonTag(): void
    {
        $button = new Button('mybutton');

        $out = $button->render();

        $this->assertStringStartsWith('<button', $out);
    }
}
