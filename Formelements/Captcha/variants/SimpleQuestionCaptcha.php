<?php
    declare(strict_types=1);

    /*
     * Class for creating a captcha with a single question and some answers
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: SimpleQuestionCaptcha.php
     * Created: 22.05.2024
     */


    namespace FrontendForms;

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class SimpleQuestionCaptcha extends AbstractQuestionCaptcha
    {


        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct()
        {
            parent::__construct();
            $this->title = $this->_('Simple question Captcha (Free text)');
            $this->desc = $this->_('Please answer the question.');

        }


        /**
         * Customize the captcha input field for the simple question captcha
         * @param string $formID
         * @return InputText
         */
        public function createCaptchaInputField(string $formID): InputText
        {

            // get the question in the user language
            $fieldNameQuestion = ($this->lang_id) ? 'input_question__'.$this->lang_id : 'input_question';
            $label = $this->frontendforms[$fieldNameQuestion];

            $fieldNameAnswers = ($this->lang_id) ? 'input_answers__'.$this->lang_id : 'input_answers';
            $answersText = $this->frontendforms[$fieldNameAnswers];

            if($answersText)
                $answers = explode("\n", str_replace("\r", "", $answersText));

            // set values from the module configuration
            $parent = parent::createCaptchaInputField($formID);
            if(!empty($label))
                $parent->setLabel($label);
            if(isset($answers))
                $this->setCaptchaValidValue($answers);
            $parent->removeRule('checkCaptcha');

            $parent->setNotes($this->desc);
            return $parent;
        }

    }