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
     * Set the characters inside the captcha in reverse order as value for the captcha validation
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
