<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCaptchaFactory;
use FrontendForms\DefaultTextCaptcha;
use FrontendForms\EvenCharacterTextCaptcha;
use FrontendForms\ReverseTextCaptcha;
use FrontendForms\SimpleMathTextCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextCaptchaFactory, exercised through the public
 * AbstractCaptchaFactory::make() entry point (the real usage path),
 * rather than calling the protected selectCaptcha() directly.
 */
final class TextCaptchaFactoryTest extends TestCase
{
    /**
     * 1) The "ReverseTextCaptcha" variant resolves to a ReverseTextCaptcha
     * instance.
     */
    public function testMakeReturnsReverseTextCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::REVERSETEXTCAPTCHA);

        $this->assertInstanceOf(ReverseTextCaptcha::class, $captcha);
    }

    /**
     * 2) The "EvenCharacterTextCaptcha" variant resolves to an
     * EvenCharacterTextCaptcha instance.
     */
    public function testMakeReturnsEvenCharacterTextCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::EVENTEXTCAPTCHA);

        $this->assertInstanceOf(EvenCharacterTextCaptcha::class, $captcha);
    }

    /**
     * 3) The "SimpleMathTextCaptcha" variant resolves to a
     * SimpleMathTextCaptcha instance.
     */
    public function testMakeReturnsSimpleMathTextCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::SIMPLEMATHCAPTCHA);

        $this->assertInstanceOf(SimpleMathTextCaptcha::class, $captcha);
    }

    /**
     * 4) An unrecognized variant name falls back to DefaultTextCaptcha.
     */
    public function testMakeFallsBackToDefaultTextCaptchaForUnknownVariant(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, 'SomeUnknownVariant');

        $this->assertInstanceOf(DefaultTextCaptcha::class, $captcha);
    }

    /**
     * 5) The explicit "DefaultTextCaptcha" variant name also resolves to
     * DefaultTextCaptcha.
     */
    public function testMakeReturnsDefaultTextCaptchaForExplicitVariant(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::TEXTCAPTCHA, AbstractCaptchaFactory::DEFAULTTEXTCAPTCHA);

        $this->assertInstanceOf(DefaultTextCaptcha::class, $captcha);
    }
}
