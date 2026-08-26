<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a fieldset close tag
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FieldsetClose.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

class FieldsetClose extends Element
{
    /**
     * Set up the fieldset closing tag
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTag('fieldset');
    }

    /**
     * Allow the element to be cast directly to a string, producing the
     * same output as render().
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Render the fieldset close tag
     * @return string
     */
    public function ___render(): string
    {
        return '</' . $this->getTag() . '>';
    }

}