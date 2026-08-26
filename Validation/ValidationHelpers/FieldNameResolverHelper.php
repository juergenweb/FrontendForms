<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;

/**
 * Responsible for resolving and validating field names
 * including optional prefix handling.
 */
class FieldNameResolverHelper extends BaseHelper
{
    /**
     * Resolve a field name to its full prefixed version
     * and ensure it exists in the given field set.
     *
     * @param array  $fields    Available fields (key = field name)
     * @param string $fieldName Raw input field name
     *
     * @return string The fully resolved, validated field name.
     *
     * @throws InvalidArgumentException If the name is empty or the field does not exist.
     */
    public function resolve(array $fields, string $fieldName): string
    {

        $fieldName = $this->normalize($fieldName);

        if ($fieldName === '') {
            throw new InvalidArgumentException('Field name cannot be empty.');
        }

        // The common prefix must be derived from the field NAMES (the array
        // keys), not from the submitted field VALUES - using $fields here
        // directly would compute a prefix over user input instead of over
        // the actual field identifiers.
        $prefix = $this->formID;
        $normalizedFieldName = $this->applyPrefix($fieldName, $prefix);

        if (!$this->fieldExists($fields, $normalizedFieldName)) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" does not exist inside the form.', $normalizedFieldName)
            );
        }

        return $normalizedFieldName;
    }

    /**
     * Normalize raw field input.
     *
     * @param string $fieldName
     *
     * @return string
     */
    protected function normalize(string $fieldName): string
    {
        return trim($fieldName);
    }

    /**
     * Apply prefix if needed.
     *
     * @param string $fieldName
     * @param string $prefix
     *
     * @return string
     */
    protected function applyPrefix(string $fieldName, string $prefix): string
    {
        if ($prefix === '') {
            return $fieldName;
        }

        // Remove prefix if already present (normalize first)
        if (str_starts_with($fieldName, $prefix . '-')) {
            $fieldName = substr($fieldName, strlen($prefix . '-'));
        }

        return $prefix . '-' . $fieldName;
    }

    /**
     * Check if field exists in dataset.
     *
     * @param array  $fields
     * @param string $fieldName
     *
     * @return bool
     */
    protected function fieldExists(array $fields, string $fieldName): bool
    {
        return array_key_exists($fieldName, $fields);
    }

    /**
     * Retrieve the raw value of a field from the dataset.
     *
     * @param array  $fields    Available fields (key = field name => value).
     * @param string $fieldName Fully resolved field name to look up.
     *
     * @return mixed The value associated with the field.
     *
     * @throws InvalidArgumentException If the field name does not exist.
     */
    public function getFieldValue(array $fields, string $fieldName): mixed
    {
        if (!array_key_exists($fieldName, $fields)) {
            throw new InvalidArgumentException(
                sprintf('Unknown reference field: %s', $fieldName)
            );
        }

        return $fields[$fieldName];
    }
}