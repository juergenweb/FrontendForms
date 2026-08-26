<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Textarea;
use PHPUnit\Framework\TestCase;

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
}
