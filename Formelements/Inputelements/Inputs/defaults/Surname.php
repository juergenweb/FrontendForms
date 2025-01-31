<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a surname input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Surname.php
 * Created: 03.07.2022
 */

use Exception;

class Surname extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Surname'));
        $this->setRule('required');
    }

    /**
     * Render the surname input field
     * @return string
     */
    public function ___renderSurname(): string
    {
        return parent::renderInputText();
    }

}
