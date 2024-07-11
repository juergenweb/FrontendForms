<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an error message under an input element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Errormessage.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Errormessage extends TextElements
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->setCSSClass('error_messageClass');
        if ($this->frontendforms['input_framework'] === 'bootstrap5.json')
            $this->setTag('div');
        if ($this->frontendforms['input_framework'] === 'pico2.json')
            $this->setTag('small');
    }

}
