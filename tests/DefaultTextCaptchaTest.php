<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DefaultTextCaptcha;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DefaultTextCaptcha.
 */
final class DefaultTextCaptchaTest extends TestCase
{
    /**
     * 1) The constructor sets a non-empty title and description.
     */
    public function testConstructorSetsTitleAndDescription(): void
    {
        $captcha = new DefaultTextCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
    }

    /**
     * 2) createCaptchaInputField() returns an InputText field, with the
     * description text attached as notes.
     */
    public function testCreateCaptchaInputFieldReturnsInputTextWithNotes(): void
    {
        $captcha = new DefaultTextCaptcha();

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertInstanceOf(InputText::class, $field);
        $this->assertSame($captcha->desc, $field->getNotes()->getText());
    }
}
