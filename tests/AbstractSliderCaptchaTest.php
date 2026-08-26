<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractSliderCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * A minimal concrete AbstractSliderCaptcha subclass for testing.
 * AbstractSliderCaptcha declares no abstract methods of its own (it
 * extends Tag directly, not AbstractCaptcha - same reasoning as
 * AbstractQuestionCaptcha, see the architecture discussion earlier in this
 * session), so no overrides are needed here to make the class instantiable.
 */
final class ConcreteSliderCaptcha extends AbstractSliderCaptcha
{
}

/**
 * Unit tests for AbstractSliderCaptcha.
 *
 * CSS class assertions use assertContains() rather than checking the exact
 * class list, since the wrapper's class attribute may already contain a
 * framework-specific default class from the live test environment (see the
 * lesson learned in AbstractQuestionCaptchaTest earlier in this session).
 */
final class AbstractSliderCaptchaTest extends TestCase
{
    // --- createSliderCaptcha() ---

    /**
     * 1) The slider container markup is a self-contained, unvalidated div
     * with an id derived from the form ID.
     */
    public function testCreateSliderCaptchaReturnsExpectedDiv(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $this->assertSame(
            '<div id="myform-captcha" data-validated="false"></div>',
            $captcha->createSliderCaptcha('myform')
        );
    }

    // --- createCaptchaInputField() ---

    /**
     * 2) The generated checkbox's "name" attribute combines the given form
     * ID with "-slider-captcha".
     */
    public function testCreateCaptchaInputFieldSetsNameFromFormId(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('myform-slider-captcha', $field->getAttribute('name'));
    }

    /**
     * 3) The checkbox starts with value "0" (unchecked/not yet verified)
     * and carries a data-formid attribute pointing back at the form.
     */
    public function testCreateCaptchaInputFieldSetsValueAndFormIdAttributes(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('0', $field->getAttribute('value'));
        $this->assertSame('myform', $field->getAttribute('data-formid'));
    }

    /**
     * 4) The checkbox carries the slider-specific CSS class.
     */
    public function testCreateCaptchaInputFieldSetsSliderCssClass(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertContains('ff-slidercaptcha-checkbox', $field->getAttribute('class'));
    }

    /**
     * 5) Both the field's own wrapper and its input wrapper get the
     * "captcha" CSS class.
     */
    public function testCreateCaptchaInputFieldAddsCaptchaCssClassToWrappers(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertContains('captcha', $field->getFieldWrapper()->getAttribute('class'));
        $this->assertContains('captcha', $field->getInputWrapper()->getAttribute('class'));
    }

    /**
     * 6) The slider container markup (from createSliderCaptcha()) is
     * prepended to the checkbox's own rendered output.
     */
    public function testCreateCaptchaInputFieldPrependsSliderContainer(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');
        $out = $field->render();

        $this->assertStringContainsString('<div id="myform-captcha" data-validated="false"></div>', $out);
    }

    /**
     * 7) The slider container appears BEFORE the checkbox input itself in
     * the rendered output, matching prepend() semantics.
     */
    public function testSliderContainerAppearsBeforeCheckboxInput(): void
    {
        $captcha = new ConcreteSliderCaptcha();

        $field = $captcha->createCaptchaInputField('myform');
        $out = $field->render();

        $sliderPos = strpos($out, 'data-validated="false"');
        $checkboxPos = strpos($out, 'type="checkbox"');

        $this->assertNotFalse($sliderPos);
        $this->assertNotFalse($checkboxPos);
        $this->assertLessThan($checkboxPos, $sliderPos);
    }
}
