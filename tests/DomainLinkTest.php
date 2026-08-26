<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DomainLink;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DomainLink.
 *
 * Uses the root page ("/"), which always exists in any ProcessWire
 * installation, so setPageLink() (otherwise skipped elsewhere in this
 * session due to needing an arbitrary, possibly-nonexistent page) is safe
 * to exercise here.
 */
final class DomainLinkTest extends TestCase
{
    /**
     * 1) The "home-link" CSS class is applied on construction.
     */
    public function testConstructorSetsHomeLinkCssClass(): void
    {
        $link = new DomainLink();

        $this->assertContains('home-link', $link->getAttribute('class'));
    }

    /**
     * 2) The link text is set to the site's host URL.
     */
    public function testConstructorSetsLinkTextToHostUrl(): void
    {
        $link = new DomainLink();

        $this->assertNotSame('', $link->getLinkText());
    }

    /**
     * 3) By default (internal = true), the link points to the homepage via
     * setPageLink(), which resolves to a real, non-empty URL.
     */
    public function testInternalLinkByDefaultHasResolvedUrl(): void
    {
        $link = new DomainLink();

        $this->assertNotSame('', $link->getUrl());
    }

    /**
     * 4) With internal = false, the link's URL is set directly to the
     * site's absolute host URL, matching the link text.
     */
    public function testExternalLinkUsesAbsoluteHostUrlDirectly(): void
    {
        $link = new DomainLink(null, false);

        $this->assertSame($link->getLinkText(), $link->getUrl());
    }
}
