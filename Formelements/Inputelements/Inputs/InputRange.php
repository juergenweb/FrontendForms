<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input range element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRange.php
 * Created: 03.07.2022
 */

use Exception;

class InputRange extends InputNumber
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'range');
        $this->setCSSClass('input_rangeClass'); // add special range input class
        $this->removeAttributeValue('class', $this->getCSSClass('inputClass')); // remove default input class
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputRange(): string
    {
        return $this->renderInput();
    }

}
