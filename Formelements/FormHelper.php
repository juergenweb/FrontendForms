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
     *
     * @deprecated No longer called from Form::isValid() - superseded by
     * sortRulesByPriority(), which also guarantees required/fileRequired
     * run first (both have priority 0 in RULE_PRIORITIES) as part of its
     * general rule-ordering mechanism. It's also strictly more correct
     * than this method for the edge case where a field has BOTH
     * "required" and "fileRequired" set at once (a file upload field
     * with the required rule): this method only moves "fileRequired" to
     * the front, leaving "required" wherever it originally was, while
     * sortRulesByPriority() moves both to the front. Kept here, unused
     * internally, only in case external code still calls it directly.
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
     * Priority values used by sortRulesByPriority() to order a field's
     * validation rules before they're registered with the Valitron
     * validator, which runs rules in registration order. Lower values
     * run first - the same convention ProcessWire's own hook priority
     * system uses (default priority is 100). Any rule not listed here
     * keeps that default and, since the sort is stable, retains its
     * original relative order among other default-priority rules.
     *
     * MIME-type checks are prioritized ahead of extension checks: the
     * MIME type reflects the file's actual content, while the extension
     * is only a claim made by the filename - running the content-based
     * check first means a spoofed/mismatched file is already rejected
     * by the time the (weaker) extension check would run.
     *
     * required/fileRequired are also listed here (matching
     * putRequiredOnTop()'s own guarantee) so the two mechanisms stay
     * consistent with each other rather than only one of them deciding
     * what "first" means.
     *
     * To give another rule priority over others in the future, add it
     * here - no other code needs to change. Lower numbers run earlier;
     * leave gaps between values (as below) so new rules can be inserted
     * between existing ones without renumbering everything.
     */
    public const RULE_PRIORITIES = [
        'required' => 0,
        'fileRequired' => 0,
        'allowedMimeTypes' => 10,
        'forbiddenMimeTypes' => 10,
        'allowedFileExt' => 20,
        'forbiddenFileExt' => 20,
    ];

    /**
     * Sort a field's validation rules by priority - see RULE_PRIORITIES
     * for the current priority values and how to extend them. Rules
     * without an explicit entry default to priority 100 and keep their
     * original relative order (the sort is stable).
     * @param array $rules
     * @return array
     */
    public static function sortRulesByPriority(array $rules): array
    {
        if (count($rules) < 2) {
            return $rules;
        }

        $priorities = self::RULE_PRIORITIES;
        $names = array_keys($rules);
        usort($names, static fn (string $a, string $b): int
        => ($priorities[$a] ?? 100) <=> ($priorities[$b] ?? 100));

        $sorted = [];
        foreach ($names as $name) {
            $sorted[$name] = $rules[$name];
        }
        return $sorted;
    }

    /**
     * Keep allowedFileExt/forbiddenFileExt consistent with
     * allowedMimeTypes/forbiddenMimeTypes on the same field: if both a
     * MIME-type rule and its matching extension rule are present, any
     * extension in the extension rule that isn't actually possible for
     * one of the allowed/forbidden MIME types (per
     * MimeHelper::getAllValidExtensions()) is removed.
     *
     * This exists because the two rules describe overlapping ground
     * (MIME type and file extension) independently - without this, it's
     * possible to configure them so they contradict each other, e.g.
     * allowedMimeTypes => ['image/png'] together with
     * allowedFileExt => ['png', 'exe']: "exe" could never actually occur
     * for a file whose MIME type is image/png, but would still sit in
     * the extension allow-list, implying (incorrectly) that it's
     * accepted.
     *
     * Only extensions actually present in the field's own extension
     * rule are ever kept - this narrows that list, it never adds
     * extensions to it that weren't already there. If a field's
     * allowedFileExt/forbiddenFileExt end up empty after filtering, that
     * reflects a genuine configuration conflict (none of the configured
     * extensions are possible for any of the configured MIME types) and
     * is left as an empty array rather than silently falling back to
     * the original, contradictory list.
     *
     * Takes the field itself (not just its rules array) and, when a
     * filtered list actually differs from the current one, calls
     * $element->setRule() again with it - a plain array transformation
     * wouldn't be enough here, since addHTML5allowedFileExt()/
     * addHTML5forbiddenFileExt() (called from within setRule() itself)
     * is what sets the field's rendered HTML5 pattern/accept attribute;
     * only re-calling setRule() refreshes that attribute to match the
     * filtered list too. Must therefore be called early enough (see
     * Form::___isValid()) to run before the field is actually rendered,
     * not just before/during server-side validation of a submission.
     * @param Inputfields $element
     * @param MimeHelper $mimeHelper
     * @return void
     */
    public static function alignExtensionRulesWithMimeTypeRules(Inputfields $element, MimeHelper $mimeHelper): void
    {
        $rules = $element->getRules();

        $pairs = [
            'allowedMimeTypes' => 'allowedFileExt',
            'forbiddenMimeTypes' => 'forbiddenFileExt',
        ];

        foreach ($pairs as $mimeRuleName => $extRuleName) {
            if (!array_key_exists($mimeRuleName, $rules) || !array_key_exists($extRuleName, $rules)) {
                continue;
            }

            $mimeTypes = $rules[$mimeRuleName]['options'][0] ?? [];
            if (!is_array($mimeTypes)) {
                $mimeTypes = [$mimeTypes];
            }

            $possibleExtensions = [];
            foreach ($mimeTypes as $mimeType) {
                foreach ($mimeHelper->getAllValidExtensions((string) $mimeType) as $ext) {
                    // normalize for a robust, case-/dot-insensitive
                    // comparison below - the actual, original values
                    // from the field's own extension rule (not this
                    // normalized form) are what get kept in the result
                    $possibleExtensions[strtolower(ltrim($ext, '.'))] = true;
                }
            }

            $currentExtensions = $rules[$extRuleName]['options'][0] ?? [];
            if (!is_array($currentExtensions)) {
                $currentExtensions = [$currentExtensions];
            }
            $currentExtensions = array_values($currentExtensions);

            $filteredExtensions = array_values(array_filter(
                $currentExtensions,
                static fn ($ext) => isset($possibleExtensions[strtolower(ltrim((string) $ext, '.'))])
            ));

            // only re-set the rule if the filtered list actually differs -
            // avoids needlessly re-triggering setRule()'s other side
            // effects (rebuilding the note text, etc.) on every single
            // isValid() call when nothing would actually change
            if ($filteredExtensions !== $currentExtensions) {
                $element->setRule($extRuleName, $filteredExtensions);
            }
        }
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
