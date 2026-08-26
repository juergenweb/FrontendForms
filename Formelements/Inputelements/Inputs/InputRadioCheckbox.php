<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating checkbox and radio button elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRadioCheckbox.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputRadioCheckbox extends Input
{
    use TraitCheckboxesAndRadios;

    protected bool $retainSubmittedValue = true;

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->removeAttribute('class');
    }

    /**
     * Control whether a previously submitted (or default) value stays
     * selected/checked when this element is re-rendered.
     *
     * Defaults to true, matching normal checkbox/radio behavior. Set to
     * false for cases where retaining the value would be undesirable -
     * most notably the image CAPTCHA's radio group (see
     * InputRadioMultiple::retainSubmittedValue(), which propagates this
     * same setting to each of its individual InputRadio options before
     * rendering them).
     * @param bool $retain
     * @return void
     */
    public function retainSubmittedValue(bool $retain): void
    {
        $this->retainSubmittedValue = $retain;
    }
}
