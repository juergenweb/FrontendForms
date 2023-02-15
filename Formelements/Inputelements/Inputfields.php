<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating HTML input elements for collecting user inputs
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Inputfields.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Valitron\Validator;

abstract class Inputfields extends Element
{
    // Define all objects
    protected Label $label; // Object of class Label
    protected Notes $notes; // Object of class Notes
    protected Description $description; // Object of class Description
    protected Errormessage $errormessage; // Object of class error message
    protected FieldWrapper $fieldWrapper; // the wrapper object for the complete form input
    protected InputWrapper $inputWrapper; // the wrapper object for the input element
    protected Validator $validator; // the validator object (instantiated via setRule() method)
    protected ValitronAPI $api; // Default values
    protected array $sanitizer = []; // array to hold all sanitizer methods for this input field
    protected array $validatonRules = []; // array to hold all validation rules for one input field (can be none or multiple)
    protected bool $useInputWrapper = true; // show or hide the wrapper for the input element
    protected bool $useFieldWrapper = true; // show or hide the wrapper for the complete field element including label
    protected string $markupType = ''; // the selected markup type (fe UiKit, none, Bootstrap,... whatever)
    protected array $defaultValue = []; // array of all default values

    /**
     * Every input field must have a name, so the name is required as parameter in the constructor
     * The id will be created out of the name of the input field and the id of the form - can be overwritten
     * @param string $name
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct(string $name)
    {
        parent::__construct($name); //$this->setAttribute('id', $name);// set ID if input field will be rendered manually without the form class
        $this->setAttribute('name', $name);// set name attribute
        $this->fieldWrapper = new FieldWrapper();// instantiate the field wrapper object
        $this->inputWrapper = new InputWrapper();// instantiate the input wrapper object
        $this->label = new Label();// instantiate the label object
        $this->errormessage = new Errormessage();// instantiate the error message object
        $this->notes = new Notes();// instantiate the notes object
        $this->description = new Description();// instantiate the description object
        $this->validator = new Validator([]);// instantiate the Valitron object
        $this->markupType = $this->input_framework;//grab the markup type (fe uikit, none, bootstrap,...) and save it to a variable
        //  Set text sanitizer to all input elements (except multi-value inputs) for security reasons
        if (!in_array($this->className(), Tag::MULTIVALCLASSES)) {
            $this->setSanitizer('text');
        }// set sanitizer text to all input fields by default
    }

    /**
     * Add the input wrapper to the input field
     * @param bool $useInputWrapper
     * @return void
     */
    public function useInputWrapper(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;
    }

    /**
     * Add the field wrapper to the input field
     * @param bool $useFieldWrapper
     * @return void
     */
    public function useFieldWrapper(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;
    }

    /**
     * Get the input wrapper object for further manipulation on per field base
     * Use this method if you want to add custom attributes or remove attributes to and from the input field wrapper
     * @return InputWrapper
     */
    public function getInputWrapper(): InputWrapper
    {
        return $this->inputWrapper;
    }

    /**
     * Get the field wrapper object for further manipulation on per field base
     * Use this method if you want to add custom attributes or remove attributes to and from the field wrapper
     * @return FieldWrapper
     */
    public function getFieldWrapper(): FieldWrapper
    {
        return $this->fieldWrapper;
    }

    /**
     * Remove 1, more or all sanitizers if necessary from the input field
     * If you need to disable it - for whatever reason - you can use this method to remove sanitizers from an input field
     * @param array|string|null $sanitizer
     * null: means that all sanitizers will be removed from the input field
     * string: remove 1 sanitizer by its name
     * array: $sanitizers - fe ['text', 'number'] - can be one or multiple sanitizers
     * @return void
     */
    public function removeSanitizers(array|string $sanitizer = null): void
    {
        $sanitizers = $this->sanitizer;
        switch ($sanitizer) {
            case(is_string($sanitizer)):
                if (!empty($sanitizer)) {
                    unset($sanitizers[trim($sanitizer)]);
                    $this->sanitizer = $sanitizers;
                }
                break;
            case(is_array($sanitizer)):
                foreach ($sanitizer as $item) {
                    unset($sanitizers[trim($item)]);
                }
                $this->sanitizer = $sanitizers;
                break;
            default:
                $this->sanitizer = [];
        }
    }

