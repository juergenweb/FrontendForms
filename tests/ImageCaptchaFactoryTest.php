<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCaptchaFactory;
use FrontendForms\DefaultImageCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ImageCaptchaFactory.
 */
final class ImageCaptchaFactoryTest extends TestCase
{
    /**
     * 1) Any variant name (only DefaultImageCaptcha currently exists)
     * resolves to a DefaultImageCaptcha instance.
     */
    public function testMakeReturnsDefaultImageCaptcha(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::IMAGECAPTCHA, 'DefaultImageCaptcha');

        $this->assertInstanceOf(DefaultImageCaptcha::class, $captcha);
    }

    /**
     * 2) An unrecognized variant name also resolves to DefaultImageCaptcha,
     * since it's the only registered variant.
     */
    public function testMakeReturnsDefaultImageCaptchaForUnknownVariant(): void
    {
        $captcha = AbstractCaptchaFactory::make(AbstractCaptchaFactory::IMAGECAPTCHA, 'SomeUnknownVariant');

        $this->assertInstanceOf(DefaultImageCaptcha::class, $captcha);
    }
}
