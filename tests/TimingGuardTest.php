<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use Exception;
use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\FormFieldHelper;
use FrontendForms\FormHelper;
use FrontendForms\TimingGuard;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for TimingGuard.
 *
 * The submitted load-time value has to be encrypted the same way the real
 * form would encrypt it (FormHelper::encryptDecrypt(), a static, deterministic
 * AES routine with a fixed key - not environment-dependent), so tests
 * build valid encrypted values with a known "seconds ago" offset rather
 * than using arbitrary strings.
 */
final class TimingGuardTest extends TestCase
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
    private function encryptedTimeAgo(int $secondsAgo): string
    {
        return FormHelper::encryptDecrypt((string) (time() - $secondsAgo), 'encrypt');
    }

    private function guard(WireInputData $input, Form $form): TimingGuard
    {
        return new TimingGuard($input, $form, new Alert(), new FormFieldHelper($input));
    }

    // --- check() ---

    /**
     * 1) With both min and max time disabled (0), the check passes
     * immediately without even looking for the load-time field.
     */
    public function testPassesImmediatelyWhenBothTimesDisabled(): void
    {
        $form = new Form('myform');
        $form->setMinTime(0);
        $form->setMaxTime(0);

        $guard = $this->guard($this->inputData([]), $form);

        $this->assertTrue($guard->check([]));
    }

    /**
     * 2) With a min/max time configured but the load-time field missing
     * from the submission entirely, an Exception is thrown.
     */
    public function testThrowsExceptionWhenLoadTimeFieldIsMissing(): void
    {
        $form = new Form('myform');
        $form->setMinTime(3);

        $guard = $this->guard($this->inputData([]), $form);

        $this->expectException(Exception::class);

        $guard->check([]);
    }

    /**
     * 3) A submission that arrives faster than the configured minimum wait
     * time is rejected as bot-like behaviour.
     */
    public function testRejectsSubmissionFasterThanMinTime(): void
    {
        $form = new Form('myform');
        $form->setMinTime(10);
        $loadTimeField = $form->getID() . '-load_time';

        $guard = $this->guard(
            $this->inputData([$loadTimeField => $this->encryptedTimeAgo(2)]),
            $form
        );

        $this->assertFalse($guard->check([]));
    }

    /**
     * 4) A submission that arrives after the configured minimum wait time
     * (and within any configured maximum) passes.
     */
    public function testPassesSubmissionWithinTimeWindow(): void
    {
        $form = new Form('myform');
        $form->setMinTime(2);
        $form->setMaxTime(0);
        $loadTimeField = $form->getID() . '-load_time';

        $guard = $this->guard(
            $this->inputData([$loadTimeField => $this->encryptedTimeAgo(10)]),
            $form
        );

        $this->assertTrue($guard->check([]));
    }

    /**
     * 5) A submission that arrives after the configured maximum time is
     * rejected, and the session's "blocked" value is set to a
     * human-readable submission time.
     */
    public function testRejectsSubmissionSlowerThanMaxTimeAndSetsBlockedSession(): void
    {
        $form = new Form('myform');
        $form->setMinTime(0);
        $form->setMaxTime(5);
        $loadTimeField = $form->getID() . '-load_time';

        $guard = $this->guard(
            $this->inputData([$loadTimeField => $this->encryptedTimeAgo(30)]),
            $form
        );

        $this->assertFalse($guard->check([]));
        $this->assertNotNull(\ProcessWire\wire('session')->get('blocked'));

        \ProcessWire\wire('session')->remove('blocked');
    }

    // --- secondsToReadable() ---

    /**
     * 6) A duration of exactly one unit uses the singular form.
     */
    public function testSecondsToReadableUsesSingularForOneUnit(): void
    {
        $guard = $this->guard($this->inputData([]), new Form('myform'));

        $this->assertSame('1 second', $guard->secondsToReadable(1));
    }

    /**
     * 7) A duration of more than one second uses the plural form.
     */
    public function testSecondsToReadableUsesPluralForMultipleUnits(): void
    {
        $guard = $this->guard($this->inputData([]), new Form('myform'));

        $this->assertSame('5 seconds', $guard->secondsToReadable(5));
    }

    /**
     * 8) With multiple non-zero units, they are joined with "and" before
     * the last one - confirmed standalone before writing this assertion:
     * 3665 seconds = 1 hour, 1 minute, 5 seconds.
     */
    public function testSecondsToReadableJoinsMultipleUnitsWithAnd(): void
    {
        $guard = $this->guard($this->inputData([]), new Form('myform'));

        $this->assertSame('1 hour 1 minute and 5 seconds', $guard->secondsToReadable(3665));
    }

    /**
     * 9) A duration of zero seconds produces an empty string (no unit is
     * non-zero, so nothing gets added to $parts).
     */
    public function testSecondsToReadableWithZeroReturnsEmptyString(): void
    {
        $guard = $this->guard($this->inputData([]), new Form('myform'));

        $this->assertSame('', $guard->secondsToReadable(0));
    }

    /**
     * 10) Exactly two non-zero units are joined with "and", without a
     * comma.
     */
    public function testSecondsToReadableWithTwoUnitsJoinsWithAndOnly(): void
    {
        $guard = $this->guard($this->inputData([]), new Form('myform'));

        // 125 seconds = 2 minutes, 5 seconds
        $this->assertSame('2 minutes and 5 seconds', $guard->secondsToReadable(125));
    }
}