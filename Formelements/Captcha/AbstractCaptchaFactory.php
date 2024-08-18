<?php
    declare(strict_types=1);

    /*
     * Base abstract factory class for building a captcha
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: AbstractCaptchaFactory.php
     * Created: 05.08.2022
     */

    namespace FrontendForms;

    use ProcessWire\Wire as Wire;

    abstract class AbstractCaptchaFactory extends Wire
    {

        // captcha types
        const TEXTCAPTCHA = 'text';
        const IMAGECAPTCHA = 'image';
        const QUESTIONCAPTCHA = 'question';
        const SLIDERCAPTCHA = 'slider';

        // Text captcha variants of text captcha
        const DEFAULTTEXTCAPTCHA = 'DefaultTextCaptcha';
        const REVERSETEXTCAPTCHA = 'ReverseTextCaptcha';
        const EVENTEXTCAPTCHA = 'EvenCharacterTextCaptcha';
        const SIMPLEMATHCAPTCHA = 'SimpleMathTextCaptcha';
        const SIMPLEQUESTIONCAPTCHA = 'SimpleTextCaptcha';


        protected function __construct()
        {
            parent::__construct();
        }

        /**
         * Extract the type of the captcha from its class name
         * @param string $variant
         * @return string
         */
        public static function getCaptchaTypeFromClass(string $variant): string
        {
            if (str_ends_with($variant, 'TextCaptcha')) {
                return self::TEXTCAPTCHA;
            } else if (str_ends_with($variant, 'ImageCaptcha')) {
                return self::IMAGECAPTCHA;
            } else if (str_ends_with($variant, 'SliderCaptcha')) {
                return self::SLIDERCAPTCHA;
            }
            return self::QUESTIONCAPTCHA;
        }

        /**
         * Build a new class depending on the captcha type chosen
         * @param string $captchaVariant
         * @return mixed
         */
        protected function build(string $captchaVariant): mixed
        {
            return $this->selectCaptcha($captchaVariant);
        }

        /**
         * @param string $captchaType
         * @param string $captchaVariant
         * @return mixed
         */
        public static function make(string $captchaType, string $captchaVariant): mixed
        {

            switch ($captchaType) {
                case(AbstractCaptchaFactory::TEXTCAPTCHA);
                    $factory = new TextCaptchaFactory();
                    break;
                case(AbstractCaptchaFactory::IMAGECAPTCHA):
                    $factory = new ImageCaptchaFactory();
                    break;
                case(AbstractCaptchaFactory::QUESTIONCAPTCHA):
                    $factory = new QuestionCaptchaFactory();
                    break;
                case(AbstractCaptchaFactory::SLIDERCAPTCHA):
                    $factory = new SliderCaptchaFactory();
                    break;
            }

            return $factory->build($captchaVariant);
        }

        protected abstract function selectCaptcha(string $captchaVariant);

    }
