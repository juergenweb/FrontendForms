<?php
    declare(strict_types=1);

    /*
     * Factory class to build a slider captcha
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: SliderCaptchaFactory.php
     * Created: 11.08.2024
     */


    namespace FrontendForms;

    class SliderCaptchaFactory extends AbstractCaptchaFactory
    {

        protected function selectCaptcha(string $captchaVariant): object
        {
            return match ($captchaVariant) {
                AbstractCaptchaFactory::SLIDERCAPTCHA => new SliderCaptcha(),
                default => new SliderCaptcha(),
            };
        }

    }
