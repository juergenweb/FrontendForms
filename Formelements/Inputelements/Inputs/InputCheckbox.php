<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a checkbox element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputCheckbox.php
 * Created: 03.07.2022
 */

use Exception;

class InputCheckbox extends InputRadioCheckbox
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'checkbox');
        $this->removeCSSClass('inputClass');
        $this->setCSSClass('checkboxClass');
    }

    /**
     * Method to set a single checkbox checked by default (on page load)
     * Independent if input has a value or not
     * @return $this;
     */
    public function setChecked(): self
    {
        if ($this->getServerMethod()) {
            if (isset($_POST[$this->getAttribute('name')])) {
                $this->setAttribute('checked');
            }
        } else {
            $this->setAttribute('checked');
        }
        return $this;
    }


    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputCheckbox(): string
    {
        if (in_array($this->getAttribute('value'), $this->getDefaultValue())) {
            $this->setAttribute('checked');
        }
        // post value is array -> multiple checkbox value
        if (is_array($this->getPostValue())) {
            // set checked if post value is contains the checkbox value
            if (in_array($this->getAttribute('value'), $this->getPostValue())) {
                $this->setAttribute('checked');
            }
        } else {
            // set checked if post value is equal the checkbox value
            if ($this->getPostValue() === $this->getAttribute('value')) {
                $this->setAttribute('checked');
            }
        }
        return $this->renderInput();
    }

}
