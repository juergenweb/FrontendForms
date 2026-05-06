<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a password confirmation input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: PasswordConfirmation.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

/**
 * Class with pre-defined values for creating an email input field
 */
class PasswordConfirmation extends InputPassword
{

    /**
     * @param string $id
     * @param string $passwordFieldName
     * @throws Exception
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id, string $passwordFieldName)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Password Confirmation'));
        $this->setRule('required');
        $this->setRule('equals', $passwordFieldName);
        $this->setRule('lengthMin', $this->minLength);
        $this->setRule('lengthMax', '128');
        $this->showPasswordRequirements(false);
    }

    /**
     * Render the password confirmation input field
     * @return string
     * @throws WireException
     * @throws WirePermissionException
     */
    public function ___renderPasswordConfirmation(): string
    {
        return parent::renderInputPassword();
    }

}
