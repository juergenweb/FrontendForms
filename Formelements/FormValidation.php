<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Validation class to validate user inputs
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FormValidation.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use Exception;
use ProcessWire\Wire;
use ProcessWire\WireException;
use ProcessWire\WireInputData;
use ProcessWire\WireLog;
use ProcessWire\WirePermissionException;
use function ProcessWire\_n;


class FormValidation extends Tag
{
    protected WireInputData $input; //WireInputData object
    protected Form $form; // Form object
    protected Alert $alert; // Alert object

    /**
     * @param WireInputData $input
     * @param Form $form
     * @param Alert $alert
     */
    public function __construct(WireInputData $input, Form $form, Alert $alert)
    {
        parent::__construct();
        $this->input = $input;
        $this->form = $form;
        $this->alert = $alert;
    }

    /**
     * Check if exactly this form was submitted by checking the form id against the hidden field form_id
     * @return bool - true, if this form was submitted, otherwise false
     */
    public function thisFormSubmitted(): bool
    {
        $name = $this->form->getID() . '-form_id';
        return $this->input->$name === $this->form->getID();
    }

    /**
     * Check if min time and max time limits are lower/higher than submission time
     * Please keep in mind: This function calculates a new min time after each submission depending on the number of empty required fields left
     *
     * @return boolean - true, if everything is ok
     * @throws Exception
     * @throws WireException
     */
    public function checkTimeDiff(array $realFormElements): bool
    {
        if (!$this->form->getMinTime() && !$this->form->getMaxTime()) {
            return true;
        }

        $formID = $this->form->getID();
        $loadtimefieldName = $formID . '-load_time';
        $start_time = $this->input->get($loadtimefieldName);

        $requiredFields = [];
        foreach ($this->getRealInputFields($realFormElements) as $field) {
            $field->setAttribute('value', $this->wire('input')->post($field->getAttribute('name')));
            if ($field->hasRule('required')) {
                $requiredFields[$field->getAttribute('name')] = $this->wire('input')->post($field->getAttribute('name'));
            }
        }

        $filledCount = count(array_filter($requiredFields));
        $totalRequired = count($requiredFields);

        if ($filledCount && $totalRequired > 0 && !is_null($this->wire('session')->submitted)) {
            $newMinTime = (int)max(1, round(
                $this->form->getMinTime() * ($totalRequired - $filledCount) / $totalRequired
            ));
            $this->form->setMinTime($newMinTime);
        }

        if (!$start_time) {
            throw new Exception(sprintf('Inputfield %s is not present in the form.', $loadtimefieldName), 1);
        }

        $start_time = (int)$this->wire('sanitizer')->string(Form::encryptDecrypt($start_time, 'decrypt'));
        $diff = time() - $start_time;
        $submitTime = $this->secondsToReadable($diff);

        if ($this->form->getMinTime() && ($diff < $this->form->getMinTime())) {
            $secondsLeft = $this->_('seconds left'); //plural
            $secondLeft = $this->_('second left'); // singular
            $text = sprintf($this->_('You have submitted the form within %s. This seems pretty fast for a human. Your behavior is more similar to a Spam bot. Please wait at least %s until you submit the form once more.'),
                    $submitTime,
                    '<span id="' . $formID . '-minTime" data-time="' . $this->form->getMinTime() . '" data-unit="' . $secondsLeft . ';' . $secondLeft . '">' . $this->secondsToReadable($this->form->getMinTime())) . '</span><div id="' . $formID . '-timecounter"></div>';
            $this->alert->setCSSClass('alert_warningClass');
            $this->alert->setAttribute('id', $formID . '-ff-time-alert');
            $this->alert->setAttribute('data-submittime', $formID);
            $this->alert->setText($text);

            return false;
        }

        //too slow
        if ($this->form->getMaxTime() && ($diff > $this->form->getMaxTime())) {
            $text = sprintf($this->_('You have submitted the form after %s. This seems pretty slow for a human. Your behavior is more similar to a Spam bot. Please submit the form within %s the next time. You are blocked now and you have to close the browser to unlock, open it again and visit this page once more.'),
                $submitTime, $this->secondsToReadable($this->form->getMaxTime()));
            $this->alert->setText($text);
            $this->wire('session')->set('blocked',
                $submitTime); // set session for blocked value is the submission time
            return false;
        }

        return true;
    }

