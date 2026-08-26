<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DefaultImageCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DefaultImageCaptcha.
 *
 * Everything inherited from AbstractImageCaptcha (blur/pixelate/grayscale
 * config handling, image category extraction, ...) is already covered by
 * AbstractImageCaptchaTest - this file only tests what DefaultImageCaptcha
 * itself adds: setting the CAPTCHA's backend-select title/description.
 */
final class DefaultImageCaptchaTest extends TestCase
{
    /**
     * 1) A non-empty title is set on construction, used as this CAPTCHA
     * type's label in the backend CAPTCHA type selector.
     */
    public function testConstructorSetsNonEmptyTitle(): void
    {
        $captcha = new DefaultImageCaptcha();

        $this->assertNotSame('', $captcha->title);
    }

    /**
     * 2) A non-empty description is set on construction, used as this
     * CAPTCHA type's tooltip/explanation in the backend CAPTCHA type
     * selector.
     */
    public function testConstructorSetsNonEmptyDescription(): void
    {
        $captcha = new DefaultImageCaptcha();

        $this->assertNotSame('', $captcha->desc);
    }

    /**
     * 3) Title and description are different strings from each other (a
     * basic sanity check that both were actually configured distinctly,
     * not accidentally set to the same value).
     */
    public function testTitleAndDescriptionAreDistinct(): void
    {
        $captcha = new DefaultImageCaptcha();

        $this->assertNotSame($captcha->title, $captcha->desc);
    }
}
