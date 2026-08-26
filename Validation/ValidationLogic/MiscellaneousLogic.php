<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;

/**
 * Contains all miscellaneous validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class MiscellaneousLogic extends BaseLogic
{
    private MiscellaneousHelper $miscellaneousHelper;

    /**
     * Create a new MiscellaneousLogic instance.
     *
     * @param MiscellaneousHelper $miscellaneousHelper Helper dependency for miscellaneous operations.
     */
    public function __construct(MiscellaneousHelper $miscellaneousHelper)
    {
        parent::__construct();

        $this->miscellaneousHelper = $miscellaneousHelper;
    }





    /**
     * Validate that the submitted value is a valid CSS hex color code (#RGB or #RRGGBB).
     * Empty values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the value is empty or a valid hex color code.
     */
    public function validateHexValue(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        if ($value === null || $value === '') {
            return true;
        }

        return (bool) preg_match(
            '/\A#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})\z/',
            (string) $value
        );
    }









    /**
     * Validate that the current field has a value when a referenced field matches
     * one or more of the expected values.
     *
     * @param string $field  Current field name.
     * @param mixed  $value  Value to validate.
     * @param array  $params [0] comparison field name, [1] expected value(s), [2] optional operator ('and'|'or').
     * @param array  $fields Full validation dataset.
     *
     * @return bool True if the condition is not met, or the field has a value.
     *
     * @throws InvalidArgumentException If required parameters are missing or invalid.
     */
    public function validateRequiredIfEqual(
        string $field,
        mixed $value,
        array $params,
        array $fields
    ): bool {
        if (!isset($params[0], $params[1])) {
            throw new InvalidArgumentException(
                'Missing required params for "' . $field . '".'
            );
        }

        if (trim((string) $params[0]) === '') {
            throw new InvalidArgumentException('Comparison field name must not be empty.');
        }

        $this->miscellaneousHelper->assertAtLeastTwoFields($fields);

        $prefix = $this->formID;

        $baseField = $params[0];

        if ($prefix !== '' && str_starts_with($baseField, $prefix . '-')) {
            $baseField = substr($baseField, strlen($prefix) + 1);
        }

        $conditionalFieldname = $prefix !== ''
            ? $prefix . '-' . $baseField
            : $baseField;

        if ($conditionalFieldname === $field) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" cannot be used as its own comparison field.', $field)
            );
        }

        if (!array_key_exists($conditionalFieldname, $fields)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Comparison field "%s" does not exist inside the form.',
                    $conditionalFieldname
                )
            );
        }

        $conditionalFieldValue = $fields[$conditionalFieldname];

        if ($this->miscellaneousHelper->isUploadField($conditionalFieldValue)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Field "%s" cannot be used as comparison field because it is a file upload field.',
                    $conditionalFieldname
                )
            );
        }

        $equalValues = $params[1];

        if (is_string($equalValues) && str_contains($equalValues, '|')) {
            $equalValues = explode('|', $equalValues);
        }

        $operator = strtolower((string) ($params[2] ?? 'or'));

        if (!in_array($operator, ['and', 'or'], true)) {
            $operator = 'or';
        }

        if (is_array($equalValues)) {
            if ($operator === 'and') {
                $conditionMet =
                    is_array($conditionalFieldValue)
                    && count(array_intersect($conditionalFieldValue, $equalValues)) === count($equalValues);
            } else {
                $conditionMet =
                    is_array($conditionalFieldValue)
                    && count(array_intersect($conditionalFieldValue, $equalValues)) > 0;
            }
        } else {
            $conditionMet = $conditionalFieldValue === $equalValues;
        }

        $hasValue = is_array($value)
            ? $value !== []
            : ($value !== null && $value !== '');

        return !$conditionMet || $hasValue;
    }

    /**
     * Validate that the current field has a value when a referenced field is empty.
     *
     * @param string $field  Current field name.
     * @param mixed  $value  Value to validate.
     * @param array  $params [0] comparison field name.
     * @param array  $fields Full validation dataset.
     *
     * @return bool True if the referenced field is not empty, or the current field has a value.
     *
     * @throws InvalidArgumentException If the parameter is missing, empty, or self-referential.
     */
    public function validateRequiredIfEmpty(
        string $field,
        mixed $value,
        array $params,
        array $fields
    ): bool {
        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                'Missing required params for "' . $field . '".'
            );
        }

        if (trim((string) $params[0]) === '') {
            throw new InvalidArgumentException('Comparison field name must not be empty.');
        }

        $this->miscellaneousHelper->assertAtLeastTwoFields($fields);
        $prefix = $this->formID;

        $baseField = $params[0];

        if ($prefix !== '' && str_starts_with($baseField, $prefix . '-')) {
            $baseField = substr($baseField, strlen($prefix) + 1);
        }

        $conditionalFieldname = $prefix !== ''
            ? $prefix . '-' . $baseField
            : $baseField;

        if ($conditionalFieldname === $field) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" cannot be used as its own comparison field.', $field)
            );
        }

        if (!array_key_exists($conditionalFieldname, $fields)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Comparison field "%s" does not exist inside the form.',
                    $conditionalFieldname
                )
            );
        }

        $conditionalFieldValue = $fields[$conditionalFieldname];

        if ($this->miscellaneousHelper->isUploadField($conditionalFieldValue)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Field "%s" cannot be used as comparison field because it is a file upload field.',
                    $conditionalFieldname
                )
            );
        }

        $isEmpty = static function (mixed $v): bool {
            if (is_array($v)) {
                return $v === [];
            }

            return $v === null || $v === '';
        };

        if (!$isEmpty($conditionalFieldValue)) {
            return true;
        }

        return !$isEmpty($value);
    }

    /**
     * Validate that the current field has no value when a referenced field is not empty.
     *
     * @param string $field  Current field name.
     * @param mixed  $value  Value to validate.
     * @param array  $params [0] comparison field name.
     * @param array  $fields Full validation dataset.
     *
     * @return bool True if the referenced field is empty, or the current field is empty.
     *
     * @throws InvalidArgumentException If the parameter is missing, empty, or self-referential.
     */
    public function validateRequiredIfNotEmpty(
        string $field,
        mixed $value,
        array $params,
        array $fields
    ): bool {
        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                'Missing required params for "' . $field . '".'
            );
        }

        if (trim((string) $params[0]) === '') {
            throw new InvalidArgumentException('Comparison field name must not be empty.');
        }

        $this->miscellaneousHelper->assertAtLeastTwoFields($fields);
        $prefix = $this->formID;

        $baseField = $params[0];

        if ($prefix !== '' && str_starts_with($baseField, $prefix . '-')) {
            $baseField = substr($baseField, strlen($prefix) + 1);
        }

        $conditionalFieldname = $prefix !== ''
            ? $prefix . '-' . $baseField
            : $baseField;

        if ($conditionalFieldname === $field) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" cannot be used as its own comparison field.', $field)
            );
        }

        if (!array_key_exists($conditionalFieldname, $fields)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Comparison field "%s" does not exist inside the form.',
                    $conditionalFieldname
                )
            );
        }

        $conditionalFieldValue = $fields[$conditionalFieldname];

        if ($this->miscellaneousHelper->isUploadField($conditionalFieldValue)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Field "%s" cannot be used as comparison field because it is a file upload field.',
                    $conditionalFieldname
                )
            );
        }

        $isEmpty = static function (mixed $v): bool {
            if (is_array($v)) {
                return $v === [];
            }

            return $v === null || $v === '';
        };

        if ($isEmpty($conditionalFieldValue)) {
            return true;
        }

        return $isEmpty($value);
    }




}