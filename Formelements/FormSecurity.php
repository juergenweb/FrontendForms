<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Security class to prevent SPAM and other attacks against forms
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FormValidation.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 * Split into guard classes via Claude AI 13.07.26
 */

use Exception;
use ProcessWire\Wire;
use ProcessWire\WireException;
use ProcessWire\WireInputData;
use ProcessWire\WirePermissionException;

/**
 * Facade that orchestrates all form security checks by delegating to
 * focused guard classes (timing, attempts, double-submission, CSRF) and
 * to the FormFieldHelper for field-related data processing.
 *
 * The public API is unchanged from the original monolithic implementation,
 * so existing callers do not need to change.
 */
class FormSecurity extends Wire
{
    /**
     * @var WireInputData Submitted form input data.
     */
    protected WireInputData $input;

    /**
     * @var Form The form instance this security check belongs to.
     */
    protected Form $form;

    /**
     * @var Alert Alert instance used to display security-related messages.
     */
    protected Alert $alert;

    /**
     * @var FormFieldHelper Helper for filtering input fields and sanitizing POST values.
     */
    private FormFieldHelper $fieldHelper;

    /**
     * @var TimingGuard Guard for anti-bot submission timing checks.
     */
    private TimingGuard $timingGuard;

    /**
     * @var AttemptGuard Guard for maximum failed attempts checks.
     */
    private AttemptGuard $attemptGuard;

    /**
     * @var SubmitGuard Guard for double form submission checks.
     */
    private SubmitGuard $submitGuard;

    /**
     * @var CSRFGuard Guard for CSRF attack checks.
     */
    private CSRFGuard $csrfGuard;

    /**
     * @param WireInputData $input Submitted form input data.
     * @param Form          $form  The form instance this security check belongs to.
     * @param Alert         $alert Alert instance used to display security-related messages.
     */
    public function __construct(WireInputData $input, Form $form, Alert $alert)
    {
        parent::__construct();
        $this->input = $input;
        $this->form = $form;
        $this->alert = $alert;

        $this->fieldHelper = new FormFieldHelper($input);
        $this->timingGuard = new TimingGuard($input, $form, $alert, $this->fieldHelper);
        $this->attemptGuard = new AttemptGuard($input, $form, $alert);
        $this->submitGuard = new SubmitGuard($input, $form, $alert);
        $this->csrfGuard = new CSRFGuard($input, $form, $alert);
    }

    /**
     * Check whether exactly this form was submitted, by comparing the form's
     * ID against the value of its hidden "form_id" field.
     *
     * @return bool True if this specific form was submitted, false otherwise.
     */
    public function thisFormSubmitted(): bool
    {
        $name = $this->form->getID() . '-form_id';

        return $this->input->$name === $this->form->getID();
    }

    /**
     * Check whether the submission time is within the configured min/max
     * time window. Delegates to TimingGuard.
     *
     * @param array $realFormElements All elements currently registered on the form.
     *
     * @return bool True if the submission time is within the allowed window.
     *
     * @throws Exception If the hidden load-time field is missing from the form.
     * @throws WireException
     */
    public function checkTimeDiff(array $realFormElements): bool
    {
        return $this->timingGuard->check($realFormElements);
    }

    /**
     * Convert a number of seconds into a human-readable duration string.
     * Delegates to TimingGuard.
     *
     * @param int $ss Number of seconds to convert.
     *
     * @return string Human-readable duration.
     */
    public function secondsToReadable(int $ss): string
    {
        return $this->timingGuard->secondsToReadable($ss);
    }

    /**
     * Check whether the maximum number of allowed attempts has been reached.
     * Delegates to AttemptGuard.
     *
     * @param int|string|bool|null $attempts The current number of failed
     *                                        attempts recorded in the session.
     *
     * @return bool True if the attempts limit has not been reached, false otherwise.
     *
     * @throws WireException
     */
    public function checkMaxAttempts(int|string|bool|null $attempts): bool
    {
        return $this->attemptGuard->check($attempts);
    }

    /**
     * Check whether the given form has already been submitted once.
     * Delegates to SubmitGuard.
     *
     * @param Form            $form                        The form instance to check.
     * @param int|string|bool $useDoubleFormSubmissionCheck Whether this check is enabled.
     *
     * @return bool True if the form was not submitted twice.
     *
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception If the double-submission token field is missing.
     */
    public function checkDoubleFormSubmission(Form $form, int|string|bool $useDoubleFormSubmissionCheck): bool
    {
        return $this->submitGuard->check($form, $useDoubleFormSubmissionCheck);
    }

    /**
     * Check for a CSRF attack. Delegates to CSRFGuard.
     *
     * @param bool   $useCSRFProtection Whether CSRF protection is enabled.
     * @param string $method            The HTTP method used for the submission.
     *
     * @return bool True if CSRF protection is disabled or a valid token is present.
     *
     * @throws WireException
     */
    public function checkCSRFAttack(bool $useCSRFProtection, string $method): bool
    {
        return $this->csrfGuard->check($useCSRFProtection, $method);
    }

    /**
     * Filter out all non-input elements (buttons, plain text, ...) from a
     * list of form elements. Delegates to FormFieldHelper.
     *
     * @param array $formElements All elements currently registered on the form.
     *
     * @return array Only the elements that represent actual input fields.
     */
    public function getRealInputFields(array $formElements): array
    {
        return $this->fieldHelper->getRealInputFields($formElements);
    }

    /**
     * Sanitize a submitted POST value for the given form element and write
     * it back into the element's "value" attribute. Delegates to FormFieldHelper.
     *
     * @param mixed $element The form element to sanitize the submitted value for.
     *
     * @return string|array|int|float|null The sanitized value, or null if not submitted.
     *
     * @throws WireException
     */
    public function sanitizePostValue($element): string|array|int|null|float
    {
        return $this->fieldHelper->sanitizePostValue($element);
    }
}