<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a description under an input element
 * Will be instantiated in the setDescription() method of the input fields class
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Description.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Description extends TextElements
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setCSSClass('descriptionClass');
    }

}
