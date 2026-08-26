<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\CaptchaConfig;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CaptchaConfig.
 *
 * CaptchaConfig is a plain value object with no behaviour - these tests
 * exist to lock down its default values and to guard against typos in
 * property names/types being silently reintroduced during future edits
 * (e.g. via CaptchaManager, which reads and writes these properties by name).
 */
final class CaptchaConfigTest extends TestCase
{
    /**
     * 1) A freshly created CaptchaConfig has the documented default values
     * for every property.
     */
    public function testDefaultsAreAsExpected(): void
    {
        $config = new CaptchaConfig();

        $this->assertSame('none', $config->type);
        $this->assertSame('', $config->category);
        $this->assertNull($config->position);
        $this->assertSame('', $config->successMsg);
        $this->assertSame('', $config->errorMsg);
        $this->assertSame('', $config->requiredErrorMsg);
        $this->assertSame('', $config->notes);
        $this->assertSame('', $config->description);
        $this->assertSame('', $config->descriptionPosition);
        $this->assertSame('', $config->placeholder);
        $this->assertFalse($config->removeLabel);
        $this->assertFalse($config->useLabelAsPlaceholder);
        $this->assertFalse($config->showValueOnSameQuestionAgain);
    }

    /**
     * 2) Every string/array property can be overwritten and reads back
     * exactly the assigned value (plain property access, no magic getters).
     */
    public function testStringAndArrayPropertiesCanBeSetAndReadBack(): void
    {
        $config = new CaptchaConfig();

        $config->type = 'SimpleQuestionCaptcha';
        $config->category = 'text';
        $config->position = ['email' => 'after'];
        $config->successMsg = 'Correct!';
        $config->errorMsg = 'Wrong answer.';
        $config->requiredErrorMsg = 'This field is required.';
        $config->notes = 'Please solve the riddle.';
        $config->description = 'Anti-spam question';
        $config->descriptionPosition = 'afterLabel';
        $config->placeholder = 'Your answer';

        $this->assertSame('SimpleQuestionCaptcha', $config->type);
        $this->assertSame('text', $config->category);
        $this->assertSame(['email' => 'after'], $config->position);
        $this->assertSame('Correct!', $config->successMsg);
        $this->assertSame('Wrong answer.', $config->errorMsg);
        $this->assertSame('This field is required.', $config->requiredErrorMsg);
        $this->assertSame('Please solve the riddle.', $config->notes);
        $this->assertSame('Anti-spam question', $config->description);
        $this->assertSame('afterLabel', $config->descriptionPosition);
        $this->assertSame('Your answer', $config->placeholder);
    }

    /**
     * 3) Every boolean property can be overwritten and reads back exactly
     * the assigned value.
     */
    public function testBooleanPropertiesCanBeSetAndReadBack(): void
    {
        $config = new CaptchaConfig();

        $config->removeLabel = true;
        $config->useLabelAsPlaceholder = true;
        $config->showValueOnSameQuestionAgain = true;

        $this->assertTrue($config->removeLabel);
        $this->assertTrue($config->useLabelAsPlaceholder);
        $this->assertTrue($config->showValueOnSameQuestionAgain);
    }

    /**
     * 4) The nullable string properties genuinely accept null (not just
     * empty string), since CaptchaManager/Form code checks some of them
     * with strict null-aware conditions.
     */
    public function testNullableStringPropertiesAcceptNull(): void
    {
        $config = new CaptchaConfig();

        $config->successMsg = null;
        $config->errorMsg = null;
        $config->requiredErrorMsg = null;
        $config->notes = null;
        $config->description = null;
        $config->descriptionPosition = null;
        $config->placeholder = null;

        $this->assertNull($config->successMsg);
        $this->assertNull($config->errorMsg);
        $this->assertNull($config->requiredErrorMsg);
        $this->assertNull($config->notes);
        $this->assertNull($config->description);
        $this->assertNull($config->descriptionPosition);
        $this->assertNull($config->placeholder);
    }

    /**
     * 5) Two separate CaptchaConfig instances are fully independent -
     * setting a property on one must not affect the other.
     */
    public function testInstancesAreIndependent(): void
    {
        $configA = new CaptchaConfig();
        $configB = new CaptchaConfig();

        $configA->type = 'SliderCaptcha';

        $this->assertSame('SliderCaptcha', $configA->type);
        $this->assertSame('none', $configB->type);
    }
}
