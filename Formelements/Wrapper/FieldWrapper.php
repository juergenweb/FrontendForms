<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a wrapper over a complete form input including label, error message, notes and description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FieldWrapper.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class FieldWrapper extends Wrapper
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCSSClass('field_wrapperClass');
    }

    /**
     * Grab the CSS class for errors on form inputs
     * @return string
     */
    protected function getErrorClass(): string
    {
        return $this->getCSSClass('field_wrapper_errorClass');
    }

    protected function getSuccessClass(): string
    {
        return $this->getCSSClass('field_wrapper_successClass');
    }

}
