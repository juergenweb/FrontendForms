<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Link;
use FrontendForms\PrivacyText;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for PrivacyText.
 *
 * Whether a real privacy-policy page link is available (linkExists) depends
 * on the live "input_privacypage" module config, which can't be controlled
 * from a plain unit test - so most tests here force a known state via
 * ReflectionProperty rather than relying on what the live environment
 * happens to have configured (similar to the config-dependent tests
 * elsewhere in this session).
 */
final class PrivacyTextTest extends TestCase
{
    private function forceNoLinkWithFallbackText(PrivacyText $text, string $fallback): void
    {
        $linkExists = new ReflectionProperty($text, 'linkExists');
        $linkExists->setAccessible(true);
        $linkExists->setValue($text, false);

        $privacy = new ReflectionProperty($text, 'privacy');
        $privacy->setAccessible(true);
        $privacy->setValue($text, $fallback);
    }

    // --- construction ---

    /**
     * 1) The "privacy-text" CSS class is applied on construction.
     */
    public function testConstructorSetsPrivacyTextCssClass(): void
    {
        $text = new PrivacyText();

        $this->assertContains('privacy-text', $text->getAttribute('class'));
    }

    /**
     * 2) getPolicyLink() returns a Link instance for further manipulation.
     */
    public function testGetPolicyLinkReturnsLinkInstance(): void
    {
        $text = new PrivacyText();

        $this->assertInstanceOf(Link::class, $text->getPolicyLink());
    }

    // --- ___render() ---

    /**
     * 3) With no policy page link available, the fallback plain text is
     * substituted into the template.
     */
    public function testRenderUsesFallbackTextWhenNoLinkExists(): void
    {
        $text = new PrivacyText();
        $this->forceNoLinkWithFallbackText($text, 'Terms of use and Privacy Policy');

        $out = $text->render();

        $this->assertStringContainsString('Terms of use and Privacy Policy', $out);
    }

    /**
     * 4) REGRESSION TEST for the repeated-render bug: calling render()
     * more than once on the same instance must produce identical output
     * every time, not throw and not silently diverge. Before the fix,
     * ___render() overwrote $this->text with the already-substituted
     * result and used getText() (the now-substituted text) as the sprintf()
     * format string on the next call - confirmed standalone before writing
     * this assertion that if the substituted text contains a stray "%"
     * (e.g. a fallback/link text mentioning "50%"), a second render() call
     * would throw a ValueError ("Unknown format specifier").
     */
    public function testRepeatedRenderCallsProduceIdenticalOutputEvenWithPercentSign(): void
    {
        $text = new PrivacyText();
        $this->forceNoLinkWithFallbackText($text, 'Save 50% on our Terms');

        $first = $text->render();
        $second = $text->render();
        $third = $text->render();

        $this->assertSame($first, $second);
        $this->assertSame($second, $third);
        $this->assertStringContainsString('Save 50% on our Terms', $third);
    }

    /**
     * 5) The rendered output is non-empty and ends with a period, matching
     * the template's fixed sentence structure.
     */
    public function testRenderProducesCompleteSentence(): void
    {
        $text = new PrivacyText();
        $this->forceNoLinkWithFallbackText($text, 'our policy');

        $out = strip_tags($text->render());

        $this->assertStringEndsWith('.', trim($out));
    }
}
