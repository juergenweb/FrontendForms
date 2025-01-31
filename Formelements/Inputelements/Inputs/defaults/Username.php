<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a username input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Username.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;

/**
 * This is the base class for creating input elements
 */
class Username extends InputText
{

    /**
     * @param string $id
     * @throws WireException
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Username'));
        $this->setSanitizer('pageName');
        $this->setRule('required');
        $this->setRule('usernameSyntax');
        if($this->wire('user')->isLoggedin())
            $this->setDefaultValue($this->wire('user')->name);
    }

    /**
     * Render the username input field
     * @return string
     */
    public function ___renderUsername(): string
    {
        return parent::renderInputText();
    }

}
