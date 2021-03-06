<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a password input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Password.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Password extends InputPassword
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id = 'pass')
    {
        parent::__construct($id);
        $this->setLabel($this->_('Password')); // set default label
        $this->setRule('required')->setCustomFieldName($this->_('Password'));
    }

    /**
     * @return string
     * @throws WireException
     * @throws WirePermissionException
     */
    public function renderPassword(): string
    {
        return parent::___renderInputPassword();
    }

}
