<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating an email input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Email.php
 * Created: 03.07.2022
 */

use Exception;

class Email extends InputEmail
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Email'));
        $this->setRule('required');
    }


    /**
     * Render the email field
     * @return string
     */
    public function ___renderEmail(): string
    {
        return parent::renderInputEmail();
    }

}
