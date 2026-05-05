<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding option elements depending on ProcessWire FieldtypeOption Select input fields
 * Will be used on select, datalist, checkboxes and radio elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TraitPWOptions.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

trait TraitPWOptions
{

    /**
     * Method to grab values from a PW FieldtypeOption Select field and use it as options
     * @param string $fieldName
     * @param string $addOptionMethodName
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     */
    protected function setOptionsFromFieldType(string $fieldName, string $addOptionMethodName): void
    {
        $field = $this->wire('fields')->get(trim($fieldName));

        if ($field && $field->getFieldtype()->className() === 'FieldtypeOptions') {
            foreach ($field->type->getOptions($field) as $option) {
                $this->$addOptionMethodName($option->title, $option->title ?? $option->value);
            }
        }
    }

}
