<?php
declare(strict_types=1);

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: ImageCaptchaFactory.php
 * Created: 16.08.2022 
 */

namespace FrontendForms;

class ImageCaptchaFactory extends AbstractCaptchaFactory
{
    protected function selectCaptcha(string $captchaVariant): object
    {
        return match($captchaVariant) {
            default => new DefaultImageCaptcha()
        };
    }

}
