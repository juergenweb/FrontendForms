<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\FormSecurity;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for FormSecurity.
 *
 * FormSecurity is a thin facade that delegates almost everything to
 * already-tested guard classes (TimingGuardTest, AttemptGuardTest,
 * SubmitGuardTest, CSRFGuardTest, FormFieldHelperTest all cover the actual
 * check logic in depth) - this file focuses on what the facade itself
 * adds: correct delegation/wiring, and thisFormSubmitted(), the one piece
 * of genuinely new logic here.
 */
final class FormSecurityTest extends TestCase
{
    /**
     * WireInputData's constructor takes its array argument by reference,
     * so it can't be passed a literal array expression directly at the
     * call site (see the lesson learned in the Security/ guard tests
     * earlier in this session).
     */
    private function inputData(array $data = []): WireInputData
    {
        return new WireInputData($data);
    }

    // --- thisFormSubmitted() ---

    /**
     * 1) When the submitted "form_id" field matches this form's own ID,
     * the form is recognized as having been submitted.
     */
    public function testThisFormSubmittedIsTrueWhenFormIdMatches(): void
    {
        $form = new Form('myform');
        $input = $this->inputData([$form->getID() . '-form_id' => $form->getID()]);
        $security = new FormSecurity($input, $form, new Alert());

        $this->assertTrue($security->thisFormSubmitted());
    }

    /**
     * 2) With no "form_id" field submitted at all (e.g. a fresh page
     * load), the form is not considered submitted.
     */
    public function testThisFormSubmittedIsFalseWhenFieldMissing(): void
    {
        $form = new Form('myform');
        $input = $this->inputData([]);
        $security = new FormSecurity($input, $form, new Alert());

        $this->assertFalse($security->thisFormSubmitted());
    }

    /**
     * 3) With a "form_id" value that belongs to a DIFFERENT form (e.g.
     * another form on the same page was submitted instead), this form is
     * not considered submitted.
     */
    public function testThisFormSubmittedIsFalseForDifferentFormId(): void
    {
        $form = new Form('myform');
        $input = $this->inputData([$form->getID() . '-form_id' => 'some-other-form']);
        $security = new FormSecurity($input, $form, new Alert());

        $this->assertFalse($security->thisFormSubmitted());
    }

    // --- delegation to guard classes ---

    /**
     * 4) checkMaxAttempts() delegates to AttemptGuard - with $log
     * sanitizing to 0, the check is skipped entirely and always passes
     * (already established in AttemptGuardTest; this just confirms the
     * facade actually reaches it).
     */
    public function testCheckMaxAttemptsDelegatesToAttemptGuard(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertTrue($security->checkMaxAttempts(0));
    }

    /**
     * 5) checkCSRFAttack() delegates to CSRFGuard - with protection
     * disabled, the check always passes regardless of method.
     */
    public function testCheckCSRFAttackDelegatesToCSRFGuard(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertTrue($security->checkCSRFAttack(false, 'put'));
    }

    /**
     * 6) checkDoubleFormSubmission() delegates to SubmitGuard - with the
     * check disabled, it always passes without needing a token.
     */
    public function testCheckDoubleFormSubmissionDelegatesToSubmitGuard(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertTrue($security->checkDoubleFormSubmission($form, false));
    }

    /**
     * 7) checkTimeDiff() delegates to TimingGuard - with both min/max time
     * disabled, the check always passes immediately.
     */
    public function testCheckTimeDiffDelegatesToTimingGuard(): void
    {
        $form = new Form('myform');
        $form->setMinTime(0);
        $form->setMaxTime(0);
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertTrue($security->checkTimeDiff([]));
    }

    /**
     * 8) secondsToReadable() delegates to TimingGuard's implementation.
     */
    public function testSecondsToReadableDelegatesToTimingGuard(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertSame('5 seconds', $security->secondsToReadable(5));
    }

    /**
     * 9) getRealInputFields() delegates to FormFieldHelper.
     */
    public function testGetRealInputFieldsDelegatesToFieldHelper(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());

        $this->assertSame([], $security->getRealInputFields([]));
    }

    /**
     * 10) sanitizePostValue() delegates to FormFieldHelper - a field with
     * no submitted value returns null.
     */
    public function testSanitizePostValueDelegatesToFieldHelper(): void
    {
        $form = new Form('myform');
        $security = new FormSecurity($this->inputData([]), $form, new Alert());
        $field = new InputText('myfield');

        $this->assertNull($security->sanitizePostValue($field));
    }
}
