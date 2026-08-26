<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCaptchaFactory;
use FrontendForms\DefaultImageCaptcha;
use FrontendForms\DefaultTextCaptcha;
use FrontendForms\EvenCharacterTextCaptcha;
use FrontendForms\ReverseTextCaptcha;
use FrontendForms\SimpleMathTextCaptcha;
use FrontendForms\SimpleQuestionCaptcha;
use FrontendForms\SliderCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for AbstractCaptchaFactory.
 */
final class AbstractCaptchaFactoryTest extends TestCase
{
    // --- getCaptchaTypeFromClass() ---

    /**
     * 1) A class name ending in "TextCaptcha" is classified as the "text"
     * CAPTCHA type.
     */
    public function testGetCaptchaTypeFromClassDetectsTextCaptcha(): void
    {
        $this->assertSame(
            AbstractCaptchaFactory::TEXTCAPTCHA,
            AbstractCaptchaFactory::getCaptchaTypeFromClass('DefaultTextCaptcha')
        );
    }

    /**
     * 2) A class name ending in "ImageCaptcha" is classified as the
     * "image" CAPTCHA type.
     */
    public function testGetCaptchaTypeFromClassDetectsImageCaptcha(): void
    {
        $this->assertSame(
            AbstractCaptchaFactory::IMAGECAPTCHA,
            AbstractCaptchaFactory::getCaptchaTypeFromClass('DefaultImageCaptcha')
        );
    }

    /**
     * 3) A class name ending in "SliderCaptcha" is classified as the
     * "slider" CAPTCHA type.
     */
    public function testGetCaptchaTypeFromClassDetectsSliderCaptcha(): void
    {
        $this->assertSame(
            AbstractCaptchaFactory::SLIDERCAPTCHA,
            AbstractCaptchaFactory::getCaptchaTypeFromClass('SliderCaptcha')
        );
    }

    /**
     * 4) Any other class name (e.g. a question CAPTCHA, which has no
     * distinctive suffix of its own) falls back to the "question" type.
     */
    public function testGetCaptchaTypeFromClassDefaultsToQuestionCaptcha(): void
    {
        $this->assertSame(
            AbstractCaptchaFactory::QUESTIONCAPTCHA,
            AbstractCaptchaFactory::getCaptchaTypeFromClass('SimpleQuestionCaptcha')
        );
    }

    // --- make() ---

    /**
     * 5) With the "text" type and the default variant, a DefaultTextCaptcha
     * is built.
     */
    public function testMakeBuildsDefaultTextCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, 'DefaultTextCaptcha');

        $this->assertInstanceOf(DefaultTextCaptcha::class, $captcha);
    }

    /**
     * 6) With the "text" type and a specific variant name, the matching
     * concrete text CAPTCHA class is built.
     */
    public function testMakeBuildsSpecificTextCaptchaVariants(): void
    {
        $this->assertInstanceOf(
            ReverseTextCaptcha::class,
            AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::REVERSETEXTCAPTCHA)
        );
        $this->assertInstanceOf(
            EvenCharacterTextCaptcha::class,
            AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::EVENTEXTCAPTCHA)
        );
        $this->assertInstanceOf(
            SimpleMathTextCaptcha::class,
            AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::SIMPLEMATHCAPTCHA)
        );
    }

    /**
     * 7) With the "image" type, a DefaultImageCaptcha is built (currently
     * the only image CAPTCHA variant).
     */
    public function testMakeBuildsImageCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::IMAGECAPTCHA, 'anything');

        $this->assertInstanceOf(DefaultImageCaptcha::class, $captcha);
    }

    /**
     * 8) With the "slider" type, a SliderCaptcha is built.
     */
    public function testMakeBuildsSliderCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::SLIDERCAPTCHA, AbstractCaptchaFactory::SLIDERCAPTCHA);

        $this->assertInstanceOf(SliderCaptcha::class, $captcha);
    }

    /**
     * 9) With the "question" type and the SIMPLEQUESTIONCAPTCHA constant, a
     * SimpleQuestionCaptcha is built. This also exercises the fixed
     * SIMPLEQUESTIONCAPTCHA constant value (was 'SimpleTextCaptcha',
     * matching no real class, before the fix) - though note this specific
     * scenario would have passed even before the fix too, since
     * QuestionCaptchaFactory's match() falls back to the same
     * SimpleQuestionCaptcha class by default regardless; the constant fix
     * is about correctness/clarity, not restoring broken behaviour here.
     */
    public function testMakeBuildsQuestionCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(
            AbstractCaptchaFactory::QUESTIONCAPTCHA,
            AbstractCaptchaFactory::SIMPLEQUESTIONCAPTCHA
        );

        $this->assertInstanceOf(SimpleQuestionCaptcha::class, $captcha);
    }

    /**
     * 10) REGRESSION TEST for the missing default case fix: calling make()
     * with an unrecognized CAPTCHA type must not fail with an "undefined
     * variable $factory" error - it falls back to the text CAPTCHA factory.
     */
    public function testMakeWithUnrecognizedTypeFallsBackToTextCaptchaFactory(): void
    {
        $captcha = AbstractCaptchaFactory::make('not-a-real-captcha-type', 'DefaultTextCaptcha');

        $this->assertInstanceOf(DefaultTextCaptcha::class, $captcha);
    }
}
