<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating the legend element for fieldsets
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Legend.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Legend extends TextElements
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setTag('legend');
        $this->setCSSClass('legendClass');
    }

}
