<?php

declare(strict_types=1);

namespace FrontendForms;

use DateTimeImmutable;
use Exception;
use InvalidArgumentException;

/**
 * Provides custom date validation logic for FrontendForms.
 *
 * This service is used by Valitron validation callbacks and
 * contains validation methods for:
 *
 * - ISO week values
 * - ISO month values
 * - Date comparisons between fields
 * - Relative date range checks
 *
 * All validator methods follow the Valitron callback signature.
 *
 * Validation philosophy:
 * ----------------------
 * - Empty values are considered valid by default.
 *   Required-field validation must be handled separately.
 *
 * - Scalar normalization is performed before validation
 *   to prevent invalid array/object input.
 *
 * - Date parsing uses DateTimeImmutable to avoid
 *   mutable date side effects and silent parser issues.
 *
 * - Round-trip validation is used whenever possible:
 *   parsed dates are reformatted and compared against
 *   the original input to detect parser auto-corrections.
 *
 * Example:
 * --------
 * 2025-13 could otherwise silently become 2026-01.
 *
 * Dependencies:
 * -------------
 * - DateHelper
 * - BaseHelper
 * - FieldNameResolverHelper
 * - ProcessWire wire() API
 */
class DateLogic extends BaseLogic
{
    /**
     * Helper service for date operations.
     *
     * Responsible for:
     * - field value retrieval
     * - date comparison logic
     * - date range calculations
     */
    private DateHelper $dateHelper;

    /**
     * Helper service for resolving and validating prefixed field names.
     */
    private FieldNameResolverHelper $fieldNameResolverHelper;

    /**
     * Create a new DateLogic instance.
     *
     * @param DateHelper              $dateHelper              Helper dependency for date-related operations.
     * @param FieldNameResolverHelper $fieldNameResolverHelper Helper dependency for field name resolution.
     */
    public function __construct(DateHelper $dateHelper, FieldNameResolverHelper $fieldNameResolverHelper)
    {
        $this->dateHelper = $dateHelper;
        $this->fieldNameResolverHelper = $fieldNameResolverHelper;
    }

    /**
     * Validate whether a value is a valid ISO-8601 week string (YYYY-Www).
     * Empty values are treated as valid.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $value  Value to validate.
     * @param array  $params Validation rule parameters (unused).
     * @param array  $fields All submitted form fields (unused).
     *
     * @return bool True if the value is a valid ISO week.
     */
    public function isWeek(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        if (!preg_match('#^(\d{4})-W(\d{2})$#', $value, $matches)) {
            return false;
        }

        $year = (int) $matches[1];
        $week = (int) $matches[2];

        if ($week < 1 || $week > 53) {
            return false;
        }

        $date = (new DateTimeImmutable())->setISODate($year, $week);

        return $date->format('o-\WW') === $value;
    }

    /**
     * Validate whether a value is a valid ISO month string (YYYY-MM).
     * Empty values are treated as valid.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $value  Value to validate.
     * @param array  $params Validation rule parameters (unused).
     * @param array  $fields All submitted form fields (unused).
     *
     * @return bool True if the value is a valid ISO month.
     */
    public function isMonth(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        if (!preg_match('#^\d{4}-(0[1-9]|1[0-2])$#', $value)) {
            return false;
        }

        $year = (int) substr($value, 0, 4);

        if ($year < 1900 || $year > 2100) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m', $value);

        if (!$date) {
            return false;
        }

        return $date->format('Y-m') === $value;
    }

    /**
     * Validate whether a value is a valid time string (HH:MM or
     * HH:MM:SS, 24-hour format). Empty values are treated as valid.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $value  Value to validate.
     * @param array  $params Validation rule parameters (unused).
     * @param array  $fields All submitted form fields (unused).
     *
     * @return bool True if the value is empty or a valid time string.
     */
    public function isTime(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        return preg_match('#^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$#', $value) === 1;
    }

