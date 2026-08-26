<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\RefererGuard;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for RefererGuard.
 *
 * The site's own host (wire('config')->httpHost) is read directly from the
 * live test environment for the "matching host" test, rather than assumed
 * or hardcoded, since it can't be controlled/mocked (it's read via the
 * global wire() function, not injected).
 */
final class RefererGuardTest extends TestCase
{

    /**
     * WireInputData's constructor takes its array argument by reference,
     * so it can't be passed a literal array expression directly at the
     * call site (new WireInputData([...])) - wrapping it in a real
     * parameter variable here satisfies that requirement.
     */
    private function inputData(array $data = []): WireInputData
    {
        return new WireInputData($data);
    }
    protected function setUp(): void
    {
        parent::setUp();
        unset($_SERVER['HTTP_REFERER']);
    }

    private function guard(): RefererGuard
    {
        return new RefererGuard($this->inputData([]), new Form('myform'), new Alert());
    }

    /**
     * 1) A missing Referer header is treated as inconclusive and allowed,
     * not rejected - many legitimate browsers/privacy tools strip it.
     */
    public function testMissingRefererIsAllowed(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check());
    }

    /**
     * 2) An empty Referer header (present but blank) is treated the same
     * as a missing one - allowed.
     */
    public function testEmptyRefererIsAllowed(): void
    {
        $_SERVER['HTTP_REFERER'] = '';

        $this->assertTrue($this->guard()->check());
    }

    /**
     * 3) A Referer pointing to an entirely different host is rejected.
     */
    public function testRefererFromDifferentHostIsRejected(): void
    {
        $_SERVER['HTTP_REFERER'] = 'https://this-is-definitely-not-the-site-host.example/page';

        $this->assertFalse($this->guard()->check());
    }

    /**
     * 4) A Referer pointing to this site's own host (read live from the
     * test environment's actual config, since it can't be known in
     * advance) is allowed.
     */
    public function testRefererFromSameHostIsAllowed(): void
    {
        $siteHost = \ProcessWire\wire('config')->httpHost;
        $_SERVER['HTTP_REFERER'] = 'https://' . $siteHost . '/some/page';

        $this->assertTrue($this->guard()->check());
    }

    /**
     * 5) A Referer that merely CONTAINS the site host as a substring (e.g.
     * an attacker-controlled host with the real host embedded elsewhere in
     * the URL) must still be rejected - parse_url() extraction, not a
     * naive substring check, is what matters here.
     */
    public function testRefererWithSiteHostAsSubstringOnlyIsRejected(): void
    {
        $siteHost = \ProcessWire\wire('config')->httpHost;
        $_SERVER['HTTP_REFERER'] = 'https://evil.example/' . $siteHost;

        $this->assertFalse($this->guard()->check());
    }

    /**
     * 6) REGRESSION TEST for the fixed bug: a Referer host that matches
     * the site host except for letter casing (hostnames are not
     * case-sensitive per DNS) is correctly allowed, not rejected.
     */
    public function testRefererWithDifferentCaseIsAllowed(): void
    {
        $siteHost = \ProcessWire\wire('config')->httpHost;
        $_SERVER['HTTP_REFERER'] = 'https://' . strtoupper($siteHost) . '/some/page';

        $this->assertTrue($this->guard()->check());
    }
}