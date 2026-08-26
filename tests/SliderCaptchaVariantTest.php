<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckbox;
use FrontendForms\SliderCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SliderCaptcha (the concrete variant class, not
 * AbstractSliderCaptcha).
 */
final class SliderCaptchaVariantTest extends TestCase
{
    /**
     * 1) The constructor sets a non-empty title and description.
     */
    public function testConstructorSetsTitleAndDescription(): void
    {
        $captcha = new SliderCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
    }

    /**
     * 2) createCaptchaInputField() (inherited from AbstractSliderCaptcha)
     * returns an InputCheckbox field for the "I am a human" checkbox.
     */
    public function testCreateCaptchaInputFieldReturnsCheckbox(): void
    {
        $captcha = new SliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertInstanceOf(InputCheckbox::class, $field);
        $this->assertSame('checkbox', $field->getAttribute('type'));
    }
}
