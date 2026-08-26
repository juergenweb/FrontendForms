<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;
use ProcessWire\Wire;
use ProcessWire\WireException;
use ProcessWire\WireInputData;

/**
 * Base helper class providing shared utility methods
 * for form handling, input access, and validation support.
 *
 * All methods are designed to work safely with
 * ProcessWire request data.
 */
abstract class BaseHelper extends Wire
{
    protected string $formID = '';

    protected ?Form $form = null;

    /**
     * Assign the current form instance and cache its ID for later use.
     *
     * @param Form $form The form instance this helper operates on.
     *
     * @return void
     */
    public function setForm(Form $form): void
    {
        $this->form = $form;
        $this->formID = $form->getID();
    }

    /**
     * Get the form instance previously assigned via setForm().
     *
     * @return Form The current form instance.
     *
     * @throws InvalidArgumentException If setForm() has not been called yet.
     */
    protected function getForm(): Form
    {
        if ($this->form === null) {
            throw new InvalidArgumentException(
                'getForm() was called before setForm() was set on this helper instance.'
            );
        }

        return $this->form;
    }

    /**
     * Normalize and sanitize scalar input values.
     *
     * This method:
     * - rejects non-scalar values
     * - trims surrounding whitespace
     * - converts empty strings to null
     *
     * Examples:
     * - " test " => "test"
     * - "" => null
     * - [] => null
     *
     * @param mixed $value The value to normalize.
     *
     * @return string|null Returns the normalized string value or null.
     */
    final public static function normalizeScalar(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    /**
     * Check whether a string, or all strings in an array,
     * contain at least one letter.
     *
     * @param string|array $value The value (or array of values) to check.
     *
     * @return bool True if every value contains at least one letter.
     */
    final public static function allValuesContainLetters(string|array $value): bool
    {
        if (is_string($value)) {
            return preg_match('/\p{L}/u', $value) === 1;
        }

        foreach ($value as $v) {
            if (!is_string($v)) {
                return false;
            }

            if (preg_match('/\p{L}/u', $v) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Normalize a mixed input value into an integer, or null if it
     * cannot be safely interpreted as one.
     *
     * @param mixed $value The value to normalize.
     *
     * @return int|null Returns the normalized integer or null.
     */
    final public static function normalizeInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (!preg_match('/^-?\d+$/', $value)) {
                return null;
            }

            return (int) $value;
        }

        return null;
    }

    /**
     * Normalize a non-negative integer value.
     *
     * @param mixed $value
     *
     * @return int|null
     */
    final public static function normalizeNonNegativeInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
            return null;
        }

        $int = (int) $value;

        return $int >= 0 ? $int : null;
    }

    /**
     * Validate if value is a positive integer or not - must be greater than 0.
     *
     * @param mixed $value
     *
     * @return bool
     */
    final public static function isPositiveInt(mixed $value): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        return filter_var($value, FILTER_VALIDATE_INT) !== false
            && (int) $value > 0;
    }

    /**
     * Validate if value is a positive int, otherwise throws an exception.
     * Returns a positive integer.
     *
     * Note: only the first array element is relevant for this rule;
     * any additional elements are intentionally ignored.
     *
     * @param array  $value
     * @param string $validator
     *
     * @return int
     *
     * @throws InvalidArgumentException
     */
    final public static function getPositiveInt(array $value, string $validator): int
    {
        if (!self::isPositiveInt($value[0] ?? null)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Please use only a positive integer as param for the %s validation rule.',
                    $validator
                )
            );
        }

        return (int) $value[0];
    }

    /**
     * Check whether a POST value has the structural signature of an
     * upload field (an array of file entries with 'tmp_name' and 'error' keys).
     *
     * @param mixed $value The POST value to check.
     *
     * @return bool True if the value looks like an upload field's data.
     */
    final public static function isUploadField(mixed $value): bool
    {
        return is_array($value)
            && isset($value[0]['tmp_name'], $value[0]['error']);
    }

    /**
     * Validate that the form contains at least 2 fields, otherwise throw
     * an exception.
     *
     * @param array $strings
     *
     * @return void
     *
     * @throws InvalidArgumentException If the form has fewer than 2 fields.
     */
    final public function assertAtLeastTwoFields(array $strings): void
    {
        if (count($strings) < 2) {
            throw new InvalidArgumentException(
                'This validation rule requires at least 2 fields inside the form.'
            );
        }
    }

}