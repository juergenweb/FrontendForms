<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input color element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputColor.php
 * Created: 03.07.2022
 */

use Exception;

class InputColor extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'color');
        $this->setCSSClass('input_colorClass');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputColor(): string
    {
        return $this->renderInput();
    }

}
