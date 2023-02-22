<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input date element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputDate.php
 * Created: 03.07.2022
 */

use Exception;

class InputDate extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'date');
        $this->setRule('date');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputDate(): string
    {
        // create HTML5 max date attribute (date must before the value)  depending on validator settings
        if(array_key_exists('dateBefore',$this->notes_array)){
            $this->setAttribute('max', (string)$this->notes_array['dateBefore']);
        }

        // create HTML5 min date attribute (date must be after the value) depending on validator settings
        if(array_key_exists('dateAfter',$this->notes_array)){
            $this->setAttribute('min', (string)$this->notes_array['dateAfter']);
        }

        return $this->renderInput();
    }

}
