<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCaptchaFactory;
use FrontendForms\SliderCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SliderCaptchaFactory.
 */
final class SliderCaptchaFactoryTest extends TestCase
{
    /**
     * 1) The "SliderCaptcha" variant resolves to a SliderCaptcha instance.
     */
    public function testMakeReturnsSliderCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::SLIDERCAPTCHA, AbstractCaptchaFactory::SLIDERCAPTCHA);

        $this->assertInstanceOf(SliderCaptcha::class, $captcha);
    }

    /**
     * 2) An unrecognized variant name also resolves to SliderCaptcha,
     * since it's the only registered variant.
     */
    public function testMakeReturnsSliderCaptchaForUnknownVariant(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::SLIDERCAPTCHA, 'SomeUnknownVariant');

        $this->assertInstanceOf(SliderCaptcha::class, $captcha);
    }
}
