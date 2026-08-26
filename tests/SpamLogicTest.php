<?php

declare(strict_types=1);

namespace Tests;

use FrontendForms\Form;
use FrontendForms\SpamHelper;
use FrontendForms\SpamLogic;
use FrontendForms\TextHelper;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Unit tests for SpamLogic::validateContentForSpam() and SpamHelper::calculateContentScore().
 *
 * Covers: threshold handling, score comparison, exclusion lists,
 * parameter validation, and spam scoring heuristics.
 */
final class SpamLogicTest extends TestCase
{
    private SpamLogic $logic;

    private SpamHelper $spamHelper;

    /**
     * Create a SpamLogic instance with a mocked SpamHelper shared across all tests.
     */
    protected function setUp(): void
    {
        $this->spamHelper = $this->createMock(SpamHelper::class);

        $this->logic = new SpamLogic($this->spamHelper);

        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $this->logic->setForm($form);
        $this->spamHelper->setForm($form);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: checkContentForSpam
    | Method: validateContentForSpam
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true when threshold is zero.
     */
    public function testValidateContentForSpamReturnsTrueWhenThresholdIsZero(): void
    {
        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Buy now',
                [0],
                []
            )
        );
    }

    /**
     * Verifies that it uses the default threshold of 50 when no threshold is provided.
     */
    public function testValidateContentForSpamUsesDefaultThreshold(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(50);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello World',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when score is below default threshold.
     */
    public function testValidateContentForSpamReturnsTrueWhenScoreIsBelowDefaultThreshold(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(20);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when score is below set threshold of 40.
     */
    public function testValidateContentForSpamReturnsTrueWhenScoreIsBelowThreshold(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(20);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello',
                [40],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when score equals threshold limit.
     */
    public function testValidateContentForSpamReturnsTrueWhenScoreEqualsThresholdLimit(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(50);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello',
                [50],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when score exceeds threshold limit.
     */
    public function testValidateContentForSpamReturnsFalseWhenScoreExceedsThreshold(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(51);

        $this->assertFalse(
            $this->logic->validateContentForSpam(
                'message',
                'Buy Viagra',
                [50],
                []
            )
        );
    }

    /**
     * Verifies that an exception is thrown if the threshold value exceeds 100.
     */
    public function testValidateContentForSpamCapsThresholdAtOneHundred(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Param[0] must be an integer between 0 and 100.');

        $this->logic->validateContentForSpam(
            'message',
            'Some text',
            [
                200,
                ['links', 'stopwords']
            ],
            []
        );
    }

    /**
     * Verifies that the result is true for threshold one hundred and score zero.
     */
    public function testValidateContentForSpamReturnsTrueWhenThresholdIsOneHundredAndScoreIsZero(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(0);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Normal text',
                [100],
                []
            )
        );
    }

    /**
     * Verifies that the result is false for threshold one hundred and score greater than zero.
     */
    public function testValidateContentForSpamReturnsFalseWhenThresholdIsOneHundredAndScoreIsGreaterThanZero(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(1);

        $this->assertFalse(
            $this->logic->validateContentForSpam(
                'message',
                'Spam text',
                [100],
                []
            )
        );
    }

    /**
     * Verifies that stopwords are passed from module configuration.
     */
    public function testValidateContentForSpamPassesStopwordsToSpamHelper(): void
    {
        $this->spamHelper
            ->expects($this->once())
            ->method('calculateContentScore');

        $this->logic->validateContentForSpam(
            'message',
            'Hello',
            [50],
            []
        );
    }

    /**
     * Verifies that an exception is thrown if negative threshold is set
     */
    public function testValidateContentForSpamHandlesNegativeThreshold(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Param[0] must be an integer between 0 and 100.');

        $this->logic->validateContentForSpam(
            'message',
            'Some text',
            [
                -10
            ],
            []
        );
    }

    /**
     * Verifies that an exclusion array threshold as very permissive.
     */
    public function testValidateContentForSpamHandlesExclusionArray(): void
    {
        $this->spamHelper
            ->method('calculateContentScore')
            ->willReturn(10);

        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello',
                [10, ['stopwords', 'links']],
                []
            )
        );
    }

    /**
     * Verifies that an exception is thrown when spam exclusion parameter is an empty array.
     */
    public function testValidateContentForSpamThrowsExceptionWhenParameter2IsEmptyArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Param[1] cannot be empty. Add items or remove it.');

        $this->logic->validateContentForSpam(
            'message',
            'Some text',
            [
                50,
                []
            ],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when spam exclusion parameter is not empty but contains not only strings as items.
     */
    public function testValidateContentForSpamThrowsExceptionWhenParameter2IsContainsNoStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All Param[1] items must be strings.');

        $this->logic->validateContentForSpam(
            'message',
            'Some text',
            [
                50,
                ['links', null]
            ],
            []
        );
    }

    /**
     * Verifies that a noninteger value for the threshold parameter throws an exception.
     */
    public function testValidateContentForSpamThrowsExceptionWhenParameter1IsNotAnInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Param[0] must be an integer between 0 and 100.');

        $this->logic->validateContentForSpam(
            'message',
            'Some text',
            [
                null,
                ['links', 'stopwords']
            ],
            []
        );
    }

    /**
     * Verifies that the result is true for a normal non-spam message.
     */
    public function testValidateContentForSpamReturnsTrueForNormalText(): void
    {
        $this->assertTrue(
            $this->logic->validateContentForSpam(
                'message',
                'Hello, I would like to know more about your services. Thank you for your time.',
                [50],
                []
            )
        );
    }

    /**
     * Verifies that an obvious spam text receives a score greater than zero.
     */
    public function testCalculateContentScoreWithSpamText(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $textHelper = new TextHelper();
        $textHelper->setForm($form);

        $spamHelper = new SpamHelper($textHelper);
        $spamHelper->setForm($form);

        $score = $spamHelper->calculateContentScore(
            'BUY NOW!!! CLICK HERE!!! FREE MONEY!!! https://spam1.com https://spam2.com https://spam3.com',
            [],
            null
        );

        $this->assertGreaterThan(0, $score);
    }

    /**
     * Verifies that an obvious spam text scores below 50 points.
     */
    public function testCalculateContentScoreWithSpamText35Points(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $textHelper = new TextHelper();
        $textHelper->setForm($form);

        $spamHelper = new SpamHelper($textHelper);
        $spamHelper->setForm($form);

        // 35 points
        $score = $spamHelper->calculateContentScore(
            'BUY NOW https://a.com https://b.com https://c.com',
            [],
            null
        );

        $this->assertLessThan(50, $score);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: checkCaptcha
    | Method: validateCaptcha
    |--------------------------------------------------------------------------
    */

    public function testValidateCaptchaThrowsExceptionWhenExpectedValueIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing parameter: expected CAPTCHA value.');

        $this->logic->validateCaptcha(
            'captcha',
            'abc',
            [],
            []
        );
    }

    /**
     * Returns true when the submitted CAPTCHA value is null.
     */
    public function testValidateCaptchaReturnsTrueForNullValue(): void
    {
        $this->assertTrue(
            $this->logic->validateCaptcha(
                'captcha',
                null,
                ['abc'],
                []
            )
        );
    }

    /**
     * Returns true when the submitted CAPTCHA value is an empty string.
     */
    public function testValidateCaptchaReturnsTrueForEmptyString(): void
    {
        $this->assertTrue(
            $this->logic->validateCaptcha(
                'captcha',
                '',
                ['abc'],
                []
            )
        );
    }

    /**
     * Returns true when the submitted CAPTCHA matches the expected value.
     */
    public function testValidateCaptchaReturnsTrueForMatchingValue(): void
    {
        $this->assertTrue(
            $this->logic->validateCaptcha(
                'captcha',
                'Captcha123',
                ['Captcha123'],
                []
            )
        );
    }

    /**
     * Returns true when the submitted CAPTCHA matches case-insensitively.
     */
    public function testValidateCaptchaReturnsTrueForCaseInsensitiveMatch(): void
    {
        $this->assertTrue(
            $this->logic->validateCaptcha(
                'captcha',
                'cApTcHa123',
                ['CAPTCHA123'],
                []
            )
        );
    }

    /**
     * Returns false when the submitted CAPTCHA does not match.
     */
    public function testValidateCaptchaReturnsFalseForNonMatchingValue(): void
    {
        $this->assertFalse(
            $this->logic->validateCaptcha(
                'captcha',
                'wrong',
                ['expected'],
                []
            )
        );
    }

    /**
     * Returns false when the submitted CAPTCHA value is not scalar.
     */
    public function testValidateCaptchaReturnsFalseForNonScalarValue(): void
    {
        $this->assertFalse(
            $this->logic->validateCaptcha(
                'captcha',
                ['expected'],
                ['expected'],
                []
            )
        );
    }

    /**
     * Returns true after normalizing a scalar value before comparison.
     */
    public function testValidateCaptchaReturnsTrueForNormalizedScalarValue(): void
    {
        $this->assertTrue(
            $this->logic->validateCaptcha(
                'captcha',
                12345,
                ['12345'],
                []
            )
        );
    }
}