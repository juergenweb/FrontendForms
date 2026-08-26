<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\InputfieldPassword;
use ProcessWire\WireException;

/**
 * Contains all password validation logic.
 *
 * This service is directly used by Valitron rules
 * and therefore follows the Valitron callback signature.
 */
class PasswordLogic extends BaseLogic
{
    /**
     * Path to the forbidden passwords file.
     */
    private const PASSWORD_LIST = 'FrontendForms/data/passwords.txt';

    /**
     * ProcessWire password validation module.
     */
    private InputfieldPassword $passwordModule;

    /**
     * Password helper instance.
     */
    private PasswordHelper $passwordHelper;

    /**
     * Text helper instance.
     */
    private TextHelper $textHelper;

    /**
     * Create a new PasswordLogic instance.
     *
     * Initializes helpers and configures password requirements.
     *
     * @throws WireException
     */
    public function __construct(PasswordHelper $passwordHelper, TextHelper $textHelper)
    {
        parent::__construct();

        $this->passwordHelper = $passwordHelper;
        $this->textHelper = $textHelper;

        $this->passwordModule = $this->modules->get('InputfieldPassword');

        if (!$this->passwordModule) {
            throw new WireException('InputfieldPassword module missing');
        }

        $this->passwordModule->set(
            'requirements',
            $this->passwordHelper->getPasswordRequirements()
        );
    }

    /**
     * Validate that the submitted password meets all configured password requirements.
     *
     * @param string $_field  Current field name (unused).
     * @param string $value   Password value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the password satisfies all configured requirements.
     */
    public function validateMeetsPasswordConditions(
        string $_field,
        string $value,
        array $_params,
        array $_fields
    ): bool {
        return $this->passwordModule->isValidPassword($value);
    }

    /**
     * Validate that the submitted password does not appear in the forbidden passwords list.
     *
     * @param string $_field  Current field name (unused).
     * @param string $value   Password value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the password is not on the blacklist, or the list is unavailable.
     */
    public function validateSafePassword(
        string $_field,
        string $value,
        array $_params,
        array $_fields
    ): bool {
        $passwordPath = $this->config->paths->siteModules . self::PASSWORD_LIST;

        if (
            !$this->files->exists($passwordPath)
            || !is_readable($passwordPath)
        ) {
            return true;
        }

        return empty($this->textHelper->findWords($value, $passwordPath));
    }

    /**
     * Validate that the submitted password differs from the current user's password.
     * Returns false when the user is not logged in.
     *
     * @param string $_field  Current field name (unused).
     * @param string $value   Password value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the password does not match the current user's password.
     */
    public function isDifferentPassword(
        string $_field,
        string $value,
        array $_params,
        array $_fields
    ): bool {
        if (!$this->user->isLoggedin()) {
            return false;
        }

        return !$this->passwordHelper->matchesCurrentPassword($value);
    }

    /**
     * Validate that the submitted password matches the current user's password.
     *
     * @param string $_field  Current field name (unused).
     * @param string $value   Password value to validate.
     * @param array  $_params Rule parameters (unused).
     * @param array  $_fields Full validation dataset (unused).
     *
     * @return bool True if the password matches the current user's password.
     */
    public function isCurrentPassword(
        string $_field,
        string $value,
        array $_params,
        array $_fields
    ): bool {
        return $this->passwordHelper->matchesCurrentPassword($value);
    }
}