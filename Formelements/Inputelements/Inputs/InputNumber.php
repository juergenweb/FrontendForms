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
        // create HTML5 min attribute depending on validator settings
        if(array_key_exists('min',$this->notes_array)){
            $this->setAttribute('min', (string)$this->notes_array['min']);
        }

        // create HTML5 max attribute depending on validator settings
        if(array_key_exists('max',$this->notes_array)){
            $this->setAttribute('max', (string)$this->notes_array['max']);
        }

        return $this->renderInput();
    }

}
