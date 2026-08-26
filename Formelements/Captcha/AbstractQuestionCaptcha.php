<?php

namespace FrontendForms;

abstract class AbstractQuestionCaptcha extends Tag
{
    protected array|null $captchaValidValue = []; // array containing allowed answers
    protected string $category = ''; // the category type of the captcha (text or image)
    protected string $type = ''; // the name of the captcha
    public string $title = ''; // the name for the captcha in the backend select
    public string $desc = ''; // the description of the captcha in the backend select
    public string|int|null $lang_id = '';


    /**
     * Set up the shared CAPTCHA metadata: resolve the current visitor's
     * language id (if multi-language support is active) and set the
     * CAPTCHA's internal "type" name from the concrete subclass name.
     */
    public function __construct()
    {
        parent::__construct();
        // get the current lang id if language support is enabled
        if ($this->wire('languages')) {
            $lang = $this->wire('user')->language;
            $this->lang_id = $lang->isDefault() ? '' : $lang->id;
        } else {
            $this->lang_id = null;
        }

        $this->type = $this->className(); // set the name of the captcha from the class name
    }

    /**
     * Set the real captcha value for input validation
     * This depends on the captcha variant set, so this contains the value that should be entered into the input field
     *
     * @param array|null $content
     * @return $this
     */
    protected function setCaptchaValidValue(array|null $content): self
    {
        $this->captchaValidValue = $content;
        return $this;
    }

    /**
     * Get the solution value of the captcha
     * @return array|null
     */
    protected function getCaptchaValidValue(): array|null
    {
        return $this->captchaValidValue;
    }

    /**
     * Create the captcha inputfield for a captcha
     * The inputfield for the captcha is an object of type InputText
     * @param string $formID
     * @return InputText
     */
    public function createCaptchaInputField(string $formID): InputText
    {
        // start creating the captcha input field
        $captchaInput = new InputText('captcha');
        // Remove or add wrappers depending on settings
        $captchaInput->setAttribute('name', $formID . '-captcha');
        $captchaInput->useInputWrapper($this->useInputWrapper);
        $captchaInput->useFieldWrapper($this->useFieldWrapper);
        $captchaInput->getFieldWrapper()->setAttribute('class', 'captcha');
        $captchaInput->getInputWrapper()->setAttribute('class', 'captcha');
        return $captchaInput;
    }


}