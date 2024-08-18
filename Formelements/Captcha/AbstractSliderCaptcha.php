<?php

    namespace FrontendForms;

    abstract class AbstractSliderCaptcha extends Tag
    {

        public string $title = ''; // the name for the captcha in the backend selects
        public string $desc = ''; // the description of the captcha in the backend selects

        public function __construct()
        {
            parent::__construct();
        }

        /**
         * Create the captcha input field for a captcha
         * The input field for the captcha is an object of type InputCheckbox
         * @param string $formID
         * @return \FrontendForms\InputCheckbox
         */
        public function createCaptchaInputField(string $formID): InputCheckbox
        {

            // start creating the captcha input field
            $captchaInput = new InputCheckbox('slider-captcha');
            $captchaInput->setLabel($this->_('I am a human'));
            $captchaInput->setAttribute('name', $formID . '-slider-captcha');
            $captchaInput->setAttribute('class', 'ff-slidercaptcha-checkbox');
            $captchaInput->setAttribute('data-formid', $formID);
            $captchaInput->setAttribute('value', '0');
            $captchaInput->useInputWrapper($this->useInputWrapper);
            $captchaInput->useFieldWrapper($this->useFieldWrapper);
            $captchaInput->getFieldWrapper()->setAttribute('class', 'captcha');
            $captchaInput->getInputWrapper()->setAttribute('class', 'captcha');
            $captchaInput->prepend($this->createSliderCaptcha($formID));
            return $captchaInput;

        }

        /**
         * Render the div container for the slider captcha
         * @param string $formID
         * @return string
         */
        public function createSliderCaptcha(string $formID): string
        {
            return '<div id="' . $formID . '-captcha" data-validated="false"></div>';
        }

    }
