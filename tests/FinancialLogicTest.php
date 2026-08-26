<?php

declare(strict_types=1);

namespace Tests;

use FrontendForms\FinancialHelper;
use FrontendForms\FinancialLogic;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for financial validation logic.
 */
class FinancialLogicTest extends TestCase
{
    private FinancialLogic $logic;

    protected function setUp(): void
    {
        $financialHelper = new FinancialHelper();

        $this->logic = new FinancialLogic(
            $financialHelper
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: checkIban
    | Method: validateIban
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that a valid IBAN is accepted.
     */
    public function testValidIbanReturnsTrue(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE89370400440532013000',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that spaces are ignored during validation.
     */
    public function testValidIbanWithSpacesReturnsTrue(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE89 3704 0044 0532 0130 00',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that lowercase IBANs are normalized and accepted.
     */
    public function testLowercaseIbanReturnsTrue(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'de89370400440532013000',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that an IBAN shorter than 15 characters is rejected.
     */
    public function testTooShortIbanReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an IBAN longer than 34 characters is rejected.
     */
    public function testTooLongIbanReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            str_repeat('A', 35),
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an unknown country code is rejected.
     */
    public function testUnknownCountryCodeReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'ZZ89370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an IBAN with an invalid country-specific length is rejected.
     */
    public function testInvalidCountryLengthReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE8937040044053201300',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the IBAN must start with a letter.
     */
    public function testFirstCharacterMustBeLetter(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            '1E89370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the second character must be a letter.
     */
    public function testSecondCharacterMustBeLetter(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'D189370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the third character must be a digit.
     */
    public function testThirdCharacterMustBeDigit(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DEA9370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the fourth character must be a digit.
     */
    public function testFourthCharacterMustBeDigit(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE8A370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that IBANs containing special characters are rejected.
     */
    public function testIbanWithSpecialCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE89-370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an IBAN with an invalid checksum is rejected.
     */
    public function testInvalidChecksumReturnsFalse(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            'DE88370400440532013000',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that an empty string is rejected.
     */
    public function testEmptyIbanReturnsTrue(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a whitespace-only value will be treated as an empty field.
     */
    public function testWhitespaceOnlyIbanReturnsTrue(): void
    {
        $result = $this->logic->validateIban(
            'iban',
            '     ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: checkBic
    | Method: validateBic
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that a valid 8-character BIC is accepted.
     */
    public function testValid8CharBicReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEFF',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that a valid 11-character BIC is accepted.
     */
    public function testValid11CharBicReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEFF500',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that spaces are ignored and BIC is normalized.
     */
    public function testBicWithSpacesReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUT DEFF 500',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that lowercase BIC is normalized to uppercase and accepted.
     */
    public function testLowercaseBicReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'deutdeff',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that BIC shorter than 8 characters is rejected.
     */
    public function testTooShortBicReturnsFalse(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that BIC longer than 11 characters is rejected.
     */
    public function testTooLongBicReturnsFalse(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            str_repeat('A', 12),
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the first 4 characters must be letters (bank code).
     */
    public function testFirstFourMustBeLetters(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            '1234DEFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that characters 5-6 must be letters (country code).
     */
    public function testCountryCodeMustBeLetters(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUT1EFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that a BIC with invalid country code is rejected.
     */
    public function testInvalidCountryCodeReturnsFalse(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTZZFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that 11-character BIC must have valid branch code.
     */
    public function testInvalidBranchCodeReturnsFalse(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEFF$%#',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that 11-character BIC with valid branch code is accepted.
     */
    public function testValidBranchCodeReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEFF500',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that 8-character BIC must have valid format in last 2 characters.
     */
    public function testInvalid8CharBicFormatReturnsFalse(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            'DEUTDEF@',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that empty BIC will be treated as an empty field.
     */
    public function testEmptyBicReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that whitespace-only BIC will be treated as an empty input field
     */
    public function testWhitespaceOnlyBicReturnsTrue(): void
    {
        $result = $this->logic->validateBic(
            'bic',
            '     ',
            [],
            []
        );

        $this->assertTrue($result);
    }
}