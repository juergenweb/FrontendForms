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
     */

    use Exception;
    use ProcessWire\Wire;
    use ProcessWire\WireException;
    use ProcessWire\WireInputData as WireInputData;
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
            $name = $this->form->getID() . '-form_id'; // hiddenfield value
            return ($this->input->$name) && ($this->input->$name == $this->form->getID());
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
          
            if (($this->form->getMinTime()) || ($this->form->getMaxTime())) {
                // grab the page_load value

                $loadtimefieldName = $this->form->getID() . '-load_time';
                $start_time = $this->input->get($loadtimefieldName); // encrypted string

                // Calculate the minTime depending on the field that have to be filled out and has no value
                $numberOfInputfields = $this->getRealInputFields($realFormElements);
                $requiredFields = [];
                foreach ($numberOfInputfields as $field) {
                    // populate the values back to the fields
                    $field->setAttribute('value', $this->wire('input')->post($field->getAttribute('name')));
                    // check if field is required
                    if ($field->hasRule('required')) {
                        $requiredFields[$field->getAttribute('name')] = $this->wire('input')->post($field->getAttribute('name'));
                    }
                }

                $numberOfRequiredFieldWithValues = count(array_filter($requiredFields));
                // calculate new min time depending on the required fields, which has no value at the moment
                // this condition runs only after the second and further submission attempts - not on the firs attempt
                if (($numberOfRequiredFieldWithValues) && (!is_null($this->wire('session')->submitted))) {
                    $newMinTime = (round(($this->form->getMinTime() * (count($requiredFields) - $numberOfRequiredFieldWithValues)) / count($requiredFields)));
                    if($newMinTime < 1) $newMinTime = 1; // set it to at least 1 to prevent error if newMinTime would be rounded to 0
                    $this->form->setMinTime((int)$newMinTime);
                }

                // check if the input field load_time is present in the form
                if ((($this->form->getMinTime()) || ($this->form->getMaxTime())) && (!$start_time)) {
                    $message = sprintf('Inputfield %s is not present in the form.', $loadtimefieldName);
                    throw new Exception($message, 1);
                } else {
                    //get the timestamp value and decrypt and sanitize it
                    $start_time = Form::encryptDecrypt($start_time, 'decrypt');

                    $start_Time = $this->wire('sanitizer')->string($start_time);
                    $submit_Time = time();

                    $diff = $submit_Time - (int)$start_Time;

                    $submit_Time = $this->secondsToReadable($diff);
                    // too fast
                    if ($this->form->getMinTime() && ($diff < $this->form->getMinTime())) {
                        $secondsLeft = $this->_('seconds left'); //plural
                        $secondLeft = $this->_('second left'); // singular
                        $text = sprintf($this->_('You have submitted the form within %s. This seems pretty fast for a human. Your behavior is more similar to a Spam bot. Please wait at least %s until you submit the form once more.'),
                                $submit_Time,
                                '<span id="minTime" data-time="' . $this->form->getMinTime() . '" data-unit="' . $secondsLeft . ';' . $secondLeft . '">' . $this->secondsToReadable($this->form->getMinTime())) . '</span><div id="timecounter"></div>';
                        $this->alert->setCSSClass('alert_warningClass');
                        $this->alert->setAttribute('data-submittime', $this->form->getID());
                        $this->alert->setText($text);

                        return false;
                    }
                    //too slow
                    if ($this->form->getMaxTime() && ($diff > $this->form->getMaxTime())) {
                        $text = sprintf($this->_('You have submitted the form after %s. This seems pretty slow for a human. Your behavior is more similar to a Spam bot. Please submit the form within %s the next time. You are blocked now and you have to close the browser to unlock, open it again and visit this page once more.'),
                            $submit_Time, $this->secondsToReadable($this->form->getMaxTime()));
                        $this->alert->setText($text);
                        $this->wire('session')->set('blocked',
                            $submit_Time); // set session for blocked value is the submission time
                        return false;
                    }
                    return true; // submission was in time
                }
            }
            return true;
        }

        public function secondsToReadable(int $ss): string
        {
            $bit = [
                'month' => floor($ss / 2592000),
                'week' => floor(($ss % 2592000) / 604800),
                'day' => floor(($ss % 604800) / 86400),
                'hour' => floor(($ss % 86400) / 3600),
                'minute' => floor(($ss % 3600) / 60),
                'second' => $ss % 60
            ];

            $labelSingular = [
                'month' => $this->_('month'),
                'week' => $this->_('week'),
                'day' => $this->_('day'),
                'hour' => $this->_('hour'),
                'minute' => $this->_('minute'),
                'second' => $this->_('second')
            ];

            $labelPlural = [
                'month' => $this->_('months'),
                'week' => $this->_('weeks'),
                'day' => $this->_('days'),
                'hour' => $this->_('hours'),
                'minute' => $this->_('minutes'),
                'second' => $this->_('seconds')
            ];

            $ret = [];
            foreach ($bit as $k => $v) {
                $number = explode(' ', (string)$v);
                if ($number[0] != 0) {
                    $label = $this->_n($labelSingular[$k], $labelPlural[$k], $v);
                    $ret[] = $v . ' ' . $label;
                }
            }

            if (count($ret) > 1) {
                array_splice($ret, count($ret) - 1, 0, $this->_('and'));
            }
            return implode(' ', $ret);
        }

        /**
         * Write a log if failed login attempts have reached the max number of attempts
         * @return void
         * @throws WireException
         */
        protected function writeLogFailedAttempts(): void
        {
            //write the log file
            $log = new WireLog();
            $logText = [
                'FormID' => $this->form->getID(),
                'IP' => $this->wire('session')->getIP()
            ];
            $log->save('failed-attempts-frontendforms', json_encode($logText));
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
            if ($this->wire('sanitizer')->int($log)) {
                if (($this->form->getMaxAttempts() - $this->wire('session')->attempts) > 0) {
                    return true;
                } else {
                    if ($log) {
                        // check if logging of IP addresses is enabled
                        if ($this->frontendforms['input_logFailedAttempts']) {
                            $this->writeLogFailedAttempts();// write the log file
                        }
                    }
                    $this->wire('session')->set('blocked', 'maxAttempts');// set session for blocked
                    return false;
                }
            }
            return true;
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
            if (!$useDoubleFormSubmissionCheck) {
                return true;
            } // if check is disabled, return true and go on...
            // assign submitted **secretFormValue** from your form to a local variable
            $tokenfieldName = $this->form->getID() . '-doubleSubmission_token';

            $secretFormValue = isset($this->input->$tokenfieldName) ? filter_var($this->input->$tokenfieldName,
                FILTER_UNSAFE_RAW) : '';
            // check if the value is present in the **secretFormValue** variable
            if ($secretFormValue != '') {
                // check if both values are the same
                if ($this->wire('session')->get('doubleSubmission-' . $form->getID()) == $secretFormValue) {
                    return true;
                }
                // redirect to the same page
                $this->wire('session')->redirect($this->wire('page')->url);
                return false;
            } else {
                throw new Exception("Token value to prevent double form submission is missing", 1);
            }
        }

        /**
         * Check for CSRF-Attack
         * True: If CSRF-Protection is disabled or a valid CSRF-Token is present
         * False: If CSRF-Protection is enabled and the CSRF-Token is not valid
         * @param bool $useCSRFProtection
         * @return bool
         * @throws \ProcessWire\WireException
         */
        public function checkCSRFAttack(bool $useCSRFProtection): bool
        {
            if (!$useCSRFProtection) {
                return true;
            } else {
                return $this->wire('session')->CSRF->hasValidToken();
            }
        }

        /**
         * Cleans all input fields on a form from none input fields like Button, text,...
         * @param array $formElements
         * @return array
         */
        public function getRealInputFields(array $formElements): array
        {
            $fields = [];
            foreach ($formElements as $element) {
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                    $fields[] = $element;
                }
            }
            return $fields;
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
            $value = null;
            if (array_key_exists($fieldname, $this->input->getArray())) {
                $value = $this->input->get($fieldname);
                //sanitize all array values as string for security reasons
                if (in_array($element->className(), Tag::MULTIVALCLASSES)) {
                    array_walk($value, function (&$v) {
                        $v = $this->wire('sanitizer')->string($v);
                    });
                }
                if ($value) {
                    $element->setAttribute('value', $value);
                }
            }
            return $value;
        }

    }
