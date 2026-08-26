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
 * Optimized via Claude AI 05.05.26
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
     * Add brackets to the name attribute if not already present, so PHP
     * collects all selected values into an array on submission (e.g.
     * "myfield" becomes "myfield[]"). A missing "name" attribute (which
     * should not normally happen, since Inputfields::__construct() always
     * sets one) is treated as an empty string rather than causing a
     * TypeError, since str_ends_with() requires a string argument under
     * strict_types.
     * @return void
     */
    private function convertNameAttribute(): void
    {
        $name = (string) $this->getAttribute('name');
        if (!str_ends_with($name, '[]')) {
            $this->setAttribute('name', $name . '[]');
        }
    }

    /**
     * Render the select-multiple input: ensures the "name" attribute ends
     * with "[]" before delegating to Select's own rendering.
     * @return string
     */
    public function ___renderSelectMultiple(): string
    {
        // add brackets to the name for multiple values array
        $this->convertNameAttribute();
        return $this->renderSelect();
    }

}
