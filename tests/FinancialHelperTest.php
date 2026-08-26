<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FinancialHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FinancialHelper.
 *
 * The IBAN checksum expectations were confirmed by running the exact same
 * algorithm standalone against well-known official IBAN examples before
 * writing these assertions.
 */
final class FinancialHelperTest extends TestCase
{
    // --- validateIBANChecksum() ---

    /**
     * 1) A real, valid German IBAN (the official example IBAN used in
     * banking documentation) passes the checksum check.
     */
    public function testValidateIBANChecksumAcceptsValidGermanIban(): void
    {
        $helper = new FinancialHelper();

        $this->assertTrue($helper->validateIBANChecksum('DE89370400440532013000'));
    }

    /**
     * 2) A real, valid UK IBAN (official example) passes the checksum
     * check.
     */
    public function testValidateIBANChecksumAcceptsValidUkIban(): void
    {
        $helper = new FinancialHelper();

        $this->assertTrue($helper->validateIBANChecksum('GB29NWBK60161331926819'));
    }

    /**
     * 3) A real, valid French IBAN (official example, includes a letter
     * in the BBAN portion) passes the checksum check.
     */
    public function testValidateIBANChecksumAcceptsValidFrenchIbanWithLetter(): void
    {
        $helper = new FinancialHelper();

        $this->assertTrue($helper->validateIBANChecksum('FR1420041010050500013M02606'));
    }

    /**
     * 4) A valid IBAN with a single digit altered fails the checksum
     * check.
     */
    public function testValidateIBANChecksumRejectsCorruptedIban(): void
    {
        $helper = new FinancialHelper();

        $this->assertFalse($helper->validateIBANChecksum('DE89370400440532013001'));
    }

    // --- isValidCountryCode() ---

    /**
     * 5) A real, well-known ISO 3166-1 alpha-2 country code is recognized.
     */
    public function testIsValidCountryCodeAcceptsKnownCode(): void
    {
        $helper = new FinancialHelper();

        $this->assertTrue($helper->isValidCountryCode('DE'));
        $this->assertTrue($helper->isValidCountryCode('US'));
        $this->assertTrue($helper->isValidCountryCode('GB'));
    }

    /**
     * 6) A made-up, non-existent country code is rejected.
     */
    public function testIsValidCountryCodeRejectsUnknownCode(): void
    {
        $helper = new FinancialHelper();

        $this->assertFalse($helper->isValidCountryCode('ZZ'));
        $this->assertFalse($helper->isValidCountryCode('XX'));
    }

    /**
     * 7) The check is case-sensitive - a valid code in lowercase is not
     * recognized, matching the documented expectation that input is
     * already uppercase.
     */
    public function testIsValidCountryCodeIsCaseSensitive(): void
    {
        $helper = new FinancialHelper();

        $this->assertFalse($helper->isValidCountryCode('de'));
    }

    // --- getIbanLength() ---

    /**
     * 8) The expected IBAN length for well-known countries matches the
     * real-world IBAN specification.
     */
    public function testGetIbanLengthReturnsCorrectLengthForKnownCountries(): void
    {
        $helper = new FinancialHelper();

        $this->assertSame(22, $helper->getIbanLength('DE'));
        $this->assertSame(22, $helper->getIbanLength('GB'));
        $this->assertSame(27, $helper->getIbanLength('FR'));
        $this->assertSame(18, $helper->getIbanLength('NL'));
    }

    /**
     * 9) An unknown/non-IBAN country code returns null.
     */
    public function testGetIbanLengthReturnsNullForUnknownCountry(): void
    {
        $helper = new FinancialHelper();

        $this->assertNull($helper->getIbanLength('ZZ'));
    }
}
