<?php

declare(strict_types=1);

/*
 * Factory class to build text captcha
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TextCaptchaFactory.php
 * Created: 05.08.2022
 */

namespace FrontendForms;

class TextCaptchaFactory extends AbstractCaptchaFactory
{
    /**
     * Instantiate the concrete text CAPTCHA class matching the given variant name
     * Falls back to DefaultTextCaptcha for any unrecognized variant
     * @param string $captchaVariant
     * @return object
     */
    protected function selectCaptcha(string $captchaVariant): object
    {
        return match ($captchaVariant) {
            AbstractCaptchaFactory::REVERSETEXTCAPTCHA => new ReverseTextCaptcha(),
            AbstractCaptchaFactory::EVENTEXTCAPTCHA => new EvenCharacterTextCaptcha(),
            AbstractCaptchaFactory::SIMPLEMATHCAPTCHA => new SimpleMathTextCaptcha(),
            default => new DefaultTextCaptcha(),
        };
    }

}