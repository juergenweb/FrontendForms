<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Helper class for password-related operations.
 */
class PasswordHelper extends BaseHelper
{
    /**
     * Create a new PasswordHelper instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Check whether the given value matches the current logged-in user's password.
     *
     * @param string $value Password value to check.
     *
     * @return bool True if the user is logged in and the value matches their password.
     */
    public function matchesCurrentPassword(string $value): bool
    {
        if (!$this->user || !$this->user->id) {
            return false;
        }

        return $this->user->isLoggedin()
            && $this->user->pass->matches($value);
    }

    /**
     * Retrieve the password field requirements configured in ProcessWire.
     *
     * @return array The requirements array from the 'pass' field, or an empty array if unavailable.
     */
    public function getPasswordRequirements(): array
    {
        $passwordField = $this->fields->get('pass');

        if (!$passwordField) {
            return [];
        }

        return (array) $passwordField->requirements;
    }
}