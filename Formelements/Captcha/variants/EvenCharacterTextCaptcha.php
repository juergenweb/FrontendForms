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
        // multibyte-safe iteration (not strlen()/direct string indexing,
        // which operate on bytes) - otherwise multi-byte UTF-8 characters
        // (e.g. Cyrillic letters) would be split mid-character, producing
        // invalid, corrupted fragments instead of complete characters.
        $characters = mb_str_split($content);
        $newStr = '';
        foreach ($characters as $i => $character) {
            $newStr .= (($i % 2) != 0 ? $character : '');
        }
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
