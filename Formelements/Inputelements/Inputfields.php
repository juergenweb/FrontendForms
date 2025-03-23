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
    use function ProcessWire\wireBytesStr;

    abstract class Inputfields extends Element
    {
        // Define all objects
        protected Label $label; // Object of class Label
        protected Notes $notes; // Object of class Notes
        protected Description $description; // Object of class Description
        protected Errormessage $errormessage; // Object of class error message
        protected Successmessage $successmessage; // Object of class success message
        protected Wrapper $customWrapper; // A custom wrapper object for certain use cases
        protected bool $useCustomWrapper = false;
        protected FieldWrapper $fieldWrapper; // the wrapper object for the complete form input
        protected InputWrapper $inputWrapper; // the wrapper object for the input element
        protected Validator $validator; // the validator object (instantiated via setRule() method)
        protected ValitronAPI $api; // Default values
        protected array $sanitizer = []; // array to hold all sanitizer methods for this input field
        protected array $validatonRules = []; // array to hold all validation rules for one input field (can be none or multiple)
        protected bool|null $useInputWrapper = null; // show or hide the wrapper for the input element
        protected bool|null $useFieldWrapper = null; // show or hide the wrapper for the complete field element including label
        protected string $markupType = ''; // the selected markup type (fe UiKit, none, Bootstrap,... whatever)
        protected array $defaultValue = []; // array of all default values
        protected array $notes_array = []; // property that holds multiple notes as an array - needed for some fields internally
        protected string $form_id_submitted = ''; // get the id of the form after form submission - needed for some validation rules
        protected string|int|bool $useAriaAttr = true; // whether to render area attributes or not

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
            $this->customWrapper = new Wrapper(); // instantiate the custom wrapper object
            $this->fieldWrapper = new FieldWrapper();// instantiate the field wrapper object
            $this->inputWrapper = new InputWrapper();// instantiate the input wrapper object
            $this->label = new Label();// instantiate the label object
            $this->errormessage = new Errormessage();// instantiate the error message object
            $this->successmessage = new Successmessage();// instantiate the success message object
            $this->notes = new Notes();// instantiate the notes object
            $this->description = new Description();// instantiate the description object
            $this->markupType = $this->frontendforms['input_framework'];//grab the markup type (fe uikit, none, bootstrap,...) and save it to a variable
            //  Set text sanitizer to all input elements (except multi-value inputs) for security reasons
            if (!in_array($this->className(), Tag::MULTIVALCLASSES)) {
                $this->setSanitizer('text');
            }// set sanitizer text to all input fields by default
            // get form id after submission
            $this->form_id_submitted = $this->getFormIDFromRequest($_REQUEST);

        }

        /**
         * Create a custom wrapper as the most outer container of a form field
         * This wrapper can be added on per input field base and can be used fe for JavaScript manipulations
         * @param bool $use
         * @return \FrontendForms\Wrapper
         */
        public function useCustomWrapper(bool $use = true): Wrapper
        {
            $this->useCustomWrapper = $use;
            return $this->customWrapper;
        }

        /**
         * Get the custom wrapper object for further manipulations if needed
         * @return \FrontendForms\Wrapper
         */
        public function getCustomWrapper(): Wrapper
        {
            return $this->customWrapper;
        }

        /**
         * Get the id of the form
         * Works only after Request (Get, Post)
         * Will be needed for some validation rules
         * @param array $arr
         * @return string
         */
        private function getFormIDFromRequest(array $arr): string
        {
            $result = '';
            foreach ($arr as $key => $val) {
                if (str_ends_with($key, '-form_id')) {
                    $result = $val;
                    break;
                }
            }
            return $result;
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
         * Internal function to check if input wrapper is set on per form base or not
         * @return bool|null
         */
        public function getUsageOfInputWrapper(): bool|null
        {
            return $this->useInputWrapper;
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
         * Internal function to check if field wrapper is set on per form base or not
         * @return bool|null
         */
        public function getUsageOfFieldWrapper(): bool|null
        {
            return $this->useFieldWrapper;
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
         * If you need to disable it - for whatever reason - you can use this method to remove sanitizers from an input
         * field
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
                        if (($key = array_search($sanitizer, $sanitizers)) !== false) {
                            unset($sanitizers[$key]);
                        }
                        $this->sanitizer = $sanitizers;
                    }
                    break;
                case(is_array($sanitizer)):
                    foreach ($sanitizer as $item) {
                        if (($key = array_search($item, $sanitizers)) !== false) {
                            unset($sanitizers[$key]);
                        }
                    }
                    $this->sanitizer = $sanitizers;
                    break;
                default:
                    $this->sanitizer = [];
            }
        }

        /**
         * Convert a filesize to bytes if
         * @param string|int $from
         * @param bool $ini
         * @return int|null
         */
        public static function convertToBytes(string|int $from, bool $ini = false): ?int
        {

            if ($ini) {
                // php.ini only allows 1 letter for the unit
                $units = ['B', 'K', 'M', 'G', 'T', 'P'];
            } else {
                $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
            }

            if (is_int($from)) $from = (string)$from;

            $pos = ($ini) ? -1 : -2;
            $number = substr($from, 0, $pos);
            $suffix = strtoupper(substr($from, $pos));

            //B or no suffix
            if (is_numeric(substr($suffix, 0, 1))) {
                return (int)preg_replace('/[^\d]/', '', $from);
            }

            $exponent = array_flip($units)[$suffix] ?? null;
            if ($exponent === null) {
                return null;
            }

            return (int)$number * (1024 ** $exponent);
        }

        /**
         * Set a validator rule to validate the input value
         * Checks first if the validator method exists, otherwise do nothing
         * Check https://processwire.com/api/ref/sanitizer/ for all sanitizer methods
         * @param string $validator - the name of the validator
         * @return $this
         */
        public function setRule($validator): self
        {

            $args = func_get_args(); // get all parameter inside the parenthesis
            $validator = $args[0];
            $variables = [];

            // check if paramters were set
            if (func_num_args() > 1) {
                array_shift($args); // remove the first element (validator name)
                $variables = $args;
            }

            // if only a integer has been added as allowed file size, convert it to kb, MB and so on on error messages
            if ($validator == 'allowedFileSize') {
                if (is_int($variables[0])) {
                    $variables[0] = wireBytesStr($variables[0]);
                }
            }


            $this->api = new ValitronAPI();
            $this->api->setValidator($validator);
            $result = $this->api->setRule($validator, $variables);
            $this->validatonRules[$result['name']] = ['options' => $variables];

            // add notes if a special validator has been added
            // this is special designed for file upload field to inform the user about the restrictions
            // fe only 40kb, only jpg,....
            // but can be used for other fields to if needed

            // inform about max filesize

            if ($validator == 'allowedFileSize') {

                $max_file_size = self::convertToBytes($variables[0]);
                $this->notes_array['allowedFileSize']['text'] = sprintf($this->_('Please do not upload files larger than %s'),
                    wireBytesStr($variables[0]));
                $this->notes_array['allowedFileSize']['value'] = $variables[0];
            }

            // inform about allowed extensions
            if ($validator == 'allowedFileExt') {
                if (isset($variables[0])) {
                    $this->notes_array['allowedFileExt']['text'] = sprintf($this->_('Allowed file types: %s'),
                        implode(', ', $variables[0]));
                    $this->notes_array['allowedFileExt']['value'] = implode(', ', $variables[0]);
                }
            }

            // inform about max filesize according to php.ini value
            if ($validator == 'phpIniFilesize') {

                $max_file_size = self::convertToBytes(ini_get("upload_max_filesize"), true);
                $this->notes_array['phpIniFilesize']['text'] = sprintf($this->_('Please do not upload files larger than %s'),
                    wireBytesStr($max_file_size));
                $this->notes_array['phpIniFilesize']['value'] = $max_file_size;
            }

            // add HTML5 validation attribute if present
            $method_name = 'addHTML5' . $validator;

            if (method_exists($this, $method_name)) {
                $this->$method_name($variables);
            }

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

            // remove HTML5 validation attribute if present
            $method_name = 'removeHTML5' . $rule;
            if (method_exists($this, $method_name)) {
                $this->$method_name();
            }

            // remove additional notes if present
            unset($this->notes_array[$rule]);
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
            return $this->render();
        }


        public function ___render(): string
        {

            // Add Aria attributes if set
            $this->addAriaAttributes();

            // set required to label to display asterisk if set
            if ($this->hasRule('required')) {
                $this->label->setRequired();
            }

            // Set notes to inputfield if present
            if ($this->notes->getContent() && $this->notes_array) {
                // add this value at the beginning of the note_array
                $this->notes_array = ['notes' => ['text' => $this->notes->getContent()]] + $this->notes_array;
            }

            // merge all notes texts
            if ($this->notes_array) {
                // grab all key with the name 'text'
                $texts = array_column($this->notes_array, 'text');
                $this->setNotes(implode('<br>', $texts));
            }

            $className = $this->className();
            $inputfield = 'render' . $className;
            $input = $this->$inputfield();
            $out = '';

            // add error class to the wrapper container
            if ($this->getErrormessage()->getText()) {
                $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getErrorClass());

            }

            // add success class to the wrapper container
            if ($this->getPostValue() && $this->getSuccessmessage()->getText() && !$this->getErrormessage()->getText()) {
                $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getSuccessClass());
            } else {
                // remove the success message
                $this->setSuccessMessage('');
            }

            // Add ARIA attributes to description and notes ... for all elements except hidden inputs
            if ($className != 'InputHidden') {

                // Description
                if ($this->getDescription()->getText()) {
                    $this->setAttribute('aria-describedby', $this->getID() . '-desc');
                }
                // Notes
                if ($this->getNotes()->getText()) {
                    $this->setAttribute('aria-describedby', $this->getID() . '-notes');
                }

            }

            // generate the render method name out of the markup type
            $methodName = 'render' . ucfirst(pathinfo($this->markupType, PATHINFO_FILENAME));
            if (method_exists($this, '___' . $methodName)) {
                $content = $this->$methodName($className, $input);
            } else {
                $content = $this->renderDefault($className, $input);
            }

            // Add fieldwrapper if set
            if (!$this->useFieldWrapper) {
                $out .= $content;
            } else {
                $this->fieldWrapper->setContent($content);
                $out .= $this->fieldWrapper->render() . PHP_EOL;
            }

            // check if custom wrapper was added and render it as the most outer container
            if ($this->useCustomWrapper) {
                $this->customWrapper->setContent($out);
                $out = $this->customWrapper->render() . PHP_EOL;
            }
            return $out;
        }

        /**
         *  Render the input field including wrappers, notes, description, prepend markup, append markup and error
         *  message
         * @param string $className
         * @param $input
         * @param string $errormsg
         * @param string $successmsg
         * @return string
         */
        public function ___renderDefault(string $className, $input): string
        {

            $out = '';

            $description = $this->description->render() . PHP_EOL;
            $successmsg = $this->getSuccessMessage()->render() . PHP_EOL;
            $errormsg = $this->getErrorMessage()->render() . PHP_EOL;

            switch ($className) {
                case ('InputHidden'):

                    $this->removeAttribute('class');// we need no class attribute for styling on hidden fields
                    $input_markup = $input;
                    break;
                case ('InputCheckbox'):
                case ('InputRadio'):
                case ('Privacy'):
                case ('SendCopy'):

                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $out .= $this->getDescription()->render();
                    }

                    /** Special treatment for label markup */
                    // render label and input different on single checkbox and single radio
                    $this->label->removeAttributeValue('class', $this->getCSSClass('checklabel'));

                    // 1) Label will be appended after checkbox/radio
                    if ($this->appendLabel) {
                        $input = $input . PHP_EOL;
                        $this->label->setContent($this->getLabel()->getText());
                        $input .= $this->label->render() . PHP_EOL;
                    } else {
                        // 2) Input will be displayed within the label tag (default)
                        $this->label->setContent($input . $this->getLabel()->getText());
                        $input = $this->label->render() . PHP_EOL;
                    }

                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {
                        $out .= $this->getDescription()->render();
                    }
                    $input_markup = $input . $errormsg . $successmsg;

                    break;
                default:

                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $out .= $this->getDescription()->render();
                    }
                    if ($this->getLabel()->getText()) {
                        $out .= $this->getLabel()->render() . PHP_EOL;
                    }
                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {

                        $out .= $this->getDescription()->render();
                    }
                    $input_markup = $input . $errormsg . $successmsg;

            }

            // add input-wrapper if set
            if ($this->useInputWrapper) {

                $this->inputWrapper->setContent($input_markup);
                $out .= $this->inputWrapper->render() . PHP_EOL;
            } else {
                $out .= $input_markup;
            }

            // add notes afterwards
            $out .= $this->getNotes()->render();

            // add description at the end if set
            if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterInput')) {
                $out .= $this->getDescription()->render();
            }

            return $out;

        }

        /**
         * Render the input field including wrappers, notes, description, prepend markup, append markup and error
         * message
         * Markup rendering for Uikit 3
         * @param string $className
         * @param $input
         * @return string
         */
        public function ___renderUikit3(string $className, $input): string
        {
            // remove the input wrapper on all checkboxes and radio classes (excluding multiple redios and checkboxes)
            switch ($className) {
                case ('InputCheckbox'):
                case ('InputRadio'):
                case ('Privacy'):
                case ('SendCopy'):
                    $this->useInputWrapper = false;
                    break;
            }
            return $this->renderDefault($className, $input);
        }


        /**
         *  Render the input field including wrappers, notes, description, prepend markup, append markup and error
         *  message
         * Markup rendering for Bootstrap 5
         * @param string $className
         * @param $input
         * @param string $errormsg
         * @param string $successmsg
         * @return string
         */
        public function ___renderBootstrap5(string $className, $input)
        {

            $out = $content = '';
            $description = $this->description->render() . PHP_EOL;
            $successmsg = $this->getSuccessMessage()->render() . PHP_EOL;
            $errormsg = $this->getErrorMessage()->render() . PHP_EOL;


            // add is-valid or is-invalid class to the label tag
            if ($this->getErrorMessage()->getText()) {
                $this->getLabel()->setCSSClass('input_errorClass');
            } else {
                if ($_POST)
                    $this->getLabel()->setCSSClass('input_successClass');
            }

            switch ($className) {
                case ('InputHidden'):
                    $this->removeAttribute('class');// we need no class attribute for styling on hidden fields
                    $input_markup = $input;
                    break;
                case ('InputCheckbox'):
                case ('InputRadio'):
                case ('Privacy'):
                case ('SendCopy'):


                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $content .= $this->getDescription()->render();
                    }

                    /** Special treatment for label markup */
                    // render label and input different on single checkbox and single radio
                    $this->label->removeAttributeValue('class', $this->getCSSClass('labelClass'));
                    $this->label->setCSSClass('checklabelClass');
                    $content .= $input . $this->label->render() . PHP_EOL;

                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {
                        $content .= $this->getDescription()->render();
                    }

                    $input_markup = $content . $errormsg . $successmsg;

                    break;
                default:

                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $content .= $this->getDescription()->render();
                    }

                    if ($this->getLabel()->getText()) {

                        if (($className === 'InputRadioMultiple') || ($className === 'InputCheckboxMultiple')) {
                            if ($this->getErrorMessage()->getText()) {
                                $this->getLabel()->setCSSClass('input_errorClass');
                            } else {
                                $this->getLabel()->setCSSClass('input_successClass');
                            }
                        }

                        $content .= $this->getLabel()->render() . PHP_EOL;
                    }

                    $content .= $input;
                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {
                        $content .= $this->getDescription()->render();
                    }
                    $input_markup = $content . $errormsg . $successmsg;

            }

            // add input-wrapper if set
            if ($this->useInputWrapper) {
                $this->inputWrapper->setContent($input_markup);
                $out .= $this->inputWrapper->render() . PHP_EOL;
            } else {
                $out .= $input_markup;
            }

            // add notes afterwards
            $out .= $this->getNotes()->render();

            // add description at the end if set
            if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterInput')) {
                $out .= $this->getDescription()->render();
            }

            return $out;

        }

        /**
         *  Render the input field including wrappers, notes, description, prepend markup, append markup and error
         *  message
         * Markup rendering for Pico 2
         * @param string $className
         * @param $input
         * @param string $errormsg
         * @param string $successmsg
         * @return string
         */
        public function ___renderPico2(string $className, $input)
        {

            $out = '';
            $description = $this->description->render() . PHP_EOL;
            $successmsg = $this->getSuccessMessage()->render() . PHP_EOL;
            $errormsg = $this->getErrorMessage()->render() . PHP_EOL;

            switch ($className) {
                case ('InputHidden'):
                    $this->removeAttribute('class');// we need no class attribute for styling on hidden fields
                    $input_markup = $input;
                    break;
                case ('InputCheckbox'):
                case ('InputRadio'):
                case ('Privacy'):
                case ('SendCopy'):

                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $out .= $this->getDescription()->render();
                    }

                    /** Special treatment for label markup */

                    // pico does not accept an asterisk inside a tag, so every tag must be removed from the asterisk first
                    $asterisk = '';
                    if ($this->getRules() && array_key_exists('required', $this->getRules())) {
                        $asterisk = ($this->frontendforms['input_showasterisk']) ? strip_tags($this->getLabel()->renderAsterisk()) : '';
                    }


                    // add the asterisk directly after the label text, but before the error message
                    // the label must also contain success and error message
                    $this->label->setContent($input . $this->getLabel()->getText() . $asterisk . $errormsg . $successmsg);

                    // disable the asterisk, so it will not be rendered again afterwards
                    $this->getLabel()->disableAsterisk();
                    $out .= $this->label->render() . PHP_EOL;

                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {
                        $out .= $this->getDescription()->render();
                    }
                    $input_markup = '';

                    break;
                default:

                    // add the description before the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'beforeLabel')) {
                        $out .= $this->getDescription()->render();
                    }
                    if ($this->getLabel()->getText()) {
                        $out .= $this->getLabel()->render() . PHP_EOL;
                    }
                    // add the description after the label
                    if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterLabel')) {
                        $out .= $this->getDescription()->render();
                    }
                    $input_markup = $input . $errormsg . $successmsg;

            }

            // add input-wrapper if set
            if ($this->useInputWrapper) {
                $this->inputWrapper->setContent($input_markup);
                $out .= $this->inputWrapper->render() . PHP_EOL;
            } else {
                $out .= $input_markup;
            }

            // add notes afterwards
            $out .= $this->getNotes()->render();

            // add description at the end if set
            if (($this->getDescription()->getText()) && ($this->getDescription()->getPosition() === 'afterInput')) {
                $out .= $this->getDescription()->render();
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
        public function getRules(): array
        {
            return $this->validatonRules;
        }

        /**
         * Method to clear all validation rules of an element
         * @return void
         */
        public function removeAllRules(): void
        {
            $this->validatonRules = [];
        }

        /**
         * Get the label object (if present)
         * @return Label
         */
        public function getLabel(): Label
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
         * Get the Successmessage object
         * You can use this to manipulate attributes of the sucess message on per field base
         * Example $field->getSuccessMessage()->setAttribute('class', 'myErrorClass');
         * @return Successmessage
         */
        public function getSuccessMessage(): Successmessage
        {
            return $this->successmessage;
        }

        /**
         * Set the success message text
         * Will be set during processing of the form, not by the user
         * @param string $successMessage
         * @return Successmessage
         */
        protected function setSuccessMessage(string $successMessage): Successmessage
        {
            $this->successmessage->setText($successMessage);
            return $this->successmessage;
        }

        /**
         * Get the Description object
         * @return Description
         */
        public function getDescription(): Description
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
        public function getNotes(): Notes
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
         * @param int|string|array|null $default
         * @return $this
         */
        public function setDefaultValue(int|string|array|null $default = null): self
        {
            if (!$this->isSubmitted()) { // set default value(s) only before form is submitted
                if (!is_null($default)) {
                    if (is_int($default)) {
                        $default = (string)($default);
                    } // convert int to string
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
                        // set only default value and value if a value attribute is present, or it is a select input field
                        $this->defaultValue = $default;
                    }
                }
            }
            return $this;
        }

        /**
         * Set a sanitizer from ProcessWire sanitizer methods to sanitize the input value
         * Checks first if the entered sanitizer method exists, otherwise informs you that this method does not exist
         * Check https://processwire.com/api/ref/sanitizer/ for all sanitizer methods
         * @param string $sanitizer - the name of the sanitizer
         * @throws Exception
         */
        public function setSanitizer(string $sanitizer): void
        {
            $sanitizer = trim(strtolower($sanitizer));
            //if sanitizer method exist add the name of the sanitizer to the sanitizer property
            if (method_exists($this->wire('sanitizer'), $sanitizer)) {
                $this->sanitizer = array_merge($this->sanitizer, [$sanitizer]);
            } else {
                throw new Exception('This sanitizer method does not exist in ProcessWire');
            }
        }

        /**
         * Get all sanitizer that were set to a field
         * @return array
         */
        public function getSanitizers(): array
        {
            return $this->sanitizer;
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

        /**
         * Various add and remove methods for adding HTML5 Browser validation attributes depending on validators set
         * Will be added or removed via setRule and removeRule method for field validation
         */

        /**
         * Add HTML5 attribute required to the input tag
         * Validator rule: required
         * @return void
         */
        protected function addHTML5required(): void
        {
            $this->setAttribute('required');
        }

        /**
         * Remove HTML5 required attribute from the input tag
         * Validator rule: required
         * @return void
         */
        protected function removeHTML5required(): void
        {
            $this->removeAttribute('required');
        }


        /**
         * Add HTML5 attribute min to the input tag
         * Validator rule: min
         * @param array $value
         * @return void
         */
        protected function addHTML5min(array $value): void
        {
            $this->setAttribute('min', $value[0]);
        }

        /**
         * Remove HTML5 min attribute from the input tag
         * Validator rule: min
         * @return void
         */
        protected function removeHTML5min(): void
        {
            $this->removeAttribute('min');
        }

        /**
         * Add HTML5 attribute max to the input tag
         * Validator rule: max
         * @param array $value
         * @return void
         */
        protected function addHTML5max(array $value): void
        {
            $this->setAttribute('max', $value[0]);
        }

        /**
         * Remove HTML5 max attribute from the input tag
         * Validator rule: max
         * @return void
         */
        protected function removeHTML5max(): void
        {
            $this->removeAttribute('max');
        }

        /**
         * Add HTML5 attribute minlength to the input tag
         * Validator rule: lengthMin
         * @param array $value
         * @return void
         */
        protected function addHTML5lengthMin(array $value): void
        {
            $this->setAttribute('minlength', (string)$value[0]);
        }

        /**
         * Remove HTML5 minlength attribute from the input tag
         * Validator rule: lengthMin
         * @return void
         */
        protected function removeHTML5lengthMin(): void
        {
            $this->removeAttribute('minlength');
        }

        /**
         * Add HTML5 attribute maxlength to the input tag
         * Validator rule: lengthMax
         * @param array $value
         * @return void
         */
        protected function addHTML5lengthMax(array $value): void
        {
            $this->setAttribute('maxlength', $value[0]);
        }

        /**
         * Remove HTML5 maxlength attribute from the input tag
         * Validator rule: lengthMax
         * @return void
         */
        protected function removeHTML5lengthMax(): void
        {
            $this->removeAttribute('maxlength');
        }

        /**
         * Add HTML5 attribute pattern for alphabetical letters only to the input tag
         * Validator rule: alpha
         * @return void
         */
        protected function addHTML5alpha(): void
        {
            $this->setAttribute('pattern', '[a-zA-Z]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should only contain letters'), $label));
        }

        /**
         * Remove attribute pattern for alphabetical letters only from the input tag
         * Validator rule: alpha
         * Can be used on input type text and textarea
         * @return void
         */
        protected function removeHTML5alpha(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for the BIC code only to the input tag
         * Validator rule: checkBic
         * @return void
         */
        protected function addHTML5checkBic(): void
        {
            $pattern = '[A-Z]{6}[A-Z2-9][A-NP-Z0-9][A-Z0-9]{3}';
            $this->setAttribute('pattern', $pattern);
        }

        /**
         * Remove attribute pattern for the BIC code only from the input tag
         * Validator rule: checkBic
         * Can be used on input type text and textarea
         * @return void
         */
        protected function removeHTML5checkBic(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for the Iban code only to the input tag
         * Validator rule: checkIban
         * @return void
         */
        protected function addHTML5checkIban(): void
        {
            // This pattern is for international IBAN numbers
            $pattern = '[A-Z]{2}\d{13,32}|(?=.{18,42}$)[A-Z]{2}\d{2}( )(\d{4}\1){2,7}\d{1,4}';
            //$pattern = '[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}';
            $this->setAttribute('pattern', $pattern);
        }

        /**
         * Remove attribute pattern for the Iban code only from the input tag
         * Validator rule: checkIban
         * Can be used on input type text and textarea
         * @return void
         */
        protected function removeHTML5checkIban(): void
        {
            $this->removeAttribute('pattern');
        }


        /**
         * Add HTML5 attribute pattern for alphanumerical letters only to the input tag
         * Validator rule: alphaNum
         * @return void
         */
        protected function addHTML5alphaNum(): void
        {
            $this->setAttribute('pattern', '[a-zA-Z0-9]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should only contain letters and numbers'), $label));
        }

        /**
         * Remove attribute pattern for alphanumeric letters only from the input tag
         * Validator rule: alphaNum
         * @return void
         */
        protected function removeHTML5alphaNum(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute minlength and maxlength to the input tag
         * Validator rule: lengthBetween
         * @param array $value
         * @return void
         */
        protected function addHTML5lengthBetween(array $value): void
        {
            $this->setAttribute('minlength ', $value[0]);
            $this->setAttribute('maxlength ', $value[1]);
        }

        /**
         * Remove attribute minlength and maxlength from the input tag
         * Validator rule: lengthBetween
         * @return void
         */
        protected function removeHTML5lengthBetween(): void
        {
            $this->removeAttribute('minlength');
            $this->removeAttribute('maxlength');
        }


        /**
         * Add HTML5 attribute pattern for ascii characters to the input tag
         * Validator rule: ascii
         * @return void
         */
        protected function addHTML5ascii(): void
        {
            $this->setAttribute('pattern ', '[\x00-\x7F]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should only contain ascii characters'), $label));

        }

        /**
         * Remove attribute pattern for ascii characters from the input tag
         * Validator rule: ascii
         * @return void
         */
        protected function removeHTML5ascii(): void
        {
            $this->removeAttribute('pattern');
        }


        /**
         * Add HTML5 attribute pattern for a slug to the input tag
         * Validator rule: slug
         * @return void
         */
        protected function addHTML5slug(): void
        {
            $this->setAttribute('pattern ', '[-a-z0-9_-]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain letters, numbers, underscores or hyphens'), $label));
        }

        /**
         * Remove attribute pattern for a slug from the input tag
         * Validator rule: slug
         * @return void
         */
        protected function removeHTML5slug(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for an url to the input tag
         * Validator rule: url
         * @return void
         */
        protected function addHTML5url(): void
        {
            if (($this->className() != 'InputUrl') || (is_subclass_of($this, 'InputUrl'))) {
                $this->setAttribute('pattern ', 'https?://.+');
                $label = $this->getLabel()->getText();
                $this->setAttribute('title',
                    sprintf($this->_('%s should be a valid URL starting with http:// or https://'), $label));
            }
        }

        /**
         * Remove attribute pattern for an url from the input tag
         * Validator rule: url
         * @return void
         */
        protected function removeHTML5url(): void
        {
            if (($this->className() != 'InputUrl') || (is_subclass_of($this, 'InputUrl'))) {
                $this->removeAttribute('pattern');
            }
        }

        /**
         * Add HTML5 attribute pattern for an email address to the input tag
         * Validator rule: email
         * @return void
         */
        protected function addHTML5email(): void
        {
            if (($this->className() != 'InputEamil') || (is_subclass_of($this, 'InputEmail'))) {
                $this->setAttribute('pattern ',
                    '^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$');
                $label = $this->getLabel()->getText();
                $this->setAttribute('title', sprintf($this->_('%s should be a valid email address'), $label));
            }
        }

        /**
         * Remove attribute pattern for an email from the input tag
         * Validator rule: email
         * @return void
         */
        protected function removeHTML5email(): void
        {
            if (($this->className() != 'InputEamil') || (is_subclass_of($this, 'InputEmail'))) {
                $this->removeAttribute('pattern');
            }
        }

        /**
         * Add HTML5 attribute pattern for a numeric string to the input tag
         * Validator rule: numeric
         * @return void
         */
        protected function addHTML5numeric(): void
        {
            $this->setAttribute('pattern ', '[-+]?[0-9]*[.,]?[0-9]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should only numbers (integers or floats)'), $label));
        }

        /**
         * Remove attribute pattern for a numeric string from the input tag
         * Validator rule: numeric
         * @return void
         */
        protected function removeHTML5numeric(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for an integer to the input tag
         * Validator rule: integer
         * @return void
         */
        protected function addHTML5integer(): void
        {
            $this->setAttribute('pattern ', '[0-9]+');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should only contain integers'), $label));
        }

        /**
         * Remove attribute pattern for an integer from the input tag
         * Validator rule: integer
         * @return void
         */
        protected function removeHTML5integer(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for an IP address to the input tag
         * Validator rule: ip
         * @return void
         */
        protected function addHTML5ip(): void
        {
            $this->setAttribute('pattern ',
                '(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*){3}');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain a valid IP address in the format x.x.x.x'), $label));
        }

        /**
         * Remove attribute pattern for an IP address from the input tag
         * Validator rule: ip
         * @return void
         */
        protected function removeHTML5ip(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for an IP4 address to the input tag
         * Validator rule: ipv4
         * @return void
         */
        protected function addHTML5ipv4(): void
        {
            $this->setAttribute('pattern ', '((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain a valid IPv4 address in the format x.x.x.x'), $label));
        }

        /**
         * Remove attribute pattern for an IP4 address from the input tag
         * Validator rule: ipv4
         * @return void
         */
        protected function removeHTML5ipv4(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for an IP6 address to the input tag
         * Validator rule: ipv6
         * @return void
         */
        protected function addHTML5ipv6(): void
        {
            $this->setAttribute('pattern ', '((^|:)([0-9a-fA-F]{0,4})){1,8}$');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain a valid IPv6 address in the format x:x:x:x:x:x:x:x'), $label));
        }

        /**
         * Remove attribute pattern for an IP6 address from the input tag
         * Validator rule: ipv6
         * @return void
         */
        protected function removeHTML5ipv6(): void
        {
            $this->removeAttribute('pattern');
        }


        /**
         * Add HTML5 attribute pattern for a username to the input tag
         * Validator rule: usernameSyntax
         * @return void
         */
        protected function addHTML5usernameSyntax(): void
        {
            $this->setAttribute('pattern ', '^[-_a-z0-9]\{1,50\}$');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s contains not allowed characters. Please use only letters, numbers, underscores, periods, hyphens and @signs (no whitespaces)'),
                    $label));
        }

        /**
         * Remove attribute pattern for a username from the input tag
         * Validator rule: usernameSyntax
         * @return void
         */
        protected function removeHTML5usernameSyntax(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Method to generate a regex for the password validation depending on the settings of the password field
         * @return string|null - returns the regex for usage in HTML5 validation attribute or null if requirements are
         *     set to "none" in the backend
         * @throws WireException
         * @throws WirePermissionException
         */
        public function createPasswordRegex(): string|null
        {
            // get the password field module
            $passwordModule = $this->wire('modules')->get('InputfieldPassword'); // get the password field module
            // get the password field by the name of the field inside the user template, usually "pass"
            $passwordField = $this->wire('fields')->get('pass');
            // password conditions can be overwritten on per field base, so check before if they are overwritten. Otherwise, take the default from the module
            if (!$passwordField->requirements) {
                // if no requirements are stored in the database, take the default data from the inputfield default configuration
                $requirements = $passwordModule->requirements;
            } else {
                $requirements = $passwordField->requirements;
            }
            if (!in_array('none', $requirements)) {

                // create array of positive look ahead for all options of the password as defined in the backend
                $regex_requirements = [
                    'letter' => '(?=.*[A-Za-z])', // at least 1 letter (upper and lowercase)
                    'lower' => '(?=.*[a-z])', // at least 1 lower case letter
                    'upper' => '(?=.*[A-Z])', // at least 1 upper case letter
                    'digit' => '(?=.*\d)', // at least 1 number
                    'other' => '(?=.*\W)' // at least 1 non-word character
                ];

                $regex_parameters = [];
                foreach ($regex_requirements as $key => $look_ahead) {
                    if (in_array($key, $requirements)) {
                        $regex_parameters[$key] = $look_ahead;
                    }
                }

                // get the default min length value as set in the input field if present
                if ($passwordField->minlength) {
                    $length = (string)$passwordField->minlength;
                } else {
                    // get the default value from the module
                    $length = (string)$passwordModule->minlength;
                }

                // Concatenate all look ahead to working regex string and return it
                return implode('', $regex_parameters) . '.\{' . $length . ',128\}$';
            }
            return null;
        }

        /**
         * Method to add the pattern attribute to the input tag for password syntax
         * @return void
         * @throws WireException
         * @throws WirePermissionException
         */
        protected function addHTML5meetsPasswordConditions(): void
        {
            if (!is_null($this->createPasswordRegex())) {
                $this->setAttribute('pattern', $this->createPasswordRegex());
            }
        }

        /**
         * Remove the password syntax pattern, if validator for the password requirements is no longer present
         * @return void
         */
        protected function removeHTML5meetsPasswordConditions(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for a date in the given format to the input tag
         * Validator rule: dateformat
         * @param array $value
         * @return void
         */
        protected function addHTML5dateFormat(array $value): void
        {
            $format = strtolower($value[0]);

            $dateformats = [
                'dd.mm.yyyy' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}',
                'yyyy.mm.dd' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
                'mm/dd/yyyy' => '(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d\d'
            ];
            if (array_key_exists($format, $dateformats)) {
                $this->setAttribute('pattern ', $dateformats[$format]);
                $label = $this->getLabel()->getText();
                $this->setAttribute('title',
                    sprintf($this->_('%s should only contain a valid date in the format %s'), $label, $format));
            }

        }

        /**
         * Remove attribute pattern for a date in the given format from the input tag
         * Validator rule: dateFormat
         * @return void
         */
        protected function removeHTML5dateFormat(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for a regex to the input tag
         * Validator rule: regex
         * @param array $value
         * @return void
         */
        protected function addHTML5regex(array $value): void
        {
            $pattern = str_replace('$', '', $value[0]); // remove $
            $pattern = str_replace('i', '', $pattern); // remove i
            $pattern = str_replace('/', '', $pattern); // remove /

            $this->setAttribute('pattern ', $pattern);
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s contains an invalid value'), $label));
        }

        /**
         * Remove attribute pattern regex from the input tag
         * Validator rule: regex
         * @return void
         */
        protected function removeHTML5regex(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for exact equal string to the input tag
         * Validator rule: exactValue
         * @param array $value
         * @return void
         */
        protected function addHTML5exactValue(array $value): void
        {
            $this->setAttribute('pattern ', $value[0]);
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should contain the exact value %s'), $label, $value[0]));
        }

        /**
         * Remove attribute pattern exact equal string from the input tag
         * Validator rule: exactValue
         * @return void
         */
        protected function removeHTML5exactValue(): void
        {
            $this->removeAttribute('pattern');
        }


        /**
         * Add HTML5 attribute pattern for different string to the input tag
         * Validator rule: differentValue
         * @param array $value
         * @return void
         */
        protected function addHTML5differentValue(array $value): void
        {
            $this->setAttribute('pattern', '((?!' . $value[0] . ').)*');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title', sprintf($this->_('%s should not contain the value %s'), $label, $value[0]));
        }

        /**
         * Remove attribute pattern different string from the input tag
         * Validator rule: differentValue
         * @return void
         */
        protected function removeHTML5differentValue(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Add HTML5 attribute pattern for different file extensions to the input tag
         * Validator rule: allowedFileExt
         * @param array $value
         * @return void
         */
        protected function addHTML5allowedFileExt(array $value): void
        {
            $accept_extensions = [];
            // add dot in front of file extensions
            if ($value) {
                foreach ($value[0] as $ext) {
                    $accept_extensions[] = '.' . $ext;
                }
            }
            $accept_extensions = implode(',', $accept_extensions);
            $this->setAttribute('accept',
                $accept_extensions); // this is not for the validation - it shows only allowed files in the dialog window!!
        }

        /**
         * Remove attribute pattern different string from the input tag
         * Validator rule: allowedFileExt
         * @return void
         */
        protected function removeHTML5allowedFileExt(): void
        {
            $this->removeAttribute('accept');
        }

        /**
         * Add HTML5 attribute max to the input tag
         * Validator rule: dateBefore
         * @param array $value
         * @return void
         */
        protected function addHTML5dateBefore(array $value): void
        {
            $this->setAttribute('max', $value[0]);
        }

        /**
         * Remove attribute max from the input tag
         * Validator rule: dateBefore
         * @return void
         */
        protected function removeHTML5dateBefore(): void
        {
            $this->removeAttribute('max');
        }

        /**
         * Add HTML5 attribute min to the input tag
         * Validator rule: dateAfter
         * @param array $value
         * @return void
         */
        protected function addHTML5dateAfter(array $value): void
        {
            $this->setAttribute('min', $value[0]);
        }

        /**
         * Remove attribute max from the input tag
         * Validator rule: dateBefore
         * @return void
         */
        protected function removeHTML5dateAfter(): void
        {
            $this->removeAttribute('min');
        }

        /**
         * Add HTML5 attribute pattern to the input tag
         * Validator rule: week
         * @return void
         */
        protected function addHTML5week(): void
        {
            // add pattern only if input type is not of week (fe. InputText)
            if (($this->className() != 'InputWeek') || (is_subclass_of($this, 'InputWeek'))) {
                $this->setAttribute('pattern', '^\d{1,4}-[W](\d|[0-4]\d|5[0123])$');
            }
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain a valid week in the format YYYY-Www'), $label));
        }

        /**
         * Remove attribute pattern from the input tag
         * Validator rule: week
         * @return void
         */
        protected function removeHTML5week(): void
        {
            if (($this->className() != 'InputWeek') || (is_subclass_of($this, 'InputWeek'))) {
                $this->removeAttribute('pattern');
            }
        }

        /**
         * Add HTML5 attribute pattern to the input tag
         * Validator rule: month
         * @return void
         */
        protected function addHTML5month(): void
        {
            if (($this->className() != 'InputMonth') || (is_subclass_of($this, 'InputMonth'))) {
                $this->setAttribute('pattern', '^\d{4}-(0[1-9]|1[012])$');
            }
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should only contain a valid month in the format YYYY-MM'), $label));
        }

        /**
         * Remove attribute pattern from the input tag
         * Validator rule: month
         * @return void
         */
        protected function removeHTML5month(): void
        {
            if (($this->className() != 'InputMonth') || (is_subclass_of($this, 'InputMonth'))) {
                $this->removeAttribute('pattern');
            }
        }

        /**
         * Add HTML5 attribute pattern for HEX code to the input tag
         * Validator rule: checkHex
         * @return void
         */
        protected function addHTML5checkHex(): void
        {
            $this->setAttribute('pattern', '#([a-fA-F0-9]{3}){1,2}\b');
            $label = $this->getLabel()->getText();
            $this->setAttribute('title',
                sprintf($this->_('%s should be a valid HEX code in the format #XXX or #XXXXXX'), $label));
        }

        /**
         * Remove attribute pattern for HEX code validation from the input tag
         * Validator rule: checkHex
         * @return void
         */
        protected function removeHTML5checkHex(): void
        {
            $this->removeAttribute('pattern');
        }

        /**
         * Internal method to set validator attributes to beforeDateField and afterDateField validators
         * Both validators needs a similar setting of attributes
         * @param array $value
         * @param bool $before
         * @return void
         */
        private function beforeAfter(array $value, bool $before): void
        {
            $this->setAttribute('data-ff_field', $value[0]); // get name of the reference field
            $attribute = ($before) ? 'max' : 'min';
            $this->setAttribute('data-ff_attribute', $attribute);
            $this->setAttribute('data-ff_id', $this->getID()); // get the pure ID of this field
            // set the value of the reference field after form submission
            if ($this->form_id_submitted) {
                $date = $_REQUEST[$this->form_id_submitted . '-' . $value[0]];
                if ($before) {
                    // subtract 1 day
                    $new_date = date('Y-m-d', strtotime($date . ' - 1 day'));
                } else {
                    // add 1 day
                    $new_date = date('Y-m-d', strtotime($date . ' + 1 day'));
                }
                $this->setAttribute($attribute, $new_date);
            }
        }

        /**
         * Add HTML5 attribute max to the input tag
         * Validator rule: dateBeforeField
         * @param array $value
         * @return void
         */
        protected function addHTML5dateBeforeField(array $value): void
        {
            $this->setAttribute('data-ff_validator', 'dateBeforeField');
            $this->beforeAfter($value, true);
        }

        /**
         * Remove attribute max from the input tag
         * Validator rule: dateBeforeField
         * @return void
         */
        protected function removeHTML5dateBeforeField(): void
        {
            $this->removeAttribute('max');
        }

        /**
         * Add HTML5 attribute max to the input tag
         * Validator rule: dateAfterField
         * @param array $value
         * @return void
         */
        protected function addHTML5dateAfterField(array $value): void
        {
            $this->setAttribute('data-ff_validator', 'dateAfterField');
            $this->beforeAfter($value, false);
        }

        /**
         * Remove attribute min from the input tag
         * Validator rule: dateAfterField
         * @return void
         */
        protected function removeHTML5dateAfterField(): void
        {
            $this->removeAttribute('min');
        }


        private function withinOutside(array $value, string $type): void
        {
            $this->setAttribute('data-ff_field', $value[0]); // get name of the reference field
            $this->setAttribute('data-ff_days', (string)$value[1]);
            $this->setAttribute('data-ff_attribute', ($value[1] > 0) ? 'min' : 'max');
            $this->setAttribute('data-ff_validator', $type);
            $this->setAttribute('data-ff_id', $this->getID()); // get the pure ID of this field
            // set the value of the reference field after form submission
            if ($this->form_id_submitted) {
                if ($type == 'withinOutside') {
                    $this->setAttribute('min', $_REQUEST[$this->form_id_submitted . '-' . $value[0]]);
                    // calculate the new date
                    $this->setAttribute('max', $_REQUEST[$this->form_id_submitted . '-' . $value[0]]);
                } else {
                    if ($value[1] > 0) {
                        $this->setAttribute('min', $_REQUEST[$this->form_id_submitted . '-' . $value[0]]);
                    } else {
                        $this->setAttribute('max', $_REQUEST[$this->form_id_submitted . '-' . $value[0]]);
                    }
                }
            }
        }

        /**
         * Add HTML5 attribute min and max to the input tag
         * Validator rule: dateWithinDaysRange (min - max)
         * @param array $value
         * @return void
         */
        protected function addHTML5dateWithinDaysRange(array $value): void
        {
            $this->withinOutside($value, 'dateWithinDaysRange');
        }

        /**
         * Remove attribute min and max from the input tag
         * Validator rule: dateWithinDaysRange
         * @return void
         */
        protected function removeHTML5dateWithinDaysRange(): void
        {
            $this->removeAttribute('min');
            $this->removeAttribute('max');
        }

        /**
         * Add HTML5 attribute min and max to the input tag
         * Validator rule: dateOutsideOfDaysRange (min - max)
         * @param array $value
         * @return void
         */
        protected function addHTML5dateOutsideOfDaysRange(array $value): void
        {
            $this->withinOutside($value, 'dateOutsideOfDaysRange');
        }

        /**
         * Remove attribute min and max from the input tag
         * Validator rule: dateOutsideOfDaysRange
         * @return void
         */
        protected function removedateOutsideOfDaysRange(): void
        {
            $this->removeAttribute('min');
            $this->removeAttribute('max');
        }

        /**
         * Method to enable/disable the usage of area attributes for better accessibility
         * @param bool $ariaAttr
         * @return $this
         */
        public function useAriaAttributes(bool $ariaAttr): self
        {
            $this->useAriaAttr = $ariaAttr;
            return $this;
        }

        /**
         * Set various Aria attributes for the input element
         * @return void
         */
        protected function addAriaAttributes(): void
        {
            if ($this->useAriaAttr) {

                // aria describedby for description
                if ($this->getDescription()->getText()) $this->setAttribute('aria-describedby', $this->getID() . '-desc');

                // aria describedby for notes
                if ($this->getNotes()->getText()) $this->setAttribute('aria-describedby', $this->getID() . '-notes');

            }
        }

    }
