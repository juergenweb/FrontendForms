<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a wrapper element for form inputs
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputWrapper.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputWrapper extends Wrapper
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCSSClass('input_wrapperClass');
    }

}
