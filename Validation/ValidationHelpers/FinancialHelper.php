<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Class containing several financial specific functions
 */
class FinancialHelper extends BaseHelper
{
    /**
     * FinancialHelper constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Validate the checksum of an IBAN.
     *
     * @param string $iban IBAN without spaces and already uppercase.
     *
     * @return bool True if the IBAN's mod-97 checksum is valid.
     */
    public function validateIBANChecksum(string $iban): bool
    {
        // Move first 4 chars to the end
        $iban = substr($iban, 4) . substr($iban, 0, 4);

        $remainder = 0;
        $length = strlen($iban);

        for ($i = 0; $i < $length; $i++) {
            $char = $iban[$i];

            // Numbers: append directly
            if (ctype_digit($char)) {
                $remainder = ($remainder * 10 + (int) $char) % 97;
                continue;
            }

            // Letters: A = 10 ... Z = 35
            $value = ord($char) - 55;

            // Process each digit separately to avoid huge integers
            if ($value >= 10) {
                $remainder = ($remainder * 10 + intdiv($value, 10)) % 97;
                $remainder = ($remainder * 10 + ($value % 10)) % 97;
            } else {
                $remainder = ($remainder * 10 + $value) % 97;
            }
        }

        return $remainder === 1;
    }

    /**
     * Validates if a country code is valid (ISO 3166-1 alpha-2).
     *
     * @param string $countryCode The country code to validate.
     *
     * @return bool True if the country code is a recognized ISO 3166-1 alpha-2 code.
     */
    public function isValidCountryCode(string $countryCode): bool
    {
        static $map = null;

        $map ??= array_flip([
            'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
            'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS', 'BT', 'BV', 'BW', 'BY', 'BZ',
            'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN', 'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ',
            'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ',
            'EC', 'EE', 'EG', 'EH', 'ER', 'ES', 'ET',
            'FI', 'FJ', 'FK', 'FM', 'FO', 'FR',
            'GA', 'GB', 'GD', 'GE', 'GF', 'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY',
            'HK', 'HM', 'HN', 'HR', 'HT', 'HU',
            'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT',
            'JE', 'JM', 'JO', 'JP',
            'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ',
            'LA', 'LB', 'LC', 'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY',
            'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK', 'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ',
            'NA', 'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ',
            'OM',
            'PA', 'PE', 'PF', 'PG', 'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY',
            'QA',
            'RE', 'RO', 'RS', 'RU', 'RW',
            'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS', 'ST', 'SV', 'SX', 'SY', 'SZ',
            'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO', 'TR', 'TT', 'TV', 'TW', 'TZ',
            'UA', 'UG', 'UM', 'US', 'UY', 'UZ',
            'VA', 'VC', 'VE', 'VG', 'VI', 'VN', 'VU',
            'WF', 'WS',
            'XK',
            'YE', 'YT',
            'ZA', 'ZM', 'ZW',
        ]);

        return isset($map[$countryCode]);
    }

    /**
     * Return the expected total IBAN length for a given country code.
     *
     * @param string $countryCode Two-letter ISO country code (uppercase).
     *
     * @return int|null The expected IBAN length, or null if the country code is unknown.
     */
    public function getIbanLength(string $countryCode): ?int
    {
        static $lengths = [
            'AD' => 24, 'AE' => 23, 'AL' => 28, 'AT' => 20, 'AZ' => 28,
            'BA' => 20, 'BE' => 16, 'BG' => 22, 'BH' => 22, 'BR' => 29,
            'BY' => 28, 'CH' => 21, 'CR' => 22, 'CY' => 28, 'CZ' => 24,
            'DE' => 22, 'DK' => 18, 'DO' => 28, 'EE' => 20, 'EG' => 29,
            'ES' => 24, 'FI' => 18, 'FO' => 18, 'FR' => 27, 'GB' => 22,
            'GE' => 22, 'GI' => 23, 'GL' => 18, 'GR' => 27, 'GT' => 28,
            'HR' => 21, 'HU' => 28, 'IE' => 22, 'IL' => 23, 'IS' => 26,
            'IT' => 27, 'JO' => 30, 'KW' => 30, 'KZ' => 20, 'LB' => 28,
            'LC' => 32, 'LI' => 21, 'LT' => 20, 'LU' => 20, 'LV' => 21,
            'MC' => 27, 'MD' => 24, 'ME' => 22, 'MK' => 19, 'MR' => 27,
            'MT' => 31, 'MU' => 30, 'NL' => 18, 'NO' => 15, 'PK' => 24,
            'PL' => 28, 'PS' => 29, 'PT' => 25, 'QA' => 29, 'RO' => 24,
            'RS' => 22, 'SA' => 24, 'SE' => 24, 'SI' => 19, 'SK' => 24,
            'SM' => 27, 'TN' => 24, 'TR' => 26, 'UA' => 29, 'VA' => 22,
            'VG' => 24, 'XK' => 20,
        ];

        return $lengths[$countryCode] ?? null;
    }
}