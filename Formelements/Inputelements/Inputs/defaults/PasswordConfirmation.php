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
     * @param string $id -> the id of the password confirmation field
     * @param string $passwordfieldName -> the name of the password field to check against
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id, string $passwordfieldName)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Password Confirmation')); // set default label
        $this->setRule('required'); // a confirmation field is required by default
        $this->setRule('equals', $passwordfieldName);
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
