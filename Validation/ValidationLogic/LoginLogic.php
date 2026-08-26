<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;
use ProcessWire\Module;
use ProcessWire\User;
use ProcessWire\WireException;

/**
 * Handles all login-related validation logic.
 *
 * Validates password/username and password/email combinations,
 * and verifies two-factor authentication codes.
 *
 * Validation methods follow the Valitron callback signature:
 *
 *     function(string $field, mixed $value, array $params, array $fields): bool
 *
 * Security notes:
 * - Usernames are sanitized via pageName() before selector usage.
 * - Emails are sanitized via selectorValue() before selector usage.
 * - Failed attempts are logged to the session via LoginHelper.
 * - Timing attacks are mitigated with a constant artificial delay.
 */
class LoginLogic extends BaseLogic
{
    /**
     * Allowed TFA module class names.
     *
     * Only modules listed here may be used for TFA code validation.
     */
    private const ALLOWED_TFA_MODULES = [
        'ProcessWire\\TfaTotp',
        'ProcessWire\\TfaEmail',
    ];

    /**
     * Session and brute-force tracking helper.
     */
    private LoginHelper $loginHelper;

    /**
     * Helper service for resolving and validating prefixed field names.
     */
    private FieldNameResolverHelper $fieldNameResolverHelper;

    /**
     * Create a new LoginLogic instance.
     *
     * @param LoginHelper              $loginHelper              Helper dependency for session/login operations.
     * @param FieldNameResolverHelper  $fieldNameResolverHelper  Helper dependency for field name resolution.
     */
    public function __construct(
        LoginHelper $loginHelper,
        FieldNameResolverHelper $fieldNameResolverHelper
    ) {
        parent::__construct();

        $this->loginHelper = $loginHelper;
        $this->fieldNameResolverHelper = $fieldNameResolverHelper;
    }

    // -------------------------------------------------------------------------
    // Public Valitron callbacks
    // -------------------------------------------------------------------------

    /**
     * Validate a password against a username field.
     *
     * Valitron callback — $params[0] must contain the username field name.
     *
     * @param string $_field Current field name from Valitron (unused).
     * @param string $value  Password value submitted by the user.
     * @param array  $params Rule parameters; $params[0] = username field name.
     * @param array  $fields Full validation dataset, used to resolve the prefixed field name.
     *
     * @return bool True when password matches username credentials.
     *
     * @throws InvalidArgumentException If the field name parameter is missing.
     * @throws WireException
     */
    public function isValidPasswordUsernameMatch(
        string $_field,
        string $value,
        array $params,
        array $fields
    ): bool {
        return $this->validatePasswordMatch($value, $params, $fields, 'username');
    }

    /**
     * Validate a password against an email field.
     *
     * Valitron callback — $params[0] must contain the email field name.
     *
     * @param string $_field Current field name from Valitron (unused).
     * @param string $value  Password value submitted by the user.
     * @param array  $params Rule parameters; $params[0] = email field name.
     * @param array  $fields Full validation dataset, used to resolve the prefixed field name.
     *
     * @return bool True when password matches email credentials.
     *
     * @throws InvalidArgumentException If the field name parameter is missing.
     * @throws WireException
     */
    public function isValidPasswordEmailMatch(
        string $_field,
        string $value,
        array $params,
        array $fields
    ): bool {
        return $this->validatePasswordMatch($value, $params, $fields, 'email');
    }

    /**
     * Validate a two-factor authentication (TFA) code.
     *
     * Valitron callback — $params[0] must be a User, $params[1] a TFA Module.
     *
     * @param string $_field  Current field name from Valitron (unused).
     * @param string $value   TFA code submitted by the user.
     * @param array  $params  Rule parameters; $params[0] = User, $params[1] = Module.
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True when TFA code is valid.
     *
     * @throws WireException
     */
    public function isValidTfaCode(
        string $_field,
        string $value,
        array $params,
        array $_fields
    ): bool {
        return $this->validateTfaCode($value, $params);
    }

    // -------------------------------------------------------------------------
    // Internal validation
    // -------------------------------------------------------------------------

    /**
     * Route a password validation request to the appropriate field type.
     *
     * Extracts the target field name from $params[0] and delegates
     * to validatePasswordMatchHelper().
     *
     * @param string $value     Password value to validate.
     * @param array  $params    Rule parameters; $params[0] = target field name.
     * @param array  $fields    Full validation dataset, used to resolve the prefixed field name.
     * @param string $fieldType Login type: 'username' or 'email'.
     *
     * @return bool True when validation succeeds.
     *
     * @throws InvalidArgumentException If $params[0] is missing or empty.
     * @throws WireException
     */
    private function validatePasswordMatch(
        string $value,
        array $params,
        array $fields,
        string $fieldType
    ): bool {
        if (!isset($params[0]) || trim((string) $params[0]) === '') {
            throw new InvalidArgumentException(
                'Missing first parameter (fieldname).'
            );
        }

        return $this->validatePasswordMatchHelper(
            $params[0],
            $value,
            $fields,
            $fieldType
        );
    }

    /**
     * Look up the login field value from the request and validate credentials.
     *
     * Logs failed attempts and applies a constant delay to mitigate
     * timing-based user enumeration.
     *
     * @param string $field     Unprefixed form field name for login value.
     * @param string $password  Password value to validate.
     * @param array  $fields    Full validation dataset, used to resolve the prefixed field name.
     * @param string $type      Login type: 'username' or 'email'.
     *
     * @return bool True when credentials are valid.
     *
     * @throws InvalidArgumentException If the field name cannot be resolved.
     * @throws WireException
     */
    private function validatePasswordMatchHelper(
        string $field,
        string $password,
        array $fields,
        string $type
    ): bool {
        $fieldName = $this->fieldNameResolverHelper->resolve($fields, $field);
        $login = (string) ($this->input->$fieldName ?? '');

        $valid = $this->loginHelper->validateLogin($login, $password, $type);

        if (!$valid) {
            $this->loginHelper->createSessionForLoginAttempts($fieldName, $type);
        }

        // Always delay to prevent timing-based user enumeration.
        usleep(random_int(100000, 300000));

        return $valid;
    }

    /**
     * Verify a TFA code against a whitelisted ProcessWire TFA module.
     *
     * @param string $value   TFA code submitted by the user.
     * @param array  $params  $params[0] = User, $params[1] = TFA Module.
     *
     * @return bool True when the TFA code is valid.
     */
    private function validateTfaCode(
        string $value,
        array $params
    ): bool {
        if (!isset($params[0], $params[1])) {
            return false;
        }

        $user = $params[0];
        $module = $params[1];

        if (!$user instanceof User || !$module instanceof Module) {
            return false;
        }

        $className = $module->className(true);

        if (!in_array($className, self::ALLOWED_TFA_MODULES, true)) {
            return false;
        }

        $tfa = $this->modules->get($className);

        if (!$tfa instanceof Module || !method_exists($tfa, 'isValidUserCode')) {
            return false;
        }

        try {
            return $tfa->isValidUserCode(
                $user,
                $value,
                $module->getUserSettings($user)
            );
        } catch (\Throwable) {
            return false;
        }
    }
}