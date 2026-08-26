<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Markup;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Markup.
 */
final class MarkupTest extends TestCase
{
    // --- setMarkup() / getMarkup() ---

    /**
     * 1) A freshly created Markup element holds an empty string.
     */
    public function testMarkupIsEmptyByDefault(): void
    {
        $markup = new Markup();

        $this->assertSame('', $markup->getMarkup());
    }

    /**
     * 2) setMarkup()/getMarkup() round-trip the raw HTML string.
     */
    public function testSetAndGetMarkup(): void
    {
        $markup = new Markup();

        $markup->setMarkup('<div class="custom">Hello</div>');

        $this->assertSame('<div class="custom">Hello</div>', $markup->getMarkup());
    }

    /**
     * 3) setMarkup() returns $this, supporting fluent chaining.
     */
    public function testSetMarkupReturnsSelf(): void
    {
        $markup = new Markup();

        $this->assertSame($markup, $markup->setMarkup('<p>Hi</p>'));
    }

    // --- render() ---

    /**
     * 4) render() outputs the raw HTML string exactly as set, completely
     * unescaped - this is the one place in the whole module that
     * deliberately outputs raw, developer-supplied HTML unchanged.
     */
    public function testRenderOutputsRawMarkupUnchanged(): void
    {
        $markup = new Markup();
        $markup->setMarkup('<div class="custom"><strong>Hello</strong> & welcome</div>');

        $this->assertSame(
            '<div class="custom"><strong>Hello</strong> & welcome</div>',
            $markup->render()
        );
    }

    /**
     * 5) With no markup set, render() returns an empty string.
     */
    public function testRenderWithNoMarkupReturnsEmptyString(): void
    {
        $markup = new Markup();

        $this->assertSame('', $markup->render());
    }

    // --- no-op stub methods ---

    /**
     * 6) containsConditions() always returns false - Markup elements never
     * participate in conditional-field logic.
     */
    public function testContainsConditionsIsAlwaysFalse(): void
    {
        $markup = new Markup();

        $this->assertFalse($markup->containsConditions());
    }

    /**
     * 7) setAttribute()/getID()/getAttribute() are no-ops that don't throw
     * when called - they exist purely so Markup can be handled uniformly
     * alongside real form elements during rendering, without actually
     * doing anything.
     */
    public function testStubMethodsDoNotThrow(): void
    {
        $markup = new Markup();

        $markup->setAttribute('id', 'ignored');
        $this->assertNull($markup->getID());
        $this->assertNull($markup->getAttribute());

        // markup content is untouched by any of the above
        $this->assertSame('', $markup->getMarkup());
    }
}
