<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input email element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputEmail.php
 * Created: 03.07.2022
 */

use Exception;

class InputEmail extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'email');
        //set default validators
        $this->setRule('email');
        $this->setRule('emailDNS');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputEmail(): string
    {
        return parent::renderInputText();
    }

}
