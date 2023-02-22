<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input radio element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRadio.php
 * Created: 03.07.2022
 */

use Exception;

class InputRadio extends InputRadioCheckbox
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'radio');
        $this->removeCSSClass('inputClass');
        $this->setCSSClass('radioClass');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputRadio(): string
    {
        if (in_array($this->getAttribute('value'), $this->getDefaultValue())) {
            $this->setAttribute('checked');
        }
        if (($this->hasAttribute('value')) && ($this->getPostValue() === $this->getAttribute('value'))) {
            $this->setAttribute('checked');
        }
        return $this->renderInput();
    }

}
