<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding new methods to checkbox and radio elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: TraitCheckboxesAndRadios.php
 * Created: 03.07.2022 
 */

trait TraitCheckboxesAndRadios
{

    protected bool $appendLabel = false;

    /**
     * Append the label after the input tag depending on the settings of the form
     * @param bool $append
     * @return TraitCheckboxesAndRadios|InputCheckboxMultiple|InputRadioCheckbox|InputRadioMultiple
     */
    public function appendLabel(bool $append = true): self
    {
        $this->appendLabel = $append;
        return $this;
    }

    public function getAppendLabel(): bool
    {
        return $this->appendLabel;
    }
}


