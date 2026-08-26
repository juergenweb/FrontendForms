<?php

declare(strict_types=1);

/*
 * Factory class to build an image-based CAPTCHA
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: ImageCaptchaFactory.php
 * Created: 16.08.2022
 */

namespace FrontendForms;

class ImageCaptchaFactory extends AbstractCaptchaFactory
{
    /**
     * Instantiate the concrete image CAPTCHA class matching the given variant name
     * Currently only DefaultImageCaptcha exists, so every variant (including
     * unrecognized ones) resolves to it
     * @param string $captchaVariant
     * @return object
     */
    protected function selectCaptcha(string $captchaVariant): object
    {
        return match($captchaVariant) {
            default => new DefaultImageCaptcha()
        };
    }

}