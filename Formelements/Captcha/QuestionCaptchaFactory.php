<?php
    declare(strict_types=1);

    /*
     * Factory class to build captcha with question and answer
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: QuestionCaptchaFactory.php
     * Created: 22.05.2024
     */


    namespace FrontendForms;

    class QuestionCaptchaFactory extends AbstractCaptchaFactory
    {

        protected function selectCaptcha(string $captchaVariant): object
        {
            return match ($captchaVariant) {
                AbstractCaptchaFactory::SIMPLEQUESTIONCAPTCHA => new SimpleQuestionCaptcha(),
                default => new SimpleQuestionCaptcha(),
            };
        }

    }
