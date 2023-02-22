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

    // Text captcha variants of text captcha
    const DEFAULTTEXTCAPTCHA = 'DefaultTextCaptcha';
    const REVERSETEXTCAPTCHA = 'ReverseTextCaptcha';
    const EVENTEXTCAPTCHA = 'EvenCharacterTextCaptcha';
    const SIMPLEMATHCAPTCHA = 'SimpleMathTextCaptcha';

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
        return str_ends_with($variant, 'TextCaptcha') ? self::TEXTCAPTCHA : self::IMAGECAPTCHA;
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
        if ($captchaType == AbstractCaptchaFactory::TEXTCAPTCHA) {
            $factory = new TextCaptchaFactory();
        } else {
            $factory = new ImageCaptchaFactory();
        }
        return $factory->build($captchaVariant);
    }

    protected abstract function selectCaptcha(string $captchaVariant);

}
