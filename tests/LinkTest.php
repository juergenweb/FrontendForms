<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Link;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Link.
 *
 * setPageLink() is NOT covered here - it needs a real, saved ProcessWire
 * Page (url/title resolution, SEO Maestro module lookup), which is better
 * suited to an integration test (same reasoning as the other
 * live-database-dependent methods skipped elsewhere in this session).
 */
final class LinkTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag is "a".
     */
    public function testConstructorSetsAnchorTag(): void
    {
        $link = new Link('mylink');

        $this->assertSame('a', $link->getTag());
    }

    // --- getLinkText() / setLinkText() ---

    /**
     * 2) setLinkText()/getLinkText() round-trip the link's visible text.
     */
    public function testSetAndGetLinkText(): void
    {
        $link = new Link('mylink');
        $link->setLinkText('Click here');

        $this->assertSame('Click here', $link->getLinkText());
    }

    // --- getUrl() / setUrl() ---

    /**
     * 3) setUrl()/getUrl() round-trip the link's target URL.
     */
    public function testSetAndGetUrl(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com');

        $this->assertSame('https://example.com', $link->getUrl());
    }

    /**
     * 4) Calling setUrl() with null (or not at all) is a no-op - it does
     * not clear a previously set URL.
     */
    public function testSetUrlWithNullDoesNotClearExistingUrl(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com');
        $link->setUrl(null);

        $this->assertSame('https://example.com', $link->getUrl());
    }

    // --- getAnchor() / setAnchor() ---

    /**
     * 5) setAnchor() strips a leading "#" if present.
     */
    public function testSetAnchorStripsLeadingHash(): void
    {
        $link = new Link('mylink');
        $link->setAnchor('#section');

        $this->assertSame('section', $link->getAnchor());
    }

    /**
     * 6) setAnchor() works the same whether or not the caller already
     * included the leading "#".
     */
    public function testSetAnchorWorksWithoutLeadingHash(): void
    {
        $link = new Link('mylink');
        $link->setAnchor('section');

        $this->assertSame('section', $link->getAnchor());
    }

    /**
     * 7) REGRESSION TEST for the ltrim()-vs-substr() fix: an anchor value
     * with a DOUBLE leading "#" only has the first one stripped (the one
     * actually recognized as "the prefix" by str_starts_with()), not every
     * leading "#" character. Before the fix, ltrim($value, '#') would have
     * stripped both, since ltrim()'s second argument is a character mask,
     * not a literal prefix - confirmed standalone before writing this
     * assertion:
     *   ltrim("##section", "#") => "section"   (wrong: strips both)
     *   substr("##section", 1)  => "#section"  (correct: strips only one)
     */
    public function testSetAnchorOnlyStripsOneLeadingHashNotAll(): void
    {
        $link = new Link('mylink');
        $link->setAnchor('##section');

        $this->assertSame('#section', $link->getAnchor());
    }

    /**
     * 8) A freshly created link has no anchor.
     */
    public function testAnchorIsEmptyByDefault(): void
    {
        $this->assertSame('', (new Link('mylink'))->getAnchor());
    }

    // --- setQueryString() / getQueryString() ---

    /**
     * 9) Multiple calls to setQueryString() accumulate, joined by "&".
     */
    public function testSetQueryStringAccumulatesMultipleCalls(): void
    {
        $link = new Link('mylink');
        $link->setQueryString('foo=1');
        $link->setQueryString('bar=2');

        $this->assertSame('foo=1&bar=2', $link->getQueryString());
    }

    /**
     * 10) A freshly created link has no query string.
     */
    public function testQueryStringIsEmptyByDefault(): void
    {
        $this->assertSame('', (new Link('mylink'))->getQueryString());
    }

    // --- ___render() ---

    /**
     * 11) When no link text was set but a URL is present, the URL itself
     * becomes the visible link text.
     */
    public function testRenderUsesUrlAsLinkTextWhenTextIsEmpty(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com');

        $out = $link->render();

        $this->assertStringContainsString('>https://example.com<', $out);
    }

    /**
     * 12) An explicitly set link text takes precedence over the URL.
     */
    public function testRenderPrefersExplicitLinkTextOverUrl(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com');
        $link->setLinkText('Visit our site');

        $out = $link->render();

        $this->assertStringContainsString('>Visit our site<', $out);
        $this->assertStringNotContainsString('>https://example.com<', $out);
    }

    /**
     * 13) The query string and anchor are appended to the URL in the
     * rendered "href" attribute, in that order.
     */
    public function testRenderAppendsQueryStringAndAnchorToHref(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com/page');
        $link->setQueryString('foo=1');
        $link->setAnchor('section');

        $out = $link->render();

        $this->assertStringContainsString('href="https://example.com/page?foo=1#section"', $out);
    }

    /**
     * 14) With neither a query string nor an anchor, the href is just the
     * plain URL.
     */
    public function testRenderWithPlainUrlHasNoQueryStringOrAnchor(): void
    {
        $link = new Link('mylink');
        $link->setUrl('https://example.com/page');

        $out = $link->render();

        $this->assertStringContainsString('href="https://example.com/page"', $out);
    }
}
