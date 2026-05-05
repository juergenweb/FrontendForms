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
 * Optimized via Claude AI 05.05.26
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
        if (!$this->getServerMethod() || isset($_POST[$this->getAttribute('name')])) {
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
        $value = $this->getAttribute('value');

        $isChecked = in_array($value, (array) $this->getDefaultValue(), strict: true)
            || in_array($value, (array) $this->getPostValue(), strict: true);

        if ($isChecked) {
            $this->setAttribute('checked');
        }

        return $this->renderInput();
    }

}
