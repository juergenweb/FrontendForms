<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\EvenCharacterTextCaptcha;
use PHPUnit\Framework\TestCase;

/**
 * A thin subclass exposing setCaptchaValidValue()/getCaptchaValidValue()
 * (both protected, declared on AbstractTextCaptcha and overridden here)
 * via public wrappers, since PHPUnit test classes aren't subclasses of
 * EvenCharacterTextCaptcha.
 */
final class ExposedEvenCharacterTextCaptcha extends EvenCharacterTextCaptcha
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
 * Unit tests for EvenCharacterTextCaptcha.
 *
 * Everything inherited from AbstractCharset/AbstractTextCaptcha (charset/
 * length config handling, image generation, ...) is already covered by
 * AbstractCharsetTest/AbstractTextCaptchaTest - this file focuses on what
 * EvenCharacterTextCaptcha itself adds: the overridden setCaptchaValidValue()
 * that only keeps every second character.
 */
final class EvenCharacterTextCaptchaTest extends TestCase
{
    // --- construction ---

    /**
     * 1) Non-empty, distinct title/description are set on construction.
     */
    public function testConstructorSetsNonEmptyTitleAndDescription(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
        $this->assertNotSame($captcha->title, $captcha->desc);
    }

    // --- setCaptchaValidValue() ---

    /**
     * 2) Only every second character (positions 2, 4, 6, ... when counting
     * from 1) is kept as the valid answer - confirmed standalone before
     * writing this assertion: "AB3D9F" -> "BDF".
     */
    public function testSetCaptchaValidValueKeepsEverySecondCharacter(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('AB3D9F');

        $this->assertSame('BDF', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 3) An empty input string results in an empty valid value.
     */
    public function testSetCaptchaValidValueWithEmptyStringIsEmpty(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('');

        $this->assertSame('', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 4) A single-character input has no "second" character to keep, so
     * the valid value is empty.
     */
    public function testSetCaptchaValidValueWithSingleCharacterIsEmpty(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('A');

        $this->assertSame('', $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 5) With an odd-length input, the trailing unpaired character is
     * simply dropped, not included.
     */
    public function testSetCaptchaValidValueWithOddLengthDropsTrailingCharacter(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();
        $captcha->exposeSetCaptchaValidValue('ABCDE');

        $this->assertSame('BD', $captcha->exposeGetCaptchaValidValue());
    }

    // --- createCaptchaInputField() ---

    /**
     * 6) The field's notes are set to the CAPTCHA's description text.
     */
    public function testCreateCaptchaInputFieldSetsNotesToDescription(): void
    {
        $captcha = new ExposedEvenCharacterTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame($captcha->desc, $field->getNotes()->getText());
    }
}