    /**
     * Convert seconds to a readable human time string
     * @param int $ss
     * @return string
     */
    public function secondsToReadable(int $ss): string
    {
        $units = [
            'month' => [floor($ss / 2592000), $this->_('month'), $this->_('months')],
            'week' => [floor(($ss % 2592000) / 604800), $this->_('week'), $this->_('weeks')],
            'day' => [floor(($ss % 604800) / 86400), $this->_('day'), $this->_('days')],
            'hour' => [floor(($ss % 86400) / 3600), $this->_('hour'), $this->_('hours')],
            'minute' => [floor(($ss % 3600) / 60), $this->_('minute'), $this->_('minutes')],
            'second' => [$ss % 60, $this->_('second'), $this->_('seconds')],
        ];

        $parts = [];
        foreach ($units as [$value, $singular, $plural]) {
            if ($value != 0) {
                $parts[] = $value . ' ' . $this->_n($singular, $plural, $value);
            }
        }

        if (count($parts) > 1) {
            array_splice($parts, count($parts) - 1, 0, $this->_('and'));
        }

        return implode(' ', $parts);
    }

    /**
     * Write a log if failed login attempts have reached the max number of attempts
     * @return void
     * @throws WireException
     */
    protected function writeLogFailedAttempts(): void
    {
        (new WireLog())->save('failed-attempts-frontendforms', json_encode([
            'FormID' => $this->form->getID(),
            'IP' => $this->wire('session')->getIP(),
        ]));
    }

    /**
     * Check if max attempts are reached or not - true or false
     * Depending on the result, the form will be displayed or not
     * @param int|string|bool $log
     * @return boolean -> true if attempts limit is not reached, otherwise false
     * @throws WireException
     */
    public function checkMaxAttempts(int|string|bool|null $log): bool
    {
        if (!$this->wire('sanitizer')->int($log)) {
            return true;
        }

        if (($this->form->getMaxAttempts() - $this->wire('session')->attempts) > 0) {
            return true;
        }

        if ($log && $this->frontendforms['input_logFailedAttempts']) {
            $this->writeLogFailedAttempts();
        }

        $this->wire('session')->set('blocked', 'maxAttempts');
        return false;
    }

    /**
     * Check if form is submitted twice after successful validation
     * @param Form $form
     * @param bool $useDoubleFormSubmissionCheck
     * @return boolean (true -> form was not submitted twice)
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function checkDoubleFormSubmission(Form $form, int|string|bool $useDoubleFormSubmissionCheck): bool
    {
        // if check is disabled, return true and go on...
        if (!$useDoubleFormSubmissionCheck) {
            return true;
        }

        $formID = $this->form->getID();

        // assign submitted **secretFormValue** from your form to a local variable
        $tokenfieldName = $formID . '-doubleSubmission_token';
        $secretFormValue = filter_var($this->input->$tokenfieldName ?? '', FILTER_UNSAFE_RAW);

        // check if the value is present in the **secretFormValue** variable
        if ($secretFormValue === '') {
            throw new Exception('Token value to prevent double form submission is missing', 1);
        }

        // check if both values are the same
        if ($this->wire('session')->get('doubleSubmission-' . $formID) == $secretFormValue) {
            return true;
        }

        // redirect to the same page
        $segments = $this->wire('input')->urlSegmentStr(true) ?? '';
        $this->wire('session')->redirect($this->wire('page')->url . $segments);

        return false;

    }

    /**
     * Check for CSRF-Attack
     * True: If CSRF-Protection is disabled or a valid CSRF-Token is present
     * False: If CSRF-Protection is enabled and the CSRF-Token is not valid
     * @param bool $useCSRFProtection
     * @param string $method
     * @return bool
     * @throws \ProcessWire\WireException
     */
    public function checkCSRFAttack(bool $useCSRFProtection, string $method): bool
    {
        if (!$useCSRFProtection) {
            return true;
        }

        // sanitize method name to be all lower
        return match (strtolower($method)) {
            'post' => $this->wire('session')->CSRF->hasValidToken(),
            'get' => $this->wire('input')->get($this->wire('session')->CSRF->getTokenName())
                == $this->wire('session')->CSRF->getTokenValue(),
            default => true,
        };

    }

    /**
     * Cleans all input fields on a form from none input fields like Button, text,...
     * @param array $formElements
     * @return array
     */
    public function getRealInputFields(array $formElements): array
    {
        return array_values(array_filter(
            $formElements,
            fn($element) => is_subclass_of($element, Inputfields::class)
        ));
    }

    /**
     * Sanitize post values depending on the sanitizer set
     * @param $element
     * @return string|array|int|float|null
     * @throws WireException
     */
    public function sanitizePostValue($element): string|array|int|null|float
    {
        $fieldname = $element->getAttribute('name');

        if (!array_key_exists($fieldname, $this->input->getArray())) {
            return null;
        }

        $value = $this->input->get($fieldname);

        if (in_array($element->className(), Tag::MULTIVALCLASSES)) {
            array_walk($value, function (&$v) {
                $v = $this->wire('sanitizer')->string($v);
            });
        }

        if ($value) {
            $element->setAttribute('value', $value);
        }

        return $value;
    }

}
