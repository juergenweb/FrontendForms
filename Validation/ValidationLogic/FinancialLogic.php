<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Contains all financial validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class FinancialLogic extends BaseLogic
{
    /**
     * Helper service for financial validations.
     */
    private FinancialHelper $financialHelper;

    /**
     * Create a new FinancialLogic instance.
     *
     * @param FinancialHelper $financialHelper Helper dependency for financial-related operations.
     */
    public function __construct(FinancialHelper $financialHelper)
    {
        parent::__construct();

        $this->financialHelper = $financialHelper;
    }

    /**
     * Validate whether a value is a syntactically and checksum-valid IBAN.
     * Empty values are treated as valid (required-field validation is handled separately).
     *
     * @param string $_field Current field name (unused).
     * @param mixed  $value  Value to validate.
     * @param array  $_params Additional validator parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or a valid IBAN.
     */
    public function validateIban(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        // Remove spaces and convert to uppercase letters
        $iban = strtoupper(str_replace(' ', '', $value));

        $len = strlen($iban);

        if ($len < 15 || $len > 34) {
            return false;
        }

        $countryCode = substr($iban, 0, 2);
        $expectedLength = $this->financialHelper->getIbanLength($countryCode);

        if ($expectedLength === null || $len !== $expectedLength) {
            return false;
        }

        if (!ctype_alnum($iban)) {
            return false;
        }

        return $this->financialHelper->validateIBANChecksum($iban);
    }

    /**
     * Validate whether a value is a syntactically valid BIC/SWIFT code.
     * Empty values are treated as valid (required-field validation is handled separately).
     *
     * @param string $_field Current field name (unused).
     * @param mixed  $value  Value to validate.
     * @param array  $_params Additional validator parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or a valid BIC.
     */
    public function validateBic(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        $bic = strtoupper(str_replace(' ', '', $value));
        $len = strlen($bic);

        if ($len !== 8 && $len !== 11) {
            return false;
        }

        if (!ctype_alpha(substr($bic, 0, 4))) {
            return false;
        }

        $countryCode = substr($bic, 4, 2);

        if (!$this->financialHelper->isValidCountryCode($countryCode)) {
            return false;
        }

        if (!ctype_alnum(substr($bic, 6, 2))) {
            return false;
        }

        if ($len === 11 && !ctype_alnum(substr($bic, 8, 3))) {
            return false;
        }

        return true;
    }
}