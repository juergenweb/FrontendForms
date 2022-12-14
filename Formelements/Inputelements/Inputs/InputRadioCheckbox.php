<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating checkbox and radio button elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRadioCheckbox.php
 * Created: 03.07.2022
 */

use Exception;

class InputRadioCheckbox extends Input
{

    use TraitCheckboxesAndRadios;

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->removeAttribute('class');
    }

}
