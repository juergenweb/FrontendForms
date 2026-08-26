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
    public const TEXTCAPTCHA = 'text';
    public const IMAGECAPTCHA = 'image';
    public const QUESTIONCAPTCHA = 'question';
    public const SLIDERCAPTCHA = 'slider';

    // Text captcha variants of text captcha
    public const DEFAULTTEXTCAPTCHA = 'DefaultTextCaptcha';
    public const REVERSETEXTCAPTCHA = 'ReverseTextCaptcha';
    public const EVENTEXTCAPTCHA = 'EvenCharacterTextCaptcha';
    public const SIMPLEMATHCAPTCHA = 'SimpleMathTextCaptcha';
    public const SIMPLEQUESTIONCAPTCHA = 'SimpleQuestionCaptcha';


    /**
     * Protected constructor - instances are created via the static
     * make() factory method, not directly.
     */
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
        } elseif (str_ends_with($variant, 'ImageCaptcha')) {
            return self::IMAGECAPTCHA;
        } elseif (str_ends_with($variant, 'SliderCaptcha')) {
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
            case (AbstractCaptchaFactory::TEXTCAPTCHA):
                $factory = new TextCaptchaFactory();
                break;
            case (AbstractCaptchaFactory::IMAGECAPTCHA):
                $factory = new ImageCaptchaFactory();
                break;
            case (AbstractCaptchaFactory::QUESTIONCAPTCHA):
                $factory = new QuestionCaptchaFactory();
                break;
            case (AbstractCaptchaFactory::SLIDERCAPTCHA):
                $factory = new SliderCaptchaFactory();
                break;
            default:
                // fall back to the text CAPTCHA factory for an unrecognized type
                $factory = new TextCaptchaFactory();
                break;
        }

        return $factory->build($captchaVariant);
    }

    /**
     * Instantiate the concrete CAPTCHA class matching the given variant name
     * @param string $captchaVariant
     * @return object
     */
    abstract protected function selectCaptcha(string $captchaVariant);

}