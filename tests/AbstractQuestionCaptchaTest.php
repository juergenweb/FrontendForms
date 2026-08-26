<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractQuestionCaptcha;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A minimal concrete AbstractQuestionCaptcha subclass for testing.
 * AbstractQuestionCaptcha declares no abstract methods of its own (it
 * extends Tag directly, not AbstractCaptcha - see the architecture
 * discussion earlier in this session for why), so no overrides are needed
 * here to make the class instantiable.
 */
final class ConcreteQuestionCaptcha extends AbstractQuestionCaptcha
{
    public function exposeSetCaptchaValidValue(array|null $content): self
    {
        return $this->setCaptchaValidValue($content);
    }

    public function exposeGetCaptchaValidValue(): array|null
    {
        return $this->getCaptchaValidValue();
    }
}

/**
 * Unit tests for AbstractQuestionCaptcha.
 *
 * The multi-language branch of __construct() (resolving $lang_id from the
 * current visitor's language) depends on whether LanguageSupport is
 * actually installed/active in the live test environment, so it isn't
 * asserted against a specific value here - only that $type is set
 * correctly, which is independent of that.
 */
final class AbstractQuestionCaptchaTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The CAPTCHA's internal "type" is set to the concrete subclass's
     * own class name.
     */
    public function testConstructorSetsTypeFromClassName(): void
    {
        $captcha = new ConcreteQuestionCaptcha();

        $reflection = new ReflectionProperty($captcha, 'type');
        $reflection->setAccessible(true);
        $this->assertSame('ConcreteQuestionCaptcha', $reflection->getValue($captcha));
    }

    // --- setCaptchaValidValue() / getCaptchaValidValue() ---

    /**
     * 2) A list of valid answers can be set and read back - unlike
     * AbstractCaptcha's single-string solution value, a question CAPTCHA
     * can have multiple acceptable answers.
     */
    public function testSetAndGetCaptchaValidValueWithMultipleAnswers(): void
    {
        $captcha = new ConcreteQuestionCaptcha();
        $captcha->exposeSetCaptchaValidValue(['blue', 'light blue']);

        $this->assertSame(['blue', 'light blue'], $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 3) The valid value can also be explicitly set to null (e.g. before
     * any answers have been configured).
     */
    public function testSetCaptchaValidValueAcceptsNull(): void
    {
        $captcha = new ConcreteQuestionCaptcha();
        $captcha->exposeSetCaptchaValidValue(['a']);
        $captcha->exposeSetCaptchaValidValue(null);

        $this->assertNull($captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 4) A freshly created CAPTCHA starts with an empty array as its valid
     * value (the property's declared default).
     */
    public function testCaptchaValidValueDefaultsToEmptyArray(): void
    {
        $this->assertSame([], (new ConcreteQuestionCaptcha())->exposeGetCaptchaValidValue());
    }

    // --- createCaptchaInputField() ---

    /**
     * 5) The generated input field's "name" attribute combines the given
     * form ID with "-captcha".
     */
    public function testCreateCaptchaInputFieldSetsNameFromFormId(): void
    {
        $captcha = new ConcreteQuestionCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('myform-captcha', $field->getAttribute('name'));
    }

    /**
     * 6) Both the field's own wrapper and its input wrapper get the
     * "captcha" CSS class.
     */
    public function testCreateCaptchaInputFieldAddsCaptchaCssClassToWrappers(): void
    {
        $captcha = new ConcreteQuestionCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertContains('captcha', $field->getFieldWrapper()->getAttribute('class'));
        $this->assertContains('captcha', $field->getInputWrapper()->getAttribute('class'));
    }
}
