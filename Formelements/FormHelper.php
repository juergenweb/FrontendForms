<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Collection of small, self-contained utility methods extracted from Form.php.
 * None of these depend on a Form instance's own state - each is a pure
 * function of its own parameters (or uses only global ProcessWire API
 * functions), so they were moved here as static methods to keep Form.php
 * smaller and to make them independently testable.
 *
 * Created by Claude AI as part of a Form.php size-reduction refactor.
 */

use Exception;
use ProcessWire\Field;
use ProcessWire\Password;

class FormHelper
{
    /**
     * Check whether a given path/filename string contains a real directory
     * path (not just a bare filename in the current directory)
     * @param string $pathfilename
     * @return bool
     */
    public static function checkForPath(string $pathfilename): bool
    {
        $pathInfo = pathinfo($pathfilename);
        if ($pathInfo['dirname'] !== '.') {
            return true;
        }
        return false;
    }

    /**
     * Check if the SeoMaestro module is installed and, if so, return its
     * SEO field
     * @return Field|null
     */
    public static function getSeoMaestro(): ?Field
    {
        if (\ProcessWire\wire('modules')->isInstalled("SeoMaestro")) {
            // grab seo maestro input field
            $seoField = \ProcessWire\wire('fields')->find('type=FieldtypeSeoMaestro');
            if ($seoField) {
                return $seoField->first();
            }
        }
        return null;
    }

    /**
     * Sanitize a string, integer or boolean value to an integer value of 1 or 0
     * This is necessary, because configuration values of checkboxes are stored as integers in the db
     * @param string|int|bool $value
     * @return int
     */
    public static function sanitizeValueToInt(string|int|bool $value): int
    {
        if (is_string($value)) {
            if ($value !== '') {
                return 1;
            }
            return 0;
        } else {
            if (is_int($value)) {
                if ($value >= 1) {
                    return 1;
                }
                return 0;
            } else {
                return (int) $value;
            }
        }
    }

    /**
     * Convert the complicated $_FILES array (for a multi-file upload field)
     * into a simpler, per-file-indexed array
     * @param array $files
     * @return array
     */
    public static function simplifyMultiFileArray(array $files = []): array
    {
        $sFiles = [];
        if (is_array($files) && $files['error'] != '4') {
            foreach ($files as $key => $file) {
                foreach ($file as $index => $attr) {
                    $sFiles[$index][$key] = $attr;
                }
            }
        }
        return $sFiles;
    }

    /**
     * Put the "required" (or "fileRequired") rule always in the first
     * place of a field's validation rules array
     * Checking if a value is present is always logically the first step
     * before checking for other things
     * @param array $rules
     * @return array
     */
    public static function putRequiredOnTop(array $rules): array
    {
        if (count($rules) > 1) {
            if (array_key_exists('required', $rules)) {
                if (array_key_exists('fileRequired', $rules)) {
                    $rules = ['fileRequired' => $rules['fileRequired']] + $rules;
                } else {
                    $rules = ['required' => $rules['required']] + $rules;
                }
            }
        }
        return $rules;
    }

    /**
     * Move an array item from one position to another position
     * @param array $array
     * @param $key
     * @param int $order
     * @return void
     * @throws Exception
     */
    public static function repositionArrayElement(array &$array, $key, int $order): void
    {
        if (($a = array_search($key, array_keys($array))) === false) {
            throw new Exception("The $key cannot be found in the given array.");
        }
        $p1 = array_splice($array, $a, 1);
        $p2 = array_splice($array, 0, $order);
        $array = array_merge($p2, $p1, $array);
    }

    /**
     * Static method to encrypt/decrypt a string according to the encryption settings
     * @param string $string
     * @param string $method
     * @return string
     */
    public static function encryptDecrypt(string $string, string $method = 'encrypt'): string
    {
        // encryption settings
        $encrypt_method = 'AES-256-CBC';
        $secret_key = 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282';
        $secret_iv = '5fgf5HJ5g27';
        $algo = 'sha256';
        // user define secret key
        $key = hash($algo, $secret_key);
        $iv = substr(hash($algo, $secret_iv), 0, 16);
        $methods = ['encrypt', 'decrypt'];
        if (in_array($method, $methods)) {
            if ($method === 'encrypt') {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                return base64_encode($output);
            } else {
                return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }
        }
        return $string;
    }

    /**
     * Create a random string with a certain length for usage in URL query strings
     * @param int $charLength - the length of the random string - default is 100
     * @return string - returns a slug version of the generated random string that can be used inside an url
     */
    public static function createQueryCode(int $charLength = 100): string
    {
        $pass = new Password();
        if ($charLength <= 0) {
            $charLength = 10;
        }
        // instantiate a password object to use the methods
        $string = $pass->randomBase64String($charLength);
        return self::generateSlug($string);
    }

    /**
     * Generate a slug out of a string for usage in urls (fe query strings)
     * This is only a helper function
     * @param $string - the string
     * @return string
     */
    public static function generateSlug(string $string): string
    {
        return preg_replace('/[^A-Za-z\d-]+/', '-', $string);
    }
}