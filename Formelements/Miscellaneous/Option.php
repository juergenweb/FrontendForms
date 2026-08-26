<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an option element for select inputs and data lists
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Option.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Option extends Tag
{
    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTag('option');
    }

    /**
     * Render an option tag
     * @return string
     */
    public function ___render(): string
    {
        return $this->renderNonSelfclosingTag($this->getTag());
    }

}