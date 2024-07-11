<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating the required text hint for the form
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: RequiredTextHint.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class RequiredTextHint extends TextElements
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->setTag('p');
        $this->setCSSClass('requiredTextHintClass');
    }

}
