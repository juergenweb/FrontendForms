<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use Exception;
use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\SubmitGuard;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for SubmitGuard.
 *
 * The "token mismatch" path calls wire('session')->redirect(...), which
 * would actually attempt to send a real HTTP redirect (and likely exit())
 * if triggered for real - unsafe to exercise in a test. Only the three
 * paths that are guaranteed to return/throw BEFORE ever reaching that call
 * are covered here: the disabled-check early return, the missing-token
 * exception, and the matching-token success case (which needs a real,
 * live session value to compare against, set directly for the test).
 */
final class SubmitGuardTest extends TestCase
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
    private function guard(?WireInputData $input = null): SubmitGuard
    {
        return new SubmitGuard($input ?? $this->inputData([]), new Form('myform'), new Alert());
    }

    /**
     * 1) With the double-submission check disabled, the guard always
     * passes without even looking for a token.
     */
    public function testDisabledCheckAlwaysPasses(): void
    {
        $guard = $this->guard();
        $form = new Form('myform');

        $this->assertTrue($guard->check($form, false));
    }

    /**
     * 2) With the check enabled but no token submitted at all, an
     * Exception is thrown (the hidden token field is expected to always be
     * present on a form built by this module).
     */
    public function testThrowsExceptionWhenTokenIsMissing(): void
    {
        $guard = $this->guard();
        $form = new Form('myform');

        $this->expectException(Exception::class);

        $guard->check($form, true);
    }

    /**
     * 3) With the check enabled and the submitted token matching the
     * value stored in the session, the check passes.
     */
    public function testPassesWhenSubmittedTokenMatchesSession(): void
    {
        $form = new Form('myform');
        $tokenFieldName = $form->getID() . '-doubleSubmission_token';
        \ProcessWire\wire('session')->set('doubleSubmission-' . $form->getID(), 'secret-token-value');

        $guard = $this->guard($this->inputData([$tokenFieldName => 'secret-token-value']));

        $this->assertTrue($guard->check($form, true));

        // clean up so this doesn't leak into other tests
        \ProcessWire\wire('session')->remove('doubleSubmission-' . $form->getID());
    }
}