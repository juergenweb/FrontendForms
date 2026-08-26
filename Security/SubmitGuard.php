<?php

declare(strict_types=1);

namespace FrontendForms;

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

/**
 * Guard that checks whether a form has already been submitted once
 * (double form submission protection).
 */
class SubmitGuard extends BaseGuard
{
    /**
     * Check whether the given form has already been submitted once.
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
    public function check(Form $form, int|string|bool $useDoubleFormSubmissionCheck): bool
    {
        // if check is disabled, return true and go on...
        if (!$useDoubleFormSubmissionCheck) {
            return true;
        }

        $formID = $form->getID();

        // assign submitted **secretFormValue** from your form to a local variable
        $tokenfieldName = $formID . '-doubleSubmission_token';
        $secretFormValue = filter_var($this->input->$tokenfieldName ?? '', FILTER_UNSAFE_RAW);

        // check if the value is present in the **secretFormValue** variable
        if ($secretFormValue === '') {
            throw new Exception('Token value to prevent double form submission is missing', 1);
        }

        // check if both values are the same
        if ($this->wire('session')->get('doubleSubmission-' . $formID) === $secretFormValue) {
            return true;
        }

        // redirect to the same page
        $segments = $this->wire('input')->urlSegmentStr(true) ?? '';
        $this->wire('session')->redirect($this->wire('page')->url . $segments);

        return false;
    }
}