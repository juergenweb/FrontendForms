<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\IPBlacklistGuard;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for IPBlacklistGuard.
 *
 * check() has no ProcessWire dependency at all (no wire() calls), so it's
 * fully self-contained and deterministic - no mocks or live environment
 * concerns needed.
 */
final class IPBlacklistGuardTest extends TestCase
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
    private function guard(): IPBlacklistGuard
    {
        return new IPBlacklistGuard($this->inputData([]), new Form('myform'), new Alert());
    }

    /**
     * 1) With the IP ban feature disabled, every visitor is allowed,
     * regardless of the blacklist content.
     */
    public function testDisabledFeatureAlwaysAllows(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check(false, "1.2.3.4", '1.2.3.4'));
    }

    /**
     * 2) With an empty blacklist, every visitor is allowed.
     */
    public function testEmptyBlacklistAlwaysAllows(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check(true, '', '1.2.3.4'));
    }

    /**
     * 3) A visitor IP that appears in the blacklist is rejected.
     */
    public function testBlacklistedIpIsRejected(): void
    {
        $guard = $this->guard();

        $this->assertFalse($guard->check(true, "1.2.3.4\n5.6.7.8", '5.6.7.8'));
    }

    /**
     * 4) A visitor IP that does NOT appear in the blacklist is allowed.
     */
    public function testNonBlacklistedIpIsAllowed(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check(true, "1.2.3.4\n5.6.7.8", '9.9.9.9'));
    }

    /**
     * 5) Extra whitespace around each blacklist entry (a common copy-paste
     * artifact in the admin textarea) is trimmed before comparison.
     */
    public function testBlacklistEntriesAreTrimmed(): void
    {
        $guard = $this->guard();

        $this->assertFalse($guard->check(true, "  1.2.3.4  \n  5.6.7.8  ", '5.6.7.8'));
    }

    /**
     * 6) The comparison is strict (type + value), matching the guard's use
     * of in_array(..., true) - not just a loose/coincidental match.
     */
    public function testComparisonIsStrict(): void
    {
        $guard = $this->guard();

        // "0" as a blacklist entry must not match unrelated falsy-ish IPs
        $this->assertTrue($guard->check(true, "0", ''));
        $this->assertFalse($guard->check(true, "0", '0'));
    }
}