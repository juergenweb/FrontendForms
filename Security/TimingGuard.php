<?php

declare(strict_types=1);

namespace FrontendForms;

use Exception;
use ProcessWire\WireException;
use ProcessWire\WireInputData;

/**
 * Guard that checks whether a form's submission time is within the
 * configured min/max time window (anti-bot timing protection).
 */
class TimingGuard extends BaseGuard
{
    /**
     * @param WireInputData   $input       Submitted form input data.
     * @param Form            $form        The form instance this guard checks.
     * @param Alert           $alert       Alert instance used to display security-related messages.
     * @param FormFieldHelper $fieldHelper Helper used to filter real input fields off the form.
     */
    public function __construct(
        WireInputData $input,
        Form $form,
        Alert $alert,
        private readonly FormFieldHelper $fieldHelper
    ) {
        parent::__construct($input, $form, $alert);
    }

    /**
     * Check whether the submission time is within the configured min/max
     * time window.
     *
     * The minimum wait time is dynamically reduced based on the number of
     * required fields that are already filled in, since a partially
     * completed form on a repeated submission attempt needs less time to
     * finish than a completely empty one.
     *
     * @param array $realFormElements All elements currently registered on the form.
     *
     * @return bool True if the submission time is within the allowed window.
     *
     * @throws Exception If the hidden load-time field is missing from the form.
     * @throws WireException
     */
    public function check(array $realFormElements): bool
    {
        if (!$this->form->getMinTime() && !$this->form->getMaxTime()) {
            return true;
        }

        $formID = $this->form->getID();
        $loadtimefieldName = $formID . '-load_time';
        $start_time = $this->input->get($loadtimefieldName);

        $requiredFields = [];
        foreach ($this->fieldHelper->getRealInputFields($realFormElements) as $field) {
            $field->setAttribute('value', $this->wire('input')->post($field->getAttribute('name')));
            if ($field->hasRule('required')) {
                $requiredFields[$field->getAttribute('name')] = $this->wire('input')->post($field->getAttribute('name'));
            }
        }

        // Count only fields that are truly empty (null/''), so a legitimate
        // value of "0" is still counted as filled.
        $filledCount = count(array_filter(
            $requiredFields,
            fn ($v) => $v !== null && $v !== ''
        ));
        $totalRequired = count($requiredFields);

        if ($filledCount && $totalRequired > 0 && !is_null($this->wire('session')->submitted)) {
            $newMinTime = (int) max(1, round(
                $this->form->getMinTime() * ($totalRequired - $filledCount) / $totalRequired
            ));
            $this->form->setMinTime($newMinTime);
        }

        if (!$start_time) {
            throw new Exception(sprintf('Inputfield %s is not present in the form.', $loadtimefieldName), 1);
        }

        $start_time = (int) $this->wire('sanitizer')->string(FormHelper::encryptDecrypt($start_time, 'decrypt'));
        $diff = time() - $start_time;
        $submitTime = $this->secondsToReadable($diff);

        if ($this->form->getMinTime() && ($diff < $this->form->getMinTime())) {
            $secondsLeft = $this->_('seconds left'); // plural
            $secondLeft = $this->_('second left'); // singular
            $text = sprintf(
                $this->_('You have submitted the form within %s. This seems pretty fast for a human. Your behavior is more similar to a Spam bot. Please wait at least %s until you submit the form once more.'),
                $submitTime,
                '<span id="' . $formID . '-minTime" data-time="' . $this->form->getMinTime() . '" data-unit="' . $secondsLeft . ';' . $secondLeft . '">'
                . $this->secondsToReadable($this->form->getMinTime())
                . '</span><div id="' . $formID . '-timecounter"></div>'
            );
            $this->alert->setCSSClass('alert_warningClass');
            $this->alert->setAttribute('id', $formID . '-ff-time-alert');
            $this->alert->setAttribute('data-submittime', $formID);
            $this->alert->setText($text);

            return false;
        }

        // too slow
        if ($this->form->getMaxTime() && ($diff > $this->form->getMaxTime())) {
            $text = sprintf(
                $this->_('You have submitted the form after %s. This seems pretty slow for a human. Your behavior is more similar to a Spam bot. Please submit the form within %s the next time. You are blocked now and you have to close the browser to unlock, open it again and visit this page once more.'),
                $submitTime,
                $this->secondsToReadable($this->form->getMaxTime())
            );
            $this->alert->setText($text);
            // set session for blocked value is the submission time
            $this->wire('session')->set('blocked', $submitTime);

            return false;
        }

        return true;
    }

    /**
     * Convert a number of seconds into a human-readable duration string
     * (e.g. "2 hours and 5 minutes").
     *
     * @param int $ss Number of seconds to convert.
     *
     * @return string Human-readable duration.
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
}