<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;
use ProcessWire\NullPage;
use ProcessWire\User;
use ProcessWire\Wire;

/**
 * BaseLogic
 *
 * Provides reusable validation and user lookup functionality
 * for frontend form-related logic classes.
 *
 * This class centralizes common ProcessWire operations such as:
 *
 * - User retrieval by email or username
 * - Input sanitization for safe selector usage
 *
 * It is intended to be extended by domain-specific logic classes
 * such as LoginLogic, EmailLogic, or UsernameLogic.
 *
 * IMPORTANT:
 * All values used in ProcessWire selectors are sanitized to prevent
 * selector injection vulnerabilities.
 */
abstract class BaseLogic extends Wire
{
    protected string $formID = '';

    protected ?Form $form = null;

    /**
     * Assign the current form instance and cache its ID for later use.
     *
     * @param Form $form The form instance this logic operates on.
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
                'getForm() was called before setForm() was set on this logic instance.'
            );
        }

        return $this->form;
    }

    /**
     * Check if a param is present - otherwise throw an exception.
     *
     * Note: this only rejects empty string / null entries; it does not
     * reject nested arrays or other non-scalar values.
     *
     * @param array $param
     *
     * @return array
     *
     * @throws InvalidArgumentException When the param is missing or contains
     *                                    empty values.
     */
    protected function checkForParam(array $param): array
    {
        if ($param === []) {
            throw new InvalidArgumentException('Param is required');
        }

        foreach ($param as $value) {
            if ($value === '' || $value === null) {
                throw new InvalidArgumentException('Param contains empty values');
            }
        }

        return $param;
    }

    /**
     * Normalize validation parameters into an array.
     *
     * Supports:
     * - single string values
     * - single int values
     * - array values
     *
     * Note: float values are currently not supported and are silently
     * dropped (a bare float value resolves to [], a float inside an
     * array is skipped). Verify whether float support is required.
     *
     * Examples:
     *
     * 'jpg'
     * => ['jpg']
     *
     * ['jpg', 'png']
     * => ['jpg', 'png']
     *
     * [['jpg', 'png']]
     * => ['jpg', 'png']
     *
     * @param mixed $value Raw validation parameter.
     *
     * @return array<int, string> Normalized string array.
     */
    protected function normalizeStringArray(mixed $value): array
    {
        if (is_int($value)) {
            $value = (string) $value;
        }

        // Single string: 'jpg'
        if (is_string($value)) {
            $value = trim($value);

            return $value === '' ? [] : [$value];
        }

        // Already an array: ['jpg', 'png']
        if (is_array($value)) {
            // Nested array: [['jpg', 'png']]
            if (
                isset($value[0])
                && count($value) === 1
                && is_array($value[0])
            ) {
                $value = $value[0];
            }

            $normalized = [];

            foreach ($value as $item) {
                if (is_int($item)) {
                    $item = (string) $item;
                }

                if (!is_string($item)) {
                    continue;
                }

                $item = trim($item);

                if ($item === '') {
                    continue;
                }

                $normalized[] = $item;
            }

            return $normalized;
        }

        return [];
    }

    /**
     * Get and validate a required array parameter.
     *
     * Rejects missing, empty, and explicit zero ("0") values consistently,
     * regardless of whether the raw parameter was given as a scalar or
     * as an array.
     *
     * @param array  $params Validation parameter array.
     * @param string $label  Human-readable parameter name.
     * @param int    $index  Parameter index.
     *
     * @return array<int, string>
     *
     * @throws InvalidArgumentException Thrown when the parameter is missing,
     *                                    empty, or resolves to zero.
     */
    protected function resolveStringParam(array $params, string $label, int $index = 0): array
    {
        if (!array_key_exists($index, $params)) {
            throw new InvalidArgumentException(
                sprintf('Missing parameter: %s.', $label)
            );
        }

        $raw = $params[$index];

        if ($raw === null) {
            throw new InvalidArgumentException(
                sprintf('Parameter is empty: %s.', $label)
            );
        }

        // Normalize both scalar and array input through the same path,
        // so "0" is rejected consistently regardless of input shape.
        $normalized = is_array($raw)
            ? $this->normalizeStringArray($raw)
            : $this->normalizeStringArray(trim((string) $raw));

        if ($normalized === []) {
            throw new InvalidArgumentException(
                sprintf('Parameter is empty: %s.', $label)
            );
        }

        // Reject a single numeric zero explicitly (optional but recommended).
        if (count($normalized) === 1 && $normalized[0] === '0') {
            throw new InvalidArgumentException(
                sprintf('Parameter is not allowed to be zero: %s.', $label)
            );
        }

        return $normalized;
    }
}