<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SimpleQuestionCaptcha;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A thin subclass exposing getCaptchaValidValue() (protected, declared on
 * AbstractQuestionCaptcha) via a public wrapper, since PHPUnit test classes
 * aren't subclasses of SimpleQuestionCaptcha.
 */
final class ExposedSimpleQuestionCaptcha extends SimpleQuestionCaptcha
{
    public function exposeGetCaptchaValidValue(): array|null
    {
        return $this->getCaptchaValidValue();
    }
}

/**
 * Unit tests for SimpleQuestionCaptcha.
 *
 * $lang_id (set in the AbstractQuestionCaptcha constructor based on whether
 * multi-language support is active in the live test environment) is forced
 * to known values via ReflectionProperty, so both the default-language and
 * language-specific config key branches can be tested deterministically,
 * regardless of what the live environment actually has configured.
 */
final class SimpleQuestionCaptchaTest extends TestCase
{
    private function setLangId(ExposedSimpleQuestionCaptcha $captcha, mixed $langId): void
    {
        $prop = new ReflectionProperty($captcha, 'lang_id');
        $prop->setAccessible(true);
        $prop->setValue($captcha, $langId);
    }

    private function setConfig(ExposedSimpleQuestionCaptcha $captcha, array $values): void
    {
        $prop = new ReflectionProperty($captcha, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($captcha);
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $prop->setValue($captcha, $config);
    }

    // --- construction ---

    /**
     * 1) The title/description are set to non-empty (translated) strings on
     * construction.
     */
    public function testConstructorSetsTitleAndDescription(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();

        $this->assertNotSame('', $captcha->title);
        $this->assertNotSame('', $captcha->desc);
    }

    // --- createCaptchaInputField() - label ---

    /**
     * 2) With the default language active (falsy $lang_id), the label is
     * read from the plain "input_question" config key.
     */
    public function testCreateCaptchaInputFieldUsesDefaultLanguageQuestionKey(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'What color is the sky?']);

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('What color is the sky?', $field->getLabel()->getText());
    }

    /**
     * 3) With a specific active language (truthy $lang_id), the label is
     * read from the language-suffixed "input_question__{lang_id}" config
     * key instead.
     */
    public function testCreateCaptchaInputFieldUsesLanguageSpecificQuestionKey(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, 1003);
        $this->setConfig($captcha, [
            'input_question' => 'What color is the sky?',
            'input_question__1003' => 'Welche Farbe hat der Himmel?',
        ]);

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('Welche Farbe hat der Himmel?', $field->getLabel()->getText());
    }

    /**
     * 4) With no question configured at all, the field's label is left at
     * its default (empty) - setLabel() is simply never called.
     */
    public function testCreateCaptchaInputFieldLeavesLabelEmptyWhenNotConfigured(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => '']);

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame('', $field->getLabel()->getText());
    }

    // --- createCaptchaInputField() - answers ---

    /**
     * 5) The newline-separated configured answers are parsed into an array
     * and stored as the CAPTCHA's valid value.
     */
    public function testCreateCaptchaInputFieldParsesAnswersFromConfig(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => "blue\nlight blue"]);

        $captcha->createCaptchaInputField('myform');

        $this->assertSame(['blue', 'light blue'], $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 6) Carriage return characters (from Windows-style "\r\n" line endings
     * in the textarea value) are stripped before splitting into answers.
     */
    public function testCreateCaptchaInputFieldStripsCarriageReturns(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => "blue\r\nlight blue\r\n"]);

        $captcha->createCaptchaInputField('myform');
        $answers = $captcha->exposeGetCaptchaValidValue();

        foreach ($answers as $answer) {
            $this->assertStringNotContainsString("\r", $answer);
        }
    }

    /**
     * 7) With no answers configured at all, the CAPTCHA's valid value stays
     * at its default (empty array) - setCaptchaValidValue() is simply never
     * called.
     */
    public function testCreateCaptchaInputFieldLeavesValidValueEmptyWhenNoAnswersConfigured(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => '']);

        $captcha->createCaptchaInputField('myform');

        $this->assertSame([], $captcha->exposeGetCaptchaValidValue());
    }

    /**
     * 7b) REGRESSION TEST for the fixed bug: a blank line between two real
     * answers (e.g. accidental extra newline while editing the module
     * config) used to produce an empty string as a spurious "valid
     * answer" alongside the real ones. Now blank lines are filtered out
     * and whitespace around each answer is trimmed.
     */
    public function testCreateCaptchaInputFieldFiltersBlankLinesFromAnswers(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => "blue\n\n  light blue  \n   "]);

        $captcha->createCaptchaInputField('myform');

        $this->assertSame(['blue', 'light blue'], $captcha->exposeGetCaptchaValidValue());
    }

    // --- createCaptchaInputField() - other field setup ---

    /**
     * 8) The "checkCaptcha" validation rule is not present on the returned
     * field - this CAPTCHA type validates answers itself (via the valid
     * value list), not through the generic checkCaptcha rule used by other
     * CAPTCHA types.
     */
    public function testCreateCaptchaInputFieldDoesNotHaveCheckCaptchaRule(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => 'blue']);

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertArrayNotHasKey('checkCaptcha', $field->getRules());
    }

    /**
     * 9) The field's notes are set to the CAPTCHA's description text.
     */
    public function testCreateCaptchaInputFieldSetsNotesToDescription(): void
    {
        $captcha = new ExposedSimpleQuestionCaptcha();
        $this->setLangId($captcha, '');
        $this->setConfig($captcha, ['input_question' => 'Q?', 'input_answers' => 'blue']);

        $field = $captcha->createCaptchaInputField('myform');

        $this->assertSame($captcha->desc, $field->getNotes()->getText());
    }
}