    /**
     * Validate that the current field's date is before a referenced field's date.
     * Empty values are treated as valid.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $value  Current field value.
     * @param array  $params Validation parameters (0 = referenced field name).
     * @param array  $fields All submitted form fields.
     *
     * @return bool True if the current date is before the referenced date.
     *
     * @throws InvalidArgumentException If the referenced field parameter is missing or invalid.
     */
    public function isDateBeforeField(string $field, mixed $value, array $params, array $fields): bool
    {

        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                sprintf('Missing parameter(s) in validation rule of %s: field.', $field)
            );
        }

        if (!is_string($params[0])) {
            throw new InvalidArgumentException('Field name must be a string.');
        }

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        $refFieldName = $this->fieldNameResolverHelper->resolve($fields, $params[0]);

        $refValue = $this->fieldNameResolverHelper->getFieldValue($fields, $refFieldName);

        if (!$this->dateHelper->validateDate($refValue)) {
            return false;
        }

        if (!$this->dateHelper->validateDate($value)) {
            return false;
        }

        return $this->dateHelper->compareDates($refValue, $value);
    }

    /**
     * Validate that the current field's date is after a referenced field's date.
     * Empty values are treated as valid.
     *
     * @param string $field  Name of the field being validated.
     * @param mixed  $value  Current field value.
     * @param array  $params Validation parameters (0 = referenced field name).
     * @param array  $fields All submitted form fields.
     *
     * @return bool True if the current date is after the referenced date.
     *
     * @throws InvalidArgumentException If the referenced field parameter is missing or invalid.
     */
    public function isDateAfterField(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!isset($params[0])) {
            throw new InvalidArgumentException(
                sprintf('Missing parameter(s) in validation rule of %s: field.', $field)
            );
        }

        if (!is_string($params[0])) {
            throw new InvalidArgumentException('Field name must be a string.');
        }

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        $refFieldName = $this->fieldNameResolverHelper->resolve($fields, $params[0]);
        $refValue = $this->fieldNameResolverHelper->getFieldValue($fields, $refFieldName);

        if (!$this->dateHelper->validateDate($refValue)) {
            return false;
        }

        if (!$this->dateHelper->validateDate($value)) {
            return false;
        }

        return $this->dateHelper->compareDates($refValue, $value, false);
    }

    /**
     * Validate that a date lies inside a configurable day range relative to a referenced field.
     *
     * @param string $field Name of the field being validated.
     * @param mixed $value Current field value.
     * @param array $params [0] referenced field name, [1] day range as integer -> can be positive (fe 8, future) or negative (fe -5, past).
     * @param array $fields All submitted form fields.
     *
     * @return bool True if the date lies inside the configured range.
     *
     * @throws InvalidArgumentException If parameters are missing or invalid.
     * @throws Exception
     */
    public function validateDateInsideOfDaysRange(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!isset($params[0], $params[1])) {
            throw new InvalidArgumentException(
                sprintf('Missing parameter(s) in validation rule of %s: field and/or date range.', $field)
            );
        }

        if (!is_string($params[0])) {
            throw new InvalidArgumentException('Field name must be a string.');
        }

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        $refFieldName = $this->fieldNameResolverHelper->resolve($fields, $params[0]);
        $refValue = $this->fieldNameResolverHelper->getFieldValue($fields, $refFieldName);

        if (!$this->dateHelper->validateDate($refValue)) {
            return false;
        }

        $days = BaseHelper::normalizeInt($params[1]);

        if ($days === null || $days === 0) {
            throw new InvalidArgumentException('Day range must be an integer number and cannot be 0. Enter positive number for a range in the future or a negative number for a range starting in the past.');
        }

        if (!$this->dateHelper->validateDate($value)) {
            return false;
        }

        return $this->dateHelper->checkDateRange($refValue, $value, $days);
    }

    /**
     * Validate that a date lies outside a configurable day range relative to a referenced field.
     *
     * @param string $field Name of the field being validated.
     * @param mixed $value Current field value.
     * @param array $params [0] referenced field name, [1] day range.
     * @param array $fields All submitted form fields.
     *
     * @return bool True if the date lies outside the configured range.
     *
     * @throws InvalidArgumentException If parameters are missing or invalid.
     * @throws Exception
     */
    public function validateDateOutsideOfDaysRange(string $field, mixed $value, array $params, array $fields): bool
    {
        if (!isset($params[0], $params[1])) {
            throw new InvalidArgumentException(
                sprintf('Missing parameter(s) in validation rule of %s: field and/or date range.', $field)
            );
        }

        if (!is_string($params[0])) {
            throw new InvalidArgumentException('Field name must be a string.');
        }

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $value = BaseHelper::normalizeScalar($value);

        if ($value === null) {
            return true;
        }

        $refFieldName = $this->fieldNameResolverHelper->resolve($fields, $params[0]);
        $refValue = $this->fieldNameResolverHelper->getFieldValue($fields, $refFieldName);

        if (!$this->dateHelper->validateDate($refValue)) {
            return false;
        }

        $days = BaseHelper::normalizeInt($params[1]);

        if ($days === null || $days === 0) {
            throw new InvalidArgumentException('Day range must be an integer number and cannot be 0.');
        }

        if (!$this->dateHelper->validateDate($value)) {
            return false;
        }

        return $this->dateHelper->checkDateRange($refValue, $value, $days, false);
    }
}