<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\ReverseTextCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * A thin subclass exposing setCaptchaValidValue()/getCaptchaValidValue()
 * (both protected, declared on AbstractTextCaptcha and overridden here)
 * via public wrappers, since PHPUnit test classes aren't subclasses of
 * ReverseTextCaptcha.
 */
final class ExposedReverseTextCaptcha extends ReverseTextCaptcha
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
 * Unit tests for ReverseTextCaptcha.
 *
 * Everything inherited from AbstractCharset/AbstractTextCaptcha (charset/
 * length config handling, image generation, ...) is already covered by
 * AbstractCharsetTest/AbstractTextCaptchaTest - this file focuses on what
 * ReverseTextCaptcha itself adds: the overridden setCaptchaValidValue()
 * that reverses the character order.
 */
final class ReverseTextCaptchaTest extends TestCase
{
    // --- construction ---

    /**
     * 1) Non-empty, distinct title/description are set on construction.
     */
    public function testConstructorSetsNonEmptyTitleAndDescription(): void
    {
        $captcha = new ExposedReverseTextCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
        $this->assertNotSame($captcha->title, $captcha->desc);
    }

    // --- setCaptchaValidValue() ---

    /**
     * 2) The character order is reversed for a plain ASCII string.
     */
    public function testSetCaptchaValidValueReversesAsciiString(): void
    {
        $captcha = new ExposedReverseTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('AB3D');

        $this->assertSame('D3BA', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 3) An empty input string results in an empty valid value.
     */
    public function testSetCaptchaValidValueWithEmptyStringIsEmpty(): void
    {
        $captcha = new ExposedReverseTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('');

        $this->assertSame('', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 4) A single character reversed is itself.
     */
    public function testSetCaptchaValidValueWithSingleCharacterIsUnchanged(): void
    {
        $captcha = new ExposedReverseTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('X');

        $this->assertSame('X', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 5) REGRESSION TEST for the multibyte-safety fix: reversing a string
     * containing multibyte UTF-8 characters (e.g. accented letters, which
     * the configurable CAPTCHA charset could legitimately contain on a
     * non-English site) must produce a correctly reversed, still-valid
     * UTF-8 string. Before the fix (strrev(), which operates on bytes, not
     * characters), this would have produced corrupted, invalid UTF-8 -
     * confirmed standalone before writing this assertion:
     *   strrev("ÄÖÜ")  => invalid UTF-8 (mb_check_encoding() === false)
     *   correct result => "ÜÖÄ" (valid UTF-8)
     */
    public function testSetCaptchaValidValueIsMultibyteSafe(): void
    {
        $captcha = new ExposedReverseTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('ÄÖÜ');

        $result = $captcha->exposeGetCaptchaValidValue();

        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
        $this->assertSame('ÜÖÄ', $result);
    }

    // --- createCaptchaInputField() ---

    /**
     * 6) The field's notes are set to the CAPTCHA's description text.
     */
    public function testCreateCaptchaInputFieldSetsNotesToDescription(): void
    {
        $captcha = new ExposedReverseTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame($captcha->desc, $field->getNotes()->getText());
    }
}
