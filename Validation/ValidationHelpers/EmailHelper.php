<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\NullPage;
use ProcessWire\User;

/**
 * Helper class containing email-related utility functions.
 */
class EmailHelper extends BaseHelper
{
    /**
     * Trim, validate and sanitize an email address for safe use in a
     * ProcessWire selector query. Invalid or empty input is normalized
     * to an empty string.
     *
     * @param string $email Raw email input.
     *
     * @return string Sanitized email safe for selector queries, or '' if invalid/empty.
     */
    public function sanitizeEmail(string $email): string
    {
        $email = trim($email);

        if ($email === '') {
            return '';
        }

        return $this->sanitizer->selectorValue(
            $this->sanitizer->email($email)
        );
    }

    /**
     * Retrieve a user by email address. Returns a NullPage if the input
     * is empty/invalid after sanitization or if no matching user exists.
     *
     * @param string $email Raw email address input.
     *
     * @return User|NullPage The matching user, or NullPage if none found.
     */
    public function getUserByEmail(string $email): User|NullPage
    {
        $email = $this->sanitizeEmail($email);

        return $email === ''
            ? new NullPage()
            : $this->users->get("email=$email");
    }

    /**
     * Check whether a user with the given email address already exists.
     * Relies on a case-insensitive database collation for the underlying
     * selector query, so this check is effectively case-insensitive too.
     *
     * @param string $email Raw email address input.
     *
     * @return bool True if a user with this email exists, otherwise false.
     */
    public function emailExists(string $email): bool
    {
        return $this->getUserByEmail($email)->id !== 0;
    }
}