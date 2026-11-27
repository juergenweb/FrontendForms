<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FormFieldHelper;
use FrontendForms\Textarea;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for Textarea.
 */
final class TextareaTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag is "textarea", with a default of 5 rows.
     */
    public function testConstructorSetsTagAndDefaultRows(): void
    {
        $textarea = new Textarea('mytextarea');

        $this->assertSame('textarea', $textarea->getTag());
        $this->assertSame('5', $textarea->getAttribute('rows'));
    }

    /**
     * 2) The default "text" sanitizer is removed and replaced with
     * "textarea" for security reasons.
     */
    public function testConstructorUsesTextareaSanitizer(): void
    {
        $textarea = new Textarea('mytextarea');

        $this->assertFalse($textarea->hasSanitizer('text'));
        $this->assertTrue($textarea->hasSanitizer('textarea'));
    }

    // --- useCharacterCounter() ---

    /**
     * 3) Enabling the character counter sets the corresponding data
     * attribute to "1".
     */
    public function testUseCharacterCounterSetsDataAttribute(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->useCharacterCounter();

        $this->assertSame('1', $textarea->getAttribute('data-charactercounter'));
    }

    /**
     * 4) Explicitly disabling the character counter sets the data
     * attribute to "0".
     */
    public function testDisableCharacterCounterSetsDataAttributeToZero(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->useCharacterCounter(false);

        $this->assertSame('0', $textarea->getAttribute('data-charactercounter'));
    }

    // --- getCharacterCounter() ---

    /**
     * 5) getCharacterCounter() returns the same TextElements instance on
     * every call.
     */
    public function testGetCharacterCounterReturnsSameInstance(): void
    {
        $textarea = new Textarea('mytextarea');

        $this->assertSame($textarea->getCharacterCounter(), $textarea->getCharacterCounter());
    }

    // --- ___renderTextarea() ---

    /**
     * 6) The "value" attribute becomes the textarea's rendered content.
     */
    public function testRenderUsesValueAttributeAsContent(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setAttribute('value', 'Hello world');

        $out = $textarea->renderTextarea();

        $this->assertStringContainsString('Hello world', $out);
    }

    /**
     * 7) With the character counter disabled (the default), no counter
     * markup is rendered, even if a "lengthMax" rule is present.
     */
    public function testNoCounterRenderedWhenDisabled(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setRule('lengthMax', 100);

        $out = $textarea->renderTextarea();

        $this->assertStringNotContainsString('fc-counter', $out);
    }

    /**
     * 8) With the counter enabled but no "lengthMax" rule set, no counter
     * markup is rendered either - there is no maximum to count towards.
     */
    public function testNoCounterRenderedWithoutLengthMaxRule(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->useCharacterCounter();

        $out = $textarea->renderTextarea();

        $this->assertStringNotContainsString('fc-counter', $out);
    }

    /**
     * 9) With the counter enabled AND a "lengthMax" rule present, the
     * counter markup is rendered and mentions the configured maximum.
     */
    public function testCounterRenderedWhenEnabledWithLengthMaxRule(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->useCharacterCounter();
        $textarea->setRule('lengthMax', 100);

        $out = $textarea->renderTextarea();

        $this->assertStringContainsString('fc-counter', $out);
        $this->assertStringContainsString('100', $out);
    }

    // --- escaping of the textarea content (XSS) ---

    /**
     * 10) A default value containing "</textarea>" must not close the
     * textarea: the content is escaped, so the injected markup is shown as
     * text inside the textarea instead of being rendered as HTML.
     */
    public function testDefaultValueCannotBreakOutOfTextarea(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setDefaultValue('a</textarea><b id=x>injected</b>');

        $out = $textarea->renderTextarea();

        $this->assertStringNotContainsString('<b id=x>', $out);
        $this->assertSame(1, substr_count($out, '</textarea>'));
        $this->assertStringContainsString('>a&lt;/textarea&gt;&lt;b id=x&gt;injected&lt;/b&gt;</textarea>', $out);
    }

    /**
     * 11) A submitted value is written back unchanged into the "value"
     * attribute by FormFieldHelper::sanitizePostValue() and re-displayed after
     * a validation error - it must be escaped the same way.
     */
    public function testSubmittedValueCannotBreakOutOfTextarea(): void
    {
        $textarea = new Textarea('mytextarea');
        $payload = 'Teststrasse 1</textarea><b id="xss-marker">INJECTED</b>';
        $post = [$textarea->getAttribute('name') => $payload];
        $helper = new FormFieldHelper(new WireInputData($post));

        $this->assertSame($payload, $helper->sanitizePostValue($textarea));
        $out = $textarea->renderTextarea();

        $this->assertStringNotContainsString('<b id="xss-marker">', $out);
        $this->assertStringContainsString('Teststrasse 1&lt;/textarea&gt;&lt;b id=&quot;xss-marker&quot;&gt;INJECTED&lt;/b&gt;', $out);
    }

    /**
     * 12) Umlauts and other non-ASCII characters are not converted to
     * entities - only the HTML special characters are escaped.
     */
    public function testUmlautsAreNotEncoded(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setAttribute('value', "Grüße aus Wien & Linz\nZeile 2");

        $out = $textarea->renderTextarea();

        $this->assertStringContainsString(">Grüße aus Wien &amp; Linz\nZeile 2</textarea>", $out);
    }

    /**
     * 13) A value with invalid UTF-8 is not dropped (without ENT_SUBSTITUTE
     * htmlspecialchars() would return an empty string).
     */
    public function testValueWithInvalidUtf8IsNotDropped(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setAttribute('value', "abc\xff</textarea>");

        $out = $textarea->renderTextarea();

        $this->assertStringContainsString(">abc\u{FFFD}&lt;/textarea&gt;</textarea>", $out);
    }

    /**
     * 14) Rendering twice does not escape the value twice - the content is
     * always built from the unescaped "value" attribute.
     */
    public function testRenderingTwiceDoesNotDoubleEscape(): void
    {
        $textarea = new Textarea('mytextarea');
        $textarea->setAttribute('value', 'Tom & Jerry');

        $textarea->renderTextarea();
        $out = $textarea->renderTextarea();

        $this->assertStringContainsString('>Tom &amp; Jerry</textarea>', $out);
    }
}
