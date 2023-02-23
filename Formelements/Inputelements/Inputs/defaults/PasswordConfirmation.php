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
     * @param string $passwordfieldName
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $passwordfieldName, string $id = 'passconfirm')
    {
        parent::__construct($id);
        $this->setLabel($this->_('Password Confirmation')); // set default label
        $this->setRule('requiredWith', $passwordfieldName);
        $this->setRule('equals', $passwordfieldName);
    }

    /**
     * Render the password confirmation input field
     * @return string
     * @throws WireException
     * @throws WirePermissionException
     */
    public function ___renderPasswordConfirmation(): string
    {
        return parent::___renderInputPassword();
    }

}