    /**
     * Set a validator rule to validate the input value
     * Checks first if the validator method exists, otherwise does nothing
     * Check https://processwire.com/api/ref/sanitizer/ for all sanitizer methods
     * @param string $validator - the name of the validator
     * @return $this
     */
    public function setRule(string $validator): self
    {
        $args = func_get_args();
        $validator = $args[0];
        $variables = [];

        if (count($args) > 1) {
            array_shift($args); // remove the first element
            $variables = $args;
        }

        $this->api = new ValitronAPI();
        $this->api->setValidator($validator);
        $result = $this->api->setRule($validator, $variables);
        $this->validatonRules[$result['name']] = ['options' => $variables];
        return $this;
    }

    /**
     * Remove a validator which was set before
     * @param string $rule ;
     * @return $this;
     */
    public function removeRule(string $rule): self
    {
        $rules = $this->validatonRules;
        unset($rules[$rule]);
        $this->validatonRules = $rules;
        return $this;
    }


    /**
     * Method to overwrite default error message with a custom error message
     * Use the syntax {field} to output the Name of the field inside your custom message
     * @param string $msg - your custom error message text (fe {field} needs to be filled out)
     * @return $this
     */
    public function setCustomMessage(string $msg): self
    {
        $this->api->setCustomMessage($msg);
        $old = $this->validatonRules[$this->api->getValidator()];
        $new = ['customMsg' => $msg];
        // add the new value to the validationRules array
        $this->validatonRules[$this->api->getValidator()] = array_merge($old, $new);
        return $this;
    }

    /**
     * Method to change the field name inside the error message
     * If you need you can change fe 'Surname' to 'This field'
     * This only affects the field name inside the error message and not beside the input field
     * @param string $fieldname
     * @return $this
     */
    public function setCustomFieldname(string $fieldname): self
    {
        $this->api->setCustomFieldName($fieldname);
        $old = $this->validatonRules[$this->api->getValidator()];
        $new = ['customFieldName' => $fieldname];
        // add the new value to the validationRules array
        $this->validatonRules[$this->api->getValidator()] = array_merge($old, $new);
        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->___render();
    }

