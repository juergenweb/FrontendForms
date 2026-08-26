<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\AttemptGuard;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for AttemptGuard.
 *
 * wire('session')->attempts is set directly for each test to mirror the
 * real session state, even though AttemptGuard itself now only reads the
 * attempts count via the $attempts parameter (matching the real caller in
 * Form.php, which always passes wire('session')->attempts as the
 * argument) - this keeps the tests realistic and documents what the live
 * session would actually contain at that point.
 *
 * Note on writeLogFailedAttempts(): it constructs its own `new WireLog()`
 * internally rather than receiving one injected, so there's no way to
 * intercept/verify the actual log write from a plain unit test without
 * reading real log files (fragile, environment-dependent). The tests below
 * verify the behavioural OUTCOME (check() returning false, and reaching a
 * live-testable state where getLogFailedAttempts() is actually consulted -
 * which is the specific thing the earlier bugfix restored), not the log
 * write itself.
 */
final class AttemptGuardTest extends TestCase
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
    protected function tearDown(): void
    {
        \ProcessWire\wire('session')->remove('attempts');
        \ProcessWire\wire('session')->remove('blocked');
        parent::tearDown();
    }

    private function guard(): AttemptGuard
    {
        return new AttemptGuard($this->inputData([]), new Form('myform'), new Alert());
    }

    /**
     * 1) With $attempts sanitizing to 0, the check is skipped entirely and
     * always passes, regardless of the max-attempts configuration.
     */
    public function testLogZeroSkipsCheckEntirely(): void
    {
        $guard = $this->guard();

        $this->assertTrue($guard->check(0));
    }

    /**
     * 2) With the number of failed attempts still below the configured
     * maximum, the check passes.
     */
    public function testPassesWhenBelowMaxAttempts(): void
    {
        $form = new Form('myform');
        $form->setMaxAttempts(5);
        \ProcessWire\wire('session')->set('attempts', 2);

        $guard = new AttemptGuard($this->inputData([]), $form, new Alert());

        $this->assertTrue($guard->check(2));
    }

    /**
     * 3) Once the number of failed attempts reaches the configured
     * maximum, the check fails.
     */
    public function testFailsWhenMaxAttemptsReached(): void
    {
        $form = new Form('myform');
        $form->setMaxAttempts(5);
        \ProcessWire\wire('session')->set('attempts', 5);

        $guard = new AttemptGuard($this->inputData([]), $form, new Alert());

        $this->assertFalse($guard->check(5));
    }

    /**
     * 4) REGRESSION-RELATED TEST: at the attempts limit, with logging
     * explicitly disabled via Form::logFailedAttempts(false), the check
     * still correctly fails (the logging setting only controls whether a
     * log entry is written, not whether the limit itself is enforced).
     * This exercises Form::getLogFailedAttempts() (added as part of the
     * bugfix) without needing to inspect real log file output.
     */
    public function testFailsAtLimitRegardlessOfLoggingSetting(): void
    {
        $form = new Form('myform');
        $form->setMaxAttempts(3);
        $form->logFailedAttempts(false);
        \ProcessWire\wire('session')->set('attempts', 3);

        $guard = new AttemptGuard($this->inputData([]), $form, new Alert());

        $this->assertFalse($guard->check(3));
    }

    /**
     * 5) Once the limit is reached, "blocked" is set in the session.
     */
    public function testSetsBlockedSessionValueWhenLimitReached(): void
    {
        $form = new Form('myform');
        $form->setMaxAttempts(1);
        \ProcessWire\wire('session')->set('attempts', 1);

        $guard = new AttemptGuard($this->inputData([]), $form, new Alert());
        $guard->check(1);

        $this->assertSame('maxAttempts', \ProcessWire\wire('session')->get('blocked'));
    }

    /**
     * 6) REGRESSION TEST for the fixed bug: the $attempts parameter's
     * actual VALUE is what's compared against the configured maximum, not
     * just its truthiness. Before the fix, the parameter (then misleadingly
     * named $log) was only checked for truthiness, while the real
     * comparison silently re-read wire('session')->attempts independently
     * - meaning a caller passing a different value than the live session
     * state would have gotten a result based on the session, not on what
     * was actually passed in. Here, session and parameter deliberately
     * differ (session has fewer attempts than the parameter claims), and
     * the parameter's own value must be what determines the outcome.
     */
    public function testUsesTheParameterValueForComparisonNotAnIndependentSessionRead(): void
    {
        $form = new Form('myform');
        $form->setMaxAttempts(5);
        \ProcessWire\wire('session')->set('attempts', 1); // deliberately different from the parameter below

        $guard = new AttemptGuard($this->inputData([]), $form, new Alert());

        // Parameter claims the limit is already reached, even though the
        // (irrelevant, independently-set) session value would suggest
        // otherwise - the parameter must win.
        $this->assertFalse($guard->check(5));
    }
}