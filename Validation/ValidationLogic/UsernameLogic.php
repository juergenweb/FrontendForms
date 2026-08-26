<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Contains all username validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 *
 * Available validations:
 * - unique username
 * - username syntax
 */
class UsernameLogic extends BaseLogic
{
    /**
     * Username validation pattern.
     */
    private const USERNAME_PATTERN = '/^[a-z0-9._-]{3,30}$/';

    private UsernameHelper $usernameHelper;

    /**
     * Create a new UsernameLogic instance.
     *
     * @param UsernameHelper $usernameHelper Helper dependency for username-related operations.
     */
    public function __construct(UsernameHelper $usernameHelper)
    {
        parent::__construct();

        $this->usernameHelper = $usernameHelper;
    }

    /**
     * Validate whether a username is unique across all ProcessWire users.
     *
     * Returns true when:
     * - the value is empty (required-field validation is handled separately)
     * - the username does not exist yet
     * - the currently logged-in user already owns it
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Username value to validate.
     * @param array  $_params Validation parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the username is unique or belongs to the current user.
     */
    public function isUsernameUnique(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        $username = $this->usernameHelper->normalizeUsername($value);

        if ($username === '') {
            return true;
        }

        // Allow the current user to keep their existing username.
        if (
            $this->user->isLoggedin()
            && $this->user->name === $username
        ) {
            return true;
        }

        // sanitizeUsername() is called internally by usernameExists().
        return !$this->usernameHelper->usernameExists($username);
    }

    /**
     * Validate that a username conforms to the allowed syntax.
     *
     * Rules:
     * - 3–30 characters
     * - lowercase a-z, digits 0–9
     * - underscore, hyphen, and dot allowed
     *
     * Empty values are treated as valid.
     *
     * @param string $_field  Current field name (unused).
     * @param mixed  $value   Username value to validate.
     * @param array  $_params Validation parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the username is empty or matches the allowed syntax.
     */
    public function isValidUsernameSyntax(
        string $_field,
        mixed $value,
        array $_params,
        array $_fields
    ): bool {
        $username = $this->usernameHelper->normalizeUsername($value);

        if ($username === '') {
            return true;
        }

        return preg_match(self::USERNAME_PATTERN, $username) === 1;
    }
}