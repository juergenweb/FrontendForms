<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding option elements depending on ProcessWire FieldtypeOption Select input fields
 * Will be used on select, datalist, checkboxes and radio elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FieldsetOpen.php
 * Created: 03.07.2022
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
        $fieldName = trim($fieldName);
        $field = $this->wire('fields')->get($fieldName);
        if ($field) {
            // field must be a FieldtypeOptions field type
            if ($field->getFieldtype()->className() === 'FieldtypeOptions') {
                foreach ($field->type->getOptions($field) as $option) {
                    $this->$addOptionMethodName($option->title, $option->title ?? $option->value);
                }
            }
        }
    }

}
