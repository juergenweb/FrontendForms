<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input number element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputNumber.php
 * Created: 03.07.2022
 */

use Exception;

class InputNumber extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'number');
        // set default validators
        $this->setRule('numeric');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputNumber(): string
    {
        return $this->renderInput();
    }

}
