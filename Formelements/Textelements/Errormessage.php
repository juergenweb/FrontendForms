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
 * Optimized via Claude AI 05.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Errormessage extends TextElements
{
    use TraitTags;

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setCSSClass('error_messageClass');
    }

}
