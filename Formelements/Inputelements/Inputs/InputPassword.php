<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input password element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputPassword.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputPassword extends InputText
{
    protected $passwordField; // the password field object
    protected string $passwordFieldName = 'pass';
    protected bool $showPasswordRequirements = false;
    protected InputCheckbox $showPasswordToggle; // The password toggle checkbox object

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'password');
        $this->passwordField = $this->wire('fields')->get($this->passwordFieldName);
        // add meetsPasswordConditions Validator by default
        $this->setRule('meetsPasswordConditions');

    }

    /**
     * Show the password requirements at the password field
     * @return void
     */
    public function showPasswordRequirements(): void
    {
        $this->showPasswordRequirements = true;
    }

    /**
     * Add a password toggle checkbox to the input element
     * @return InputCheckbox
     */
    public function showPasswordToggle(): InputCheckbox
    {
        $toggle = new InputCheckbox('pwtoggle');
        $toggle->setLabel($this->_('Show password'))->setAttribute('class', 'pwtoggleLabel');
        $toggle->setAttribute('class', 'pwtoggle');
        $toggle->useInputWrapper(false);
        $toggle->useFieldWrapper(false);
        $toggle->removeAttribute('id');
        $this->showPasswordToggle = $toggle;
        return $toggle;
    }

    /**
     * Render the password input element
     * @return string
     * @throws WireException
     * @throws WirePermissionException
     */
    public function ___renderInputPassword(): string
    {
        if ($this->showPasswordRequirements) {
            if ($this->getDescription()) {
                $this->setDescription($this->renderPasswordRequirements() . '<br>' . $this->getDescription()->getText());
            } else {
                $this->setDescription($this->renderPasswordRequirements());
            }
        }

        $this->append($this->showPasswordToggle()->___render());

        return parent::___renderInputText();
    }

    /**
     * Render a text which informs about the requirements of a password as set in the backend
     * @return string|null
     * @throws WireException
     * @throws WirePermissionException
     */
    public function renderPasswordRequirements(): ?string
    {
        // check if minlength is stored inside the db otherwise take it from default data
        $passLength = $this->passwordField->minlength ?? $this->wire('modules')->get('InputfieldPassword')->minlength;
        if ($this->getPasswordConditions()) {
            return sprintf($this->_('The password must be at least %s characters and must contain characters of the following categories: %s.'),
                $passLength, $this->getPasswordConditions());
        } else {
            if ($passLength) {
                return sprintf($this->_('The password must be at least %s characters.'), $passLength);
            }
            return null;
        }
    }

    /**
     * Show a hint for requirements for the password as set in the backend
     * @return string|null - returns a comma separated list of all conditions or null if no conditions were set
     * @throws WireException
     * @throws WirePermissionException
     */
    protected function getPasswordConditions(): ?string
    {
        $passwordModule = $this->wire('modules')->get('InputfieldPassword');
        $requirements = array_merge($passwordModule->requirements, (array)$this->passwordField->requirements);
        if (in_array('none', $requirements)) {
            return null;
        }
        $conditions = [];
        foreach ($requirements as $name) {
            $conditions[] = $passwordModule->requirementsLabels[$name];
        }
        return implode(', ', $conditions);
    }
}
