<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\CSRFGuard;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for CSRFGuard.
 *
 * check() relies heavily on wire('session')->CSRF for the "post"/"get"
 * success paths, which needs real, live session state that can't be
 * controlled/mocked from a plain unit test. Only the two paths that are
 * fully independent of session state are covered here: the early-return
 * when CSRF protection is disabled, and the fail-closed default for an
 * unrecognized HTTP method (which never reaches wire('session') at all,
 * since match() falls through to the "default" arm immediately).
 */
final class CSRFGuardTest extends TestCase
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
    private function guard(): CSRFGuard
    {
        return new CSRFGuard($this->inputData([]), new Form('myform'), new Alert());
    }

    /**
     * 1) With CSRF protection disabled, the check always passes,
     * regardless of the HTTP method - even a nonsensical one.
     */
    public function testCheckPassesWhenProtectionIsDisabled(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check(false, 'post'));
        $this->assertTrue($guard->check(false, 'get'));
        $this->assertTrue($guard->check(false, 'put'));
        $this->assertTrue($guard->check(false, ''));
    }

    /**
     * 2) With CSRF protection enabled and an HTTP method other than "post"
     * or "get", the check fails closed - without ever needing to consult
     * the live session/CSRF token state.
     */
    public function testCheckFailsClosedForUnrecognizedMethod(): void
    {
        $guard = $this->guard();

        $this->assertFalse($guard->check(true, 'put'));
        $this->assertFalse($guard->check(true, 'delete'));
        $this->assertFalse($guard->check(true, ''));
    }
}