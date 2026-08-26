<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Link;
use FrontendForms\Privacy;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Privacy.
 *
 * setPrivacyPageUrl()'s "int" (internal page) branch needs a real,
 * configured ProcessWire page and isn't covered here (same reasoning as
 * setPageLink() elsewhere in this session) - the "ext" (external URL)
 * branch is fully self-contained and deterministic, so it's covered in
 * detail.
 */
final class PrivacyTest extends TestCase
{
    // --- setPrivacyPageUrl() ---

    /**
     * 1) With neither an internal page nor an external URL configured,
     * setPrivacyPageUrl() returns false and leaves the link untouched.
     */
    public function testSetPrivacyPageUrlReturnsFalseWithNoConfig(): void
    {
        $link = new Link();

        $result = Privacy::setPrivacyPageUrl(['input_privacypageselect' => ''], $link);

        $this->assertFalse($result);
        $this->assertNull($link->getUrl());
    }

    /**
     * 2) With "ext" selected and a URL configured, the link's URL is set
     * to that value and the method returns true.
     */
    public function testSetPrivacyPageUrlWithExternalUrlSetsLinkUrl(): void
    {
        $link = new Link();
        $config = [
            'input_privacypageselect' => 'ext',
            'input_privacypageurl' => 'https://example.com/privacy',
        ];

        $result = Privacy::setPrivacyPageUrl($config, $link);

        $this->assertTrue($result);
        $this->assertSame('https://example.com/privacy', $link->getUrl());
    }

    /**
     * 3) With "ext" selected but no URL actually configured (empty
     * string), setPrivacyPageUrl() returns false.
     */
    public function testSetPrivacyPageUrlWithExternalSelectedButEmptyUrlReturnsFalse(): void
    {
        $link = new Link();
        $config = [
            'input_privacypageselect' => 'ext',
            'input_privacypageurl' => '',
        ];

        $this->assertFalse(Privacy::setPrivacyPageUrl($config, $link));
    }

    /**
     * 4) With "int" selected but no page id actually configured,
     * setPrivacyPageUrl() returns false (does not attempt to resolve a
     * page at all).
     */
    public function testSetPrivacyPageUrlWithInternalSelectedButNoPageReturnsFalse(): void
    {
        $link = new Link();
        $config = [
            'input_privacypageselect' => 'int',
            'input_privacy' => null,
        ];

        $this->assertFalse(Privacy::setPrivacyPageUrl($config, $link));
    }

    // --- construction ---

    /**
     * 5) A "required" validation rule is added by default.
     */
    public function testConstructorAddsRequiredRule(): void
    {
        $field = new Privacy('privacy');

        $this->assertArrayHasKey('required', $field->getRules());
    }

    /**
     * 6) A non-empty label is set on construction.
     */
    public function testConstructorSetsNonEmptyLabel(): void
    {
        $field = new Privacy('privacy');

        $this->assertNotSame('', $field->getLabel()->getText());
    }

    // --- setPrivacyUrl() / getPrivacyUrl() ---

    /**
     * 7) setPrivacyUrl()/getPrivacyUrl() round-trip the privacy link's URL.
     */
    public function testSetAndGetPrivacyUrl(): void
    {
        $field = new Privacy('privacy');

        $field->setPrivacyUrl('https://example.com/privacy-policy');

        $this->assertSame('https://example.com/privacy-policy', $field->getPrivacyUrl());
    }

    // --- ___renderPrivacy() ---

    /**
     * 8) renderPrivacy() (dispatched via the class-name-based render
     * mechanism - "render" + className() = "renderPrivacy" for a Privacy
     * instance) produces the same output as the inherited
     * renderInputCheckbox() it forwards to.
     */
    public function testRenderPrivacyMatchesRenderInputCheckbox(): void
    {
        $field = new Privacy('privacy');

        $this->assertSame($field->renderInputCheckbox(), $field->renderPrivacy());
    }
}
