<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\NullPage;
use ProcessWire\User;

/**
 * Helper class containing username-related utility functions.
 */
class UsernameHelper extends BaseHelper
{
    /**
     * Create a new UsernameHelper instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Normalize a raw input value to a lowercase, trimmed username string.
     *
     * @param mixed $value Raw input value.
     *
     * @return string Normalized username string.
     */
    public function normalizeUsername(mixed $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    /**
     * Sanitize a username for safe ProcessWire selector usage.
     *
     * Steps performed:
     * - Trim whitespace
     * - Normalize to ProcessWire-safe page name format
     * - Escape value for selector safety
     *
     * @param string $username Raw username input.
     *
     * @return string Sanitized username safe for selector queries.
     */
    public function sanitizeUsername(string $username): string
    {
        $username = trim($username);

        if ($username === '') {
            return '';
        }

        return $this->sanitizer->selectorValue(
            $this->sanitizer->pageName($username)
        );
    }

    /**
     * Retrieve a user by username.
     *
     * The username is sanitized using ProcessWire's pageName and selectorValue
     * sanitizers to ensure safe querying. Returns a NullPage if the username
     * is empty after sanitization or no matching user exists.
     *
     * @param string $username Raw username input.
     *
     * @return User|NullPage The matching user, or NullPage if none found.
     */
    private function getUserByUsername(string $username): User|NullPage
    {

        $username = $this->sanitizeUsername($username);

        return $username === ''
            ? new NullPage()
            : $this->users->get("name=$username");
    }

    /**
     * Check whether a user with the given username already exists.
     *
     * @param string $username Raw username input.
     *
     * @return bool True if a user with this username exists, otherwise false.
     */
    public function usernameExists(string $username): bool
    {
        return $this->getUserByUsername($username)->id > 0;
    }
}