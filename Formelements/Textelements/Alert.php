<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating alert boxes
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Alert.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Alert extends TextElements
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setTag('div');
        $this->setCSSClass('alertClass');
    }

}
