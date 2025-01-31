<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a select multiple element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: SelectMultiple.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class SelectMultiple extends Select
{

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('multiple');
        $this->removeSanitizers('text');
        $this->setSanitizer('arrayVal');

    }

    /**
     * Add brackets to the name attribute if not present
     * @return void
     */
    private function convertNameAttribute(): void
    {
        $nameAttr = $this->getAttribute('name');
        if (substr($nameAttr, -1) !== '[]') {
            $this->setAttribute('name', $nameAttr . '[]');
        }
    }

    /**
     * Render the select input
     * @return string
     */
    public function renderSelectMultiple(): string
    {
        // add brackets to the name for multiple values array
        $this->convertNameAttribute();
        return $this->renderSelect();
    }

}
