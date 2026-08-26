<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SimpleMathTextCaptcha;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A thin subclass exposing setCaptchaValidValue()/getCaptchaValidValue()
 * (both protected, declared on AbstractTextCaptcha and overridden here)
 * via public wrappers, since PHPUnit test classes aren't subclasses of
 * SimpleMathTextCaptcha.
 */
final class ExposedSimpleMathTextCaptcha extends SimpleMathTextCaptcha
{
    public function exposeSetCaptchaValidValue(string $content): self
    {
        $this->setCaptchaValidValue($content);
        return $this;
    }

    public function exposeGetCaptchaValidValue(): string
    {
        return $this->getCaptchaValidValue();
    }
}

/**
 * Unit tests for SimpleMathTextCaptcha.
 *
 * calculate()/createRandomCalculation() (inherited from AbstractMath) are
 * already covered by AbstractMathTest via a bare ConcreteMath subclass -
 * this file focuses on what SimpleMathTextCaptcha itself adds: the
 * title/description, the overridden setCaptchaValidValue() (which ignores
 * its $content argument and uses $this->result instead), and
 * createCaptchaInputField()'s notes.
 */
final class SimpleMathTextCaptchaTest extends TestCase
{
    // --- construction ---

    /**
     * 1) Non-empty, distinct title/description are set on construction.
     */
    public function testConstructorSetsNonEmptyTitleAndDescription(): void
    {
        $captcha = new ExposedSimpleMathTextCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
        $this->assertNotSame($captcha->title, $captcha->desc);
    }

    // --- setCaptchaValidValue() ---

    /**
     * 2) setCaptchaValidValue() ignores whatever $content it's called with
     * and stores $this->result (the pre-calculated numeric answer) instead
     * - the user must type the RESULT of the expression, not the expression
     * text itself.
     */
    public function testSetCaptchaValidValueUsesResultNotGivenContent(): void
    {
        $captcha = new ExposedSimpleMathTextCaptcha();
        $resultProp = new ReflectionProperty($captcha, 'result');
        $resultProp->setAccessible(true);
        $resultProp->setValue($captcha, '8');

        // pass something completely different from the stored result
        $captcha->exposeSetCaptchaValidValue('3+5');

        $this->assertSame('8', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 3) This holds regardless of what $content is passed - even an empty
     * string doesn't change the outcome, since it's ignored entirely.
     */
    public function testSetCaptchaValidValueIgnoresEmptyContentToo(): void
    {
        $captcha = new ExposedSimpleMathTextCaptcha();
        $resultProp = new ReflectionProperty($captcha, 'result');
        $resultProp->setAccessible(true);
        $resultProp->setValue($captcha, '12');

        $captcha->exposeSetCaptchaValidValue('');

        $this->assertSame('12', $captcha->exposeGetCaptchaValidValue());
    }

    // --- createCaptchaInputField() ---

    /**
     * 4) The field's notes are set to the CAPTCHA's description text.
     */
    public function testCreateCaptchaInputFieldSetsNotesToDescription(): void
    {
        $captcha = new ExposedSimpleMathTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame($captcha->desc, $field->getNotes()->getText());
    }
}
