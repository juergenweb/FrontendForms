<?php

declare(strict_types=1);

namespace Tests;

use FrontendForms\TextHelper;
use FrontendForms\PasswordHelper;
use FrontendForms\PasswordLogic;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PasswordLogic validation methods.
 *
 * Covers: validateMeetsPasswordConditions (password requirements:
 * minimum length of 6, must contain letters and numbers).
 */
final class PasswordLogicTest extends TestCase
{
    /**
     * Create a PasswordLogic instance shared across all tests.
     */
    protected function setUp(): void
    {
        $passwordHelper = new PasswordHelper();
        $textHelper = new TextHelper();
        $this->logic = new PasswordLogic($passwordHelper, $textHelper);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: meetsPasswordConditions
    | Method: validateMeetsPasswordConditions
    | Password requirements: minimum length = 6, must contain letters and numbers
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds when the password meets the password conditions.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrue(): void
    {
        $result = $this->logic->validateMeetsPasswordConditions(
            'field',
            'myPass1234',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails when the password does not meet the password conditions.
     */
    public function testValidateDoesNotMeetsPasswordConditionsReturnsFalse(): void
    {
        $result = $this->logic->validateMeetsPasswordConditions(
            'field',
            'myPass',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true for a valid password with minimum length.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForMinimumValidPassword(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'abcde1',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true for a valid longer password.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForValidPassword(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'MyPassword123',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password is shorter than six characters.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseWhenPasswordIsTooShort(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'abc1',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password contains no number.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseWhenPasswordContainsNoNumber(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'abcdef',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password contains no letter.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseWhenPasswordContainsNoLetter(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                '123456',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false for an empty password.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseForEmptyPassword(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                '',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false for a password containing only spaces.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseForWhitespaceOnlyPassword(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                '      ',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when password contains letters, numbers and special characters.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForPasswordWithSpecialCharacters(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'Pass123!',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when password contains exactly one letter and one number.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForBoundaryCase(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'a11111',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when password contains uppercase letters and numbers.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForUppercasePassword(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'ABCDEF1',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when password contains lowercase letters and numbers.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForLowercasePassword(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'abcdef1',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when password contains mixed case letters and numbers.
     */
    public function testValidateMeetsPasswordConditionsReturnsTrueForMixedCasePassword(): void
    {
        $this->assertTrue(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'AbCdE123',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password consists only of special characters.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseForSpecialCharactersOnly(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                '!@#$%^',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password consists only of letters even if long enough.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseForLettersOnlyLongPassword(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                'abcdefghijklmnopqrstuvwxyz',
                [],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when password consists only of numbers even if long enough.
     */
    public function testValidateMeetsPasswordConditionsReturnsFalseForNumbersOnlyLongPassword(): void
    {
        $this->assertFalse(
            $this->logic->validateMeetsPasswordConditions(
                'password',
                '12345678901234567890',
                [],
                []
            )
        );
    }

}