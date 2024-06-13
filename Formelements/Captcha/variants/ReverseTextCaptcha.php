<?php
declare(strict_types=1);

/*
 * Class for creating a captcha with a random string that has to be entered in reverse order
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: ReverseTextCaptcha.php
 * Created: 05.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class ReverseTextCaptcha extends AbstractCharset
{


    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = $this->_('Reverse string captcha');
        $this->desc = $this->_('Enter the text from the image in reverse order into the input field (from right to left).');
    }

    /**
     * Set the characters inside the captcha in reverse order as value for the captcha validation
     * @param string $content
     * @return AbstractTextCaptcha
     */
    protected function setCaptchaValidValue(string $content): AbstractTextCaptcha
    {
        $content =  strrev($content);
        return parent::setCaptchaValidValue($content);
    }

    /**
     * Customize the captcha input field for the default text captcha
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
