<?php
declare(strict_types=1);

/*
 * Class for creating a captcha with a random string, where any even character has to be added
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: EvenCharacterTextCaptcha.php
 * Created: 05.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class EvenCharacterTextCaptcha extends AbstractCharset
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = $this->_('Even string captcha');
        $this->desc = $this->_('Enter every second character of the text from the image in the input field.');
    }

    /**
     * Set every second character inside the captcha as value for the captcha validation
     * @param string $content
     * @return AbstractTextCaptcha
     */
    protected function setCaptchaValidValue(string $content): AbstractTextCaptcha
    {
        $newStr = '';
        for( $i = 0; $i < strlen($content); $i++) { $newStr .= ( ( $i % 2 ) != 0 ? $content[ $i ] : '' );}
        return parent::setCaptchaValidValue($newStr);
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
