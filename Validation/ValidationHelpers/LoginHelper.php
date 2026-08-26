<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;
use ProcessWire\User;
use ProcessWire\WireException;

/**
 * Helper class for login-related session tracking and credential
 * validation.
 */
class LoginHelper extends BaseHelper
{
    private UsernameHelper $usernameHelper;
    private EmailHelper $emailHelper;

    /**
     * Create a new LoginHelper instance.
     *
     * @param UsernameHelper $usernameHelper Helper dependency for username sanitization.
     * @param EmailHelper    $emailHelper    Helper dependency for email sanitization.
     */
    public function __construct(
        UsernameHelper $usernameHelper,
        EmailHelper $emailHelper
    ) {
        parent::__construct();

        $this->usernameHelper = $usernameHelper;
        $this->emailHelper = $emailHelper;
    }

    /**
     * Record a login attempt (successful or failed) in the session, for
     * later brute-force/rate-limit checks. Keeps only the last 20
     * attempts per field.
     *
     * @param string $fieldName Session key under which attempts are tracked.
     * @param string $loginType Login type: 'username' or 'email'.
     *
     * @return void
     */
    public function createSessionForLoginAttempts(
        string $fieldName,
        string $loginType
    ): void {
        $attempts = $this->session->get($fieldName) ?? [];

        $attempts[] = [
            'type' => $loginType,
            'time' => time(),
            'ip' => $this->session->getIP(),
        ];

        $attempts = array_slice($attempts, -20);

        $this->session->set($fieldName, $attempts);
    }

    /**
     * Validate a login/password combination against a real ProcessWire
     * user account.
     *
     * If no matching user is found, a dummy password verification still
     * runs (its result is deliberately discarded) so that the response
     * time is similar whether or not the account exists - mitigating
     * user-enumeration via timing analysis.
     *
     * @param string $login    Raw username or email input.
     * @param string $password Raw password input.
     * @param string $type     Login type: 'username' or 'email'.
     *
     * @return bool True if the credentials match a real user account.
     *
     * @throws InvalidArgumentException If $type is neither 'username' nor 'email'.
     */
    public function validateLogin(
        string $login,
        string $password,
        string $type
    ): bool {
        $login = match ($type) {
            'username' => $this->usernameHelper->sanitizeUsername($login),
            'email' => $this->emailHelper->sanitizeEmail($login),
            default => throw new InvalidArgumentException(
                "Unsupported login type: $type"
            ),
        };

        $password = trim($password);

        if ($login === '' || $password === '') {
            return false;
        }

        $user = match ($type) {
            'username' => $this->users->get("name=$login"),
            'email' => $this->users->get("email=$login"),
        };

        if ($user instanceof User && $user->id) {
            return $user->pass->matches($password);
        }

        $dummyHash = '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234';
        $dummy = password_verify($password, $dummyHash);

        return $dummy && false;
    }
}