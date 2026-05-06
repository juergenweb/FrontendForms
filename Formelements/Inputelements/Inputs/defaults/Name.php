<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a name input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Name.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use Exception;

class Name extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Name'));
        $this->setRule('firstAndLastname');
    }

    /**
     * Render the name input field
     * @return string
     */
    public function ___renderName(): string
    {
        return parent::renderInputText();
    }

}