    /**
     * Render the input field including wrappers, notes, description, prepend markup, append markup and error message
     * @return string
     */
    public function ___render(): string
    {
        if ($this->hasRule('required')) {
            $this->label->setRequired();
            $this->setAttribute('required')->setCustomFieldName($this->getLabel()->getText());// set label as field name by default
        }
        $out = $content = '';
        $className = $this->className();
        $inputfield = 'render' . $className;
        $input = $this->$inputfield();
        switch ($className) {
            case ('InputHidden'):
                $this->removeAttribute('class');// we need no class attribute for styling on hidden fields
                $out .= $this->renderInputHidden() . PHP_EOL;// we do not need label, wrapper divs,... only the input element
                break;
            case ('InputCheckbox'):
            case ('InputRadio'):
                switch ($this->markupType) {
                    case ('bootstrap5.json'):
                        $this->label->setCSSClass('checklabelClass');
                        $label = $input . $this->label->render() . PHP_EOL;
                        break;
                    default: // uikit3.json, none
                        // render label and input different on single checkbox and single radio
                        $this->label->removeAttributeValue('class', $this->getCSSClass('checklabel'));
                        if ($this->appendLabel) {
                            $label = $input . $this->label->render() . PHP_EOL;
                        } else {
                            $this->label->setContent($input . $this->getLabel()->getText());
                            $label = $this->label->render() . PHP_EOL;
                        }
                }
                // error message and error class
                if ($this->getErrormessage()->getText()) {
                    $errormsg = $this->errormessage->___render() . PHP_EOL;
                    //add error message for validation
                    $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getErrorClass());
                    // add error class to the wrapper container
                } else {
                    $errormsg = '';
                }
                if (!$this->useInputWrapper) {
                    $content .= $label . $errormsg;
                } else {
                    $this->inputWrapper->setContent($label . $errormsg);
                    // set class to input-wrapper
                    if ($this->input_framework === 'bootstrap5.json') {
                        $this->inputWrapper->setAttribute('class', 'form-check');
                    }
                    $content .= $this->inputWrapper->___render() . PHP_EOL;
                }
                break;
            default:
                if ($this->getLabel()->getText()) {
                    $content .= $this->label->render() . PHP_EOL;
                }
                // Error message
                $errormsg = '';
                if ($this->getErrormessage()->getText()) {
                    $errormsg = $this->errormessage->___render() . PHP_EOL;
                    //add error message for validation
                    $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getErrorClass());
                    // add error class to the wrapper container
                }
                // add input-wrapper
                if ($this->useInputWrapper) {
                    $this->inputWrapper->setContent($input . $errormsg);
                    $content .= $this->inputWrapper->___render() . PHP_EOL;
                } else {
                    $content .= $input . $errormsg;
                }
        }
        // Add label and wrapper divs, error messages,... to all elements except hidden inputs
        if ($className != 'InputHidden') {
            // Description
            if ($this->getDescription()->getText()) {
                $content .= $this->description->___render() . PHP_EOL;
            }
            // Notes
            if ($this->getNotes()->getText()) {
                $content .= $this->notes->___render() . PHP_EOL;
            }
            if (!$this->useFieldWrapper) {
                $out .= $content;
            } else {
                $this->fieldWrapper->setContent($content);
                $out .= $this->fieldWrapper->___render() . PHP_EOL;
            }
        }
        return $out;
    }

    /**
     * Check if element has a specific validator
     * @param string $ruleName ->fe required
     * @return boolean
     */
    public function hasRule(string $ruleName): bool
    {
        if (array_key_exists(trim($ruleName), $this->getRules())) {
            return true;
        }
        return false;
    }

    /**
     * Get all validation rules for an input field
     * @return array
     */
    protected function getRules(): array
    {
        return $this->validatonRules;
    }

    /**
     * Method to clear all validation rules of an element
     * @return void
     */
    protected function removeAllRules(): void
    {
        $this->validatonRules = [];
    }

    /**
     * Get the label object (if present)
     * @return Label
     */
    protected function getLabel(): Label
    {
        return $this->label;
    }

    /**
     * Set the label text
     * @param string $label
     * @return Label
     */
    public function setLabel(string $label): Label
    {
        $this->label->setText($label);
        return $this->label;
    }

    /**
     * Get the Errormessage object
     * You can use this to manipulate attributes of the error message on per field base
     * Example $field->getErrorMessage()->setAttribute('class', 'myErrorClass');
     * @return Errormessage
     */
    public function getErrorMessage(): Errormessage
    {
        return $this->errormessage;
    }

    /**
     * Set the error message text
     * Will be set during processing of the form, not by the user
     * @param string $errorMessage
     * @return Errormessage
     */
    protected function setErrorMessage(string $errorMessage): Errormessage
    {
        $this->errormessage->setText($errorMessage);
        return $this->errormessage;
    }

    /**
     * Get the Description object
     * @return Description
     */
    protected function getDescription(): Description
    {
        return $this->description;
    }

    /**
     * Set the description text
     * @param string $description
     * @return Description
     */
    public function setDescription(string $description): Description
    {
        $this->description->setText($description);
        return $this->description;
    }

    /**
     * Get the Notes object
     * @return Notes
     */
    protected function getNotes(): Notes
    {
        return $this->notes;
    }

    /**
     * Set the notes text
     * @param string $notes
     * @return Notes
     */
    public function setNotes(string $notes): Notes
    {
        $this->notes->setText($notes);
        return $this->notes;
    }

    /**
     * Return the default value
     * @return string|array|null
     */
    protected function getDefaultValue(): string|array|null
    {
        return $this->defaultValue;
    }

    /**
     * Set (a) default value(s) for an input field on first page load
     * Enter values as a string: Each value has to be separated by a comma ('default value1', 'default value2')
     * Enter values as an array: ['default value1', 'default value2']
     * @param string|array|null $default
     * @return $this
     */
    public function setDefaultValue(string|array|null $default = null): self
    {
        if(!$this->isSubmitted()) { // set default value(s) only before form is submitted
            if (!is_null($default)) {
                if (is_string($default)) {
                    //create array out of string
                    $default = func_get_args();
                    //sanitize array values and set them as a string
                    array_walk($default, function (&$item) {
                        $item = trim($item);
                    });
                }
                //check if input type can have multiple values or not
                if (($this->className() === 'InputCheckboxMultiple') || ($this->className() === 'InputSelectMultiple')) {
                    $value = $default;
                } else {
                    // take only the first item of the array (single value only)
                    $value = $default[0];
                }
                if (($this->className() != 'InputCheckboxMultiple') || ($this->className() != 'InputSelectMultiple')) {
                    $this->setAttribute('value', $value);
                    // set only default value and value if a value attribute is present or it is a select input field
                    $this->defaultValue = $default;
                }
            }
        }
        return $this;
    }

    /**
     * Return all names of the sanitizer methods
     * @return array
     */
    protected function getSanitizer(): array
    {
        return $this->sanitizer;
    }

    /**
     * Set a sanitizer from ProcessWire sanitizer methods to sanitize the input value
     * Checks first if the entered sanitizer method exists, otherwise informs you that this method does not exist
     * Check https://processwire.com/api/ref/sanitizer/ for all sanitizer methods
     * @param string $sanitizer - the name of the sanitizer
     * @throws Exception
     */
    public function setSanitizer(string $sanitizer)
    {
        $sanitizer = trim(strtolower($sanitizer));
        //if sanitizer method exist add the name of the sanitizer to the sanitizer property
        if (method_exists($this->wire('sanitizer'), $sanitizer)) {
            $this->sanitizer = array_merge($this->sanitizer, [$sanitizer]);
        } else {
            throw new Exception('This sanitizer method does not exist in ProcessWire.');
        }
    }

    /**
     * Check if inputfield contains the given sanitizer
     * @param string $sanitizer
     * @return bool
     */
    public function hasSanitizer(string $sanitizer): bool
    {
        $sanitizer = trim(strtolower($sanitizer));
        return in_array($sanitizer, $this->sanitizer);
    }

    /**
     * Get the error class for input fields
     * @return string|null
     */
    protected function getinputErrorClass(): ?string
    {
        return $this->getCSSClass('input_errorClass');
    }

    /**
     * Get the post value of the input field if it is present
     * @return mixed
     */
    protected function getPostValue(): mixed
    {
        if ($this->hasPostValue()) {
            $name = str_replace('[]', '', $this->getAttribute('name'));
            // remove brackets from attribute name of multi-value input fields
            return $this->getServerMethod()[$name];
        }
        return [];// return empty array to prevent error on in_array function
    }

    /**
     * Check if post value of the input field is present
     * @return bool -> true: the post value is present, false: post value is not there
     */
    protected function hasPostValue(): bool
    {
        $name = str_replace('[]', '', $this->getAttribute('name'));
        // remove brackets from attribute name of multi-value input fields
        if (isset($this->getServerMethod()[$name])) {
            return true;
        }
        return false;
    }
}
