<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\MailPlaceholderRegistry;
use FrontendForms\MailTemplateRenderer;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireMail;

/**
 * Unit tests for MailTemplateRenderer::getLitmusHack(), getPreheaderStyle(),
 * and generateEmailPreHeader().
 *
 * These three methods are pure string builders with no ProcessWire
 * dependency beyond reading a "title" property off a WireMail object. A real
 * WireMail instance (not a createMock() double) is used for that: WireMail's
 * "title" property is a magic __get/__set property inherited from WireData,
 * which relies on internal state set up by WireData's constructor - a
 * createMock() double has that constructor disabled by default, so
 * $mail->title would silently not stick. The rest of MailTemplateRenderer
 * (includeMailTemplate(), renderTemplate(), ...) is intentionally NOT
 * covered here - it needs wire('files')/wire('session') and a real mail
 * template file, which is out of scope for a plain unit test.
 */
final class MailTemplateRendererTest extends TestCase
{
    private function createRenderer(): MailTemplateRenderer
    {
        return new MailTemplateRenderer($this->createMock(Form::class), new MailPlaceholderRegistry());
    }

    // --- getLitmusHack() ---

    /**
     * 1) getLitmusHack() returns the exact, fixed sequence of invisible
     * HTML entities used to pad out the email preheader (so email clients
     * don't pull in the next visible text as a preview instead).
     */
    public function testGetLitmusHackReturnsExpectedFixedString(): void
    {
        $renderer = $this->createRenderer();

        $expected = '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; '
            . '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; '
            . '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; '
            . '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279;';

        $this->assertSame($expected, $renderer->getLitmusHack());
    }

    /**
     * 2) getLitmusHack() is pure - it returns the exact same value on every
     * call, with no side effects or state dependency.
     */
    public function testGetLitmusHackReturnsSameValueOnEveryCall(): void
    {
        $renderer = $this->createRenderer();

        $this->assertSame($renderer->getLitmusHack(), $renderer->getLitmusHack());
    }

    // --- getPreheaderStyle() ---

    /**
     * 3) getPreheaderStyle() returns the exact, fixed inline CSS that hides
     * the preheader text visually while keeping it in the DOM for email
     * clients to pick up as the inbox preview text.
     */
    public function testGetPreheaderStyleReturnsExpectedFixedString(): void
    {
        $renderer = $this->createRenderer();

        $this->assertSame(
            'display:none;font-size:1px; color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;',
            $renderer->getPreheaderStyle()
        );
    }

    // --- generateEmailPreHeader() ---

    /**
     * 4) With no title set on the mail object, generateEmailPreHeader()
     * returns an empty string - no empty/invisible wrapper div is emitted.
     */
    public function testGenerateEmailPreHeaderReturnsEmptyStringWhenTitleIsEmpty(): void
    {
        $renderer = $this->createRenderer();
        $mail = new WireMail();
        $mail->title = '';

        $this->assertSame('', $renderer->generateEmailPreHeader($mail));
    }

    /**
     * 5) With a title set, generateEmailPreHeader() wraps it in an
     * invisible div (using getPreheaderStyle() for the inline style) and
     * appends the litmus hack padding right after the title text.
     */
    public function testGenerateEmailPreHeaderWrapsTitleWithStyleAndLitmusHack(): void
    {
        $renderer = $this->createRenderer();
        $mail = new WireMail();
        $mail->title = 'Your order has shipped';

        $expected = '<div id="preheader-text" style="' . $renderer->getPreheaderStyle() . '">'
            . 'Your order has shipped'
            . $renderer->getLitmusHack()
            . '</div>';

        $this->assertSame($expected, $renderer->generateEmailPreHeader($mail));
    }
}
