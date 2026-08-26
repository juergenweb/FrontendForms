<?php

declare(strict_types=1);

/*
 * Class for creating a captcha with a simple math calculation
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: SimpleMathTextCaptcha.php
 * Created: 18.08.2022
 */

namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class SimpleMathTextCaptcha extends AbstractMath
{
    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = $this->_('Math captcha');
        $this->desc = $this->_('Enter the result of the calculation in the input field.');
    }

    /**
     * Set the calculated result (not the displayed expression) as the
     * value for the captcha validation. The $content parameter (the
     * expression shown in the image, e.g. "3+5") is intentionally ignored -
     * the user has to enter the RESULT of the calculation, not the
     * expression itself, so $this->result (computed and stored by
     * AbstractMath::createRandomCalculation()) is used instead.
     * @param string $content
     * @return AbstractTextCaptcha
     */
    protected function setCaptchaValidValue(string $content): AbstractTextCaptcha
    {
        return parent::setCaptchaValidValue($this->result);
    }

    /**
     * Customize the captcha input field for the simple math captcha
     * @param string $formID
     * @return InputText
     */
    public function createCaptchaInputField(string $formID): InputText
    {
        $parent = parent::createCaptchaInputField($formID);
        $parent->setNotes($this->desc);
        return $parent;
    }

}