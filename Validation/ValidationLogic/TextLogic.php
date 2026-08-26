<?php

declare(strict_types=1);

namespace FrontendForms;

use Normalizer;

/**
 * Contains all text validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class TextLogic extends BaseLogic
{
    private TextHelper $textHelper;

    /**
     * Create a new TextLogic instance.
     *
     * @param TextHelper $textHelper Helper dependency for text operations.
     */
    public function __construct(TextHelper $textHelper)
    {
        parent::__construct();

        $this->textHelper = $textHelper;
    }

    /**
     * Validate that the submitted value exactly matches the value defined in $params[0].
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $params  Rule parameters; $params[0] = expected value.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value equals the expected param value.
     */
    public function validateExactValue(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        $paramValue = $this->checkForParam($params);

        return $value === $paramValue[0];
    }

    /**
     * Validate that the submitted value differs from the value defined in $params[0].
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $params  Rule parameters; $params[0] = disallowed value.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value does not equal the disallowed param value.
     */
    public function validateNoneExactValue(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        $paramValue = $this->checkForParam($params);

        return $value !== $paramValue[0];
    }

    /**
     * Validate that the submitted value matches at least one of the allowed comparison
     * strings defined in $params (case-insensitive). Used as a CAPTCHA helper.
     * Empty comparison arrays are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $params  Rule parameters; list of accepted answer strings.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value matches one of the accepted answers.
     */
    public function validateTextComparison(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        $comparisonValues = $this->resolveStringParam($params, 'Texts for comparison');

        if ($comparisonValues === []) {
            return true;
        }

        $value = mb_strtolower((string) $value);

        foreach ($comparisonValues as $answer) {
            if (mb_strtolower($answer) === $value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate that the submitted value is a valid Cyrillic name.
     * Empty values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or a valid Cyrillic name.
     */
    public function validateCyrillicName(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if (!is_string($value)) {
            return true;
        }

        $value = trim($value);

        if ($value === '') {
            return true;
        }

        if (class_exists('Normalizer')) {
            $value = Normalizer::normalize($value, Normalizer::FORM_C);
        }

        $pattern = '/\A[а-яё]+(?:[ \-][а-яё]+)*\z/ui';

        return preg_match($pattern, $value) === 1;
    }

    /**
     * Validate that the submitted value contains no Unicode letters.
     * Non-string values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value contains no letters.
     */
    public function validateNoLetters(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if (!is_string($value)) {
            return true;
        }

        return preg_match('/\p{L}/u', $value) !== 1;
    }

    /**
     * Validate that the submitted value contains no Unicode digits or numeric characters.
     * Non-string values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value contains no numeric characters.
     */
    public function validateNoNumbers(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if (!is_string($value)) {
            return true;
        }

        return preg_match('/\p{N}/u', $value) !== 1;
    }

    /**
     * Validate that a submitted string value is unique across a given ProcessWire field,
     * optionally scoped to one or more templates.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $params  [0] PW field name, [1] optional template name or array of templates.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if no page exists with this field value (optionally within the given templates).
     */
    public function validateUniqueStringValueOfPWField(
        string $_field,
        mixed $value,
        array $params,
        array $_fields
    ): bool {
        $param = $this->resolveStringParam($params, 'name of the PW field');
        $fieldName = $param[0];

        $value = $this->sanitizer->string($value);
        $value = $this->sanitizer->selectorValue($value);

        $selector = count($params) > 1
            ? "template=" . (is_string($params[1]) ? $params[1] : implode('|', $params[1])) . ",$fieldName=$value, limit=1"
            : "$fieldName=$value, include=all, limit=1";

        return !$this->pages->find($selector)->count();
    }

    /**
     * Validate that a name (first name, last name) contains only allowed letters,
     * spaces, hyphens, apostrophes, and dots. Empty values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or contains only allowed characters.
     */
    public function validateNames(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        static $pattern = '/^[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð ,.\'-]+$/u';

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        return (bool) preg_match($pattern, $value);
    }
}