<?php
declare(strict_types=1);

/*
 * Class for creating a captcha with a random string
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: DefaultTextCaptcha.php
 * Created: 05.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class DefaultTextCaptcha extends AbstractCharset
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = $this->_('Random string captcha');
        $this->desc = $this->_('Enter the text from the image in the input field.');
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