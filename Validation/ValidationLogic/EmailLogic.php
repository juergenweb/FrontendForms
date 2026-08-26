<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\User;

/**
 * Contains all email validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class EmailLogic extends BaseLogic
{
    /**
     * Helper service for email-related operations.
     */
    private EmailHelper $emailHelper;

    /**
     * Create a new EmailLogic instance.
     *
     * @param EmailHelper $emailHelper Helper dependency for email-related operations.
     */
    public function __construct(EmailHelper $emailHelper)
    {
        parent::__construct();

        $this->emailHelper = $emailHelper;
    }

    /**
     * Return the currently logged-in (or guest) ProcessWire user.
     *
     * @return User The current ProcessWire user.
     */
    protected function currentUser(): User
    {
        return $this->wire()->user;
    }

    /**
     * Validate whether an email address is unique.
     *
     * Returns true when:
     * - the email is empty (required-field validation is handled separately)
     * - the email does not already exist
     * - or the currently logged-in user already owns it
     *
     * @param string $field  Current field name (unused).
     * @param mixed  $value  Value to validate.
     * @param array  $params Additional validator parameters (unused).
     * @param array  $fields Full validation dataset (unused).
     *
     * @return bool True if the email is unique or belongs to the current user.
     */
    public function isEmailUnique(string $field, mixed $value, array $params, array $fields): bool
    {

        if (!is_scalar($value) && $value !== null) {
            return false;
        }

        $email = BaseHelper::normalizeScalar($value);

        if ($email === null) {
            return true;
        }

        $currentUser = $this->currentUser();

        if (
            $currentUser->isLoggedin()
            && strcasecmp($currentUser->email, $email) === 0
        ) {
            return true;
        }

        return !$this->emailHelper->emailExists($email);
    }
}