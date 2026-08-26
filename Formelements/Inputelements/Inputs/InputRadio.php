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
 * Optimized via Claude AI 05.05.26
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
        $value = $this->getAttribute('value');
        $postValue = $this->retainSubmittedValue ? $this->getPostValue() : null;

        // Only consider the default value before the form has ever been
        // submitted - once submitted, only the actual post value should
        // decide whether this radio is checked (see the same reasoning in
        // InputRadioMultiple/InputCheckboxMultiple).
        //
        // When retainSubmittedValue is false, neither the default nor the
        // submitted value should ever pre-check this radio.
        $isDefaultChecked = $this->retainSubmittedValue
            && !$this->isSubmitted()
            && in_array($value, (array) $this->getDefaultValue(), strict: true);

        if ($isDefaultChecked || ($value && $postValue !== null && $postValue === $value)) {
            $this->setAttribute('checked');
        }

        return $this->renderInput();
    }

}
