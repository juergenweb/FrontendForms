<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating notes under an input element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Notes.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Notes extends TextElements
{
    use TraitTags;

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setCSSClass('notesClass');
    }


}
