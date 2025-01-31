<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a gender select element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Gender.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

/**
 * Class with pre-defined values for creating a gender select field
 */
class Gender extends Select
{

    /**
     * If you want to use options from a PW field, enter the name of the field inside the constructor
     * fe $gender = new Gender('gender')
     * @param string|null $fieldName
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException|Exception
     */
    public function __construct(string $id, ?string $fieldName = null)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Gender'));
        if ($fieldName) {
            $this->setOptionsFromField($fieldName);
        } else {
            $this->addEmptyOption($this->_('Please select'));
            $this->addOption($this->_('Mister'), $this->_('Mister'));
            $this->addOption($this->_('Miss'), $this->_('Miss'));
            $this->addOption($this->_('Diverse'), $this->_('Diverse'));
        }
        $this->setRule('required')->setCustomFieldName($this->_('Gender'));
    }

    /**
     * Render the gender select input field
     * @return string
     */
    public function renderGender(): string
    {
        return parent::renderSelect();
    }

}
