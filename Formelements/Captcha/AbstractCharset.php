<?php
declare(strict_types=1);

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: AbstractCharset.php
 * Created: 16.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

abstract class AbstractCharset extends AbstractTextCaptcha
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();

        // set random string as content for the captcha
        $this->setCaptchaContent($this->createRandomString());
    }


    /**
     * Get the string of the charset that will be used to create the captcha text
     * @return string
     */
    protected function getCharset(): string
    {
        return $this->frontendforms['input_captchaCharset'];
    }

    /**
     * Get the number of characters
     * @return int
     */
    protected function getNumberOfCharacters(): int
    {
        return $this->frontendforms['input_captchaNumberOfCharacters'];
    }

    /**
     * Create the random string for the captcha depending on the number and charset setting
     * @return string
     */
    protected function createRandomString(): string
    {
        $randomString = '';
        for ($i = 0; $i < $this->getNumberOfCharacters(); $i++) {
            $index = rand(0, strlen($this->getCharset()) - 1);
            $randomString .= $this->getCharset()[$index];
        }
        return $randomString;
    }

}
