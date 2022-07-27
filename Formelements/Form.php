<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating the form element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Form.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\Field as Field;
use ProcessWire\FrontendForms;
use ProcessWire\WireException;
use Valitron\Validator;
use function ProcessWire\wire as wire;

class Form extends Tag
{
    /* constants */
    const FORMMETHODS = ['get', 'post']; // array that holds allowed action methods (get, post)

    /* properties */
    protected string $doNotReply = ''; // Text for do not reply to automatically generated emails
    protected array $formElements = []; //array that contains all elements of a form element as objects
    protected array $formErrors = []; // holds the array containing all form errors after submission
    protected array $values = []; // array of all form values (key = name of the inputfield)
    protected bool $showForm = true; // show the form on the page
    protected string|int $useDoubleFormSubmissionCheck = 1; // Enable checking of multiple submissions
    protected string $visitorIP = ''; // the IP of the visitor who is visiting this page
    protected int $userLang = 0; // the id of the current user language
    protected string|int $input_useIPBan = 1; // by default check against forbidden IPs
    protected string $input_uploadPath = ''; // The path to upload directory

    /* objects */
    protected Alert $alert; // alert box
    protected RequiredTextHint $requiredHint; // hint to inform that all required fields have to be filled out
    protected Wrapper $formElementsWrapper; // the wrapper object over all form elements

    /**
     * Every form must have an id, so let's add it via the constructor
     * The id will be taken for further automatic id generation of the inputfields
     * @throws WireException
     */
    public function __construct(string $id)
    {
        parent::__construct();

        // check if LanguageSupport module is installed
        if ($this->wire('modules')->isInstalled('LanguageSupport')) {
            $this->userLang = $this->wire('user')->language->id;
        }

        // instantiate all objects first
        $this->alert = new Alert();
        $this->requiredHint = new RequiredTextHint();
        $this->formElementsWrapper = new Wrapper();

        // set default properties
        $this->visitorIP = $this->wire('session')->getIP();
        $this->showForm = $this->allowFormViewByIP(); // show or hide the form depending on IP ban
        $this->setAttribute('method', 'post'); // default is post
        $this->setAttribute('action', $this->wire('page')->url); // stay on the same page
        $this->setAttribute('id', $id); // set the id
        $this->setAttribute('novalidate'); // set novalidate by default
        $this->setTag('form'); // set the form tag
        $this->setCSSClass('formClass'); // add the CSS class
        $this->setSuccessMsg($this->getLangValueOfConfigField('input_alertSuccessText'));
        $this->setErrorMsg($this->getLangValueOfConfigField('input_alertErrorText'));
        $this->setRequiredTextPosition($this->input_requiredHintPosition); // set the position for the required text
        $this->getFormElementsWrapper()->setAttribute('id',
            $this->getAttribute('id') . '-formelementswrapper'); // add id
        $this->getFormElementsWrapper()->setAttribute('class',
            $this->input_wrapperFormElementsCSSClass); // add css class to wrapper element
        $this->useDoubleFormSubmissionCheck($this->useDoubleFormSubmissionCheck);
        $this->setRequiredText($this->getLangValueOfConfigField('input_requiredText'));
        $this->logFailedAttempts($this->input_logFailedAttempts); // enable or disable the logging of blocked visitor's IP depending on config settings
        $this->setMaxAttempts($this->input_maxAttempts); // set max attempts
        $this->setMinTime($this->input_minTime); // set min time
        $this->setMaxTime($this->input_maxTime); // set max time

        // Global text for auto-generated emails
        $this->doNotReply = $this->_('This email was generated automatically. So please do not reply to this email.');
    }

    /**
     * Output the value of multilang fields from the module configuration
     * @param string $fieldName
     * @return string
     */
    protected function getLangValueOfConfigField(string $fieldName): string
    {
        $fieldNameLang = $fieldName . '__' . $this->userLang;
        if (property_exists($this, $fieldNameLang)) {
            return $this->$fieldNameLang != '' ? $this->$fieldNameLang : $this->$fieldName;
        }
        return $this->$fieldName; // use default
    }

    /**
     * Method to sanitize string, integer or boolean value to integer value 1 and 0
     * This is necessary, because configuration values of checkboxes are stored as integers in the db
     * @param string|int|bool $value
     * @return int
     */
    protected function sanitizeValueToInt(string|int|bool $value): int
    {
        if (is_string($value)) {
            if ($value !== '') {
                return 1;
            }
            return 0;
        } else {
            if (is_int($value)) {
                if ($value >= 1) {
                    return 1;
                }
                return 0;
            } else {
                return (int)$value;
            }
        }
    }

    /**
     * Set a custom upload path for uploaded files
     * @param string $folderName
     * @param bool $createfolder
     * @param bool $keepFiles -> delete files afterwards (false) or keep files (true)
     * @return void
     * @throws WireException
     */
    public function setUploadPath(string $folderName, bool $createfolder = false, bool $keepFiles = false): void
    {
        //sanitize folder name first to remove trailing slashes
        $folderName = ltrim($folderName, '/');
        $folderName = rtrim($folderName, '/');
        $to = $this->wire('config')->paths->assets . 'files/' . $folderName . '/';
        if ($this->isSubmitted()) {
            $from = $this->input_uploadPath;
            $createfolder = true;
        }
        if (!$this->wire('files')->exists($to)) {
            if ($createfolder) {
                $this->wire('files')->mkdir($to);
            } else {
                throw new Exception('This directory does not exist, so the upload path could not be changed.');
            }
        }
        if ($this->isSubmitted()) {
            $this->saveUploadedFile($from, $to, $folderName, $keepFiles);
        }
        // set the property
        $this->input_uploadPath = $to;
    }

    /**
     * Copy uploaded files from the temp_uploads folder to the newly created folder
     * @param string $from -> the folder where the files are stored
     * @param string $to -> the folder where the files should be stored
     * @param string $folderName -> the name of the new folder
     * @param bool $keepFiles -> should the files in the temp folder be kept (true) or deleted afterwards (false)
     * @return void
     * @throws WireException
     */
    private function saveUploadedFile(string $from, string $to, string $folderName, bool $keepFiles): void
    {
        $files = $this->wire('files')->find($from);
        // move files from temp_uploads to the newly created directory
        if ($files) {
            if ($this->wire('files')->copy($from, $to)) {
                if(!$keepFiles){
                    foreach ($files as $file) {
                        // unlink all files
                        $this->wire('files')->unlink($file);
                    }
                }
            }
        }
    }

    /**
     * This method is only for testing of ip addresses that should be banned
     * Enter ip adresses as a numeric array
     * @param string $ip
     * @return void
     * @throws Exception
     */
    public function testIPBan(string $ip): void
    {
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            $this->visitorIP = $ip;
        } else {
            throw new Exception(sprintf($this->_('%s is not a valid IP address.'), $ip));
        }
    }

    /**
     * Disable/enable the IP ban on per form base
     * @param bool $enabled
     * @return void
     */
    public function useIPBan(int|string|bool $enabled): void
    {
        $this->input_useIPBan = $this->sanitizeValueToInt($enabled);
    }

    /**
     * Check if visitor is on the black list or not
     * @return bool - true if visitior is not on the black list
     */
    protected function allowFormViewByIP(): bool
    {
        if (!$this->input_useIPBan) {
            return true;
        }
        if ($this->input_preventIPs === '') {
            return true;
        }
        $ipaddresses = $this->newLineToArray($this->input_preventIPs);
        return !in_array($this->visitorIP, $ipaddresses);
    }

    /**
     * Convert the values of a textbox to an array
     * Will be needed for list of banned IP in the module configuration
     * @param string|null $textarea - the value of the textarea field
     * @return array
     */
    protected function newLineToArray(string $textarea = null): array
    {
        $final_array = [];
        if (!is_null($textarea)) {
            $textarea_array = array_map('trim', explode("\n", $textarea)); // remove extra spaces from each array value
            foreach ($textarea_array as $textarea_arr) {
                $final_array[] = trim($textarea_arr);
            }
        }
        return $final_array;
    }

    /**
     * Enable or disable the logging of blocked visitor's IP on per form base
     * True: logging is enabled
     * False: logging is disabled
     * @param string|bool|int $logFailedAttempts
     * @return void
     */
    public function logFailedAttempts(string|bool|int $logFailedAttempts): void
    {
        $this->input_logFailedAttempts = $this->sanitizeValueToInt($logFailedAttempts);
    }

    /**
     * Convert post values to a string
     * @param bool $showButtonValues
     * @return string
     */
    public function getValuesAsString(bool $showButtonValues = false): string
    {
        $postData = $this->flattenMixedArray($this->getValues($showButtonValues));
        $dataAttributes = array_map(function ($value, $key) {
            return $key . '=' . $value;
        }, array_values($postData), array_keys($postData));
        return implode(', ', $dataAttributes);
    }

    /**
     * Returns a numeric array of all available languages
     * Only for internal usage
     *
     * @return array
     * @throws WireException
     */
    protected function getAllAvailableLanguages(): array
    {
        $path = $this->wire('config')->paths->siteModules . 'FrontendForms/lang';
        $langFiles = $this->wire('files')->find($path);
        $languages = [];
        foreach ($langFiles as $lang) {
            $languages[] = basename($lang, '.php');
        }
        return $languages;
    }

    /**
     * Set the langue of the form directly in the form object
     * @param string|null $languageCode
     * @return void
     * @throws WireException
     */
    public function setLang(?string $languageCode): void
    {
        if (!is_null($languageCode) && in_array($languageCode, $this->getAllAvailableLanguages())) {
            $frontendforms = new FrontendForms();
            $frontendforms->setLang($languageCode);
        }
    }

    /**
     * Enable/disable the wrapping of checkboxes by its label
     * This is useful for some cases where you need to add the label after the input (fe. some CSS frameworks
     * @param bool $wrap
     * @return void
     */
    public function appendLabelOnCheckboxes(bool $wrap): void
    {
        $this->appendcheckbox = $wrap;
    }

    /**
     * Get the value of appendcheckbox
     * @return bool
     */
    protected function getAppendLabelOnCheckboxes(): bool
    {
        return $this->appendcheckbox;
    }

    /**
     * Enable/disable the wrapping of checkboxes by its label
     * This is useful for some cases where you need to add the label after the input (fe. some CSS frameworks
     * @param bool $wrap
     * @return void
     */
    public function appendLabelOnRadios(bool $wrap): void
    {
        $this->appendradio = $wrap;
    }

    /**
     * Get the value of appendradio
     * @return bool
     */
    protected function getAppendLabelOnRadios(): bool
    {
        return $this->appendradio;
    }

    /**
     * Set your own text for required fields
     * @param string $requiredText
     * @return RequiredTextHint
     */
    public function setRequiredText(string $requiredText): RequiredTextHint
    {
        if ($requiredText === '') {
            $requiredText = $this->_('All fields marked with (*) are mandatory and must be completed.');
        }
        $this->requiredHint->setText($requiredText);
        return $this->requiredHint;
    }

    /**
     * Get the required text hint object for further manipulations
     * @return RequiredTextHint
     */
    public function getRequiredText(): RequiredTextHint
    {
        return $this->requiredHint;
    }

    /**
     * Enable/disable checking of double form submissions
     * True: enabled
     * False: disabled
     * @param bool $useDoubleFormSubmissionCheck
     * @return void
     * @throws WireException
     */
    public function useDoubleFormSubmissionCheck(int|string|bool $useDoubleFormSubmissionCheck): void
    {
        $useDoubleFormSubmissionCheck = $this->sanitizeValueToInt($useDoubleFormSubmissionCheck); // sanitize to int

        $this->useDoubleFormSubmissionCheck = $useDoubleFormSubmissionCheck; // set the property
        if ($useDoubleFormSubmissionCheck) {

            // check if session exists
            if ($this->wire('session')->get('doubleSubmission-' . $this->getID())) {
                $this->doubleSubmission = $this->wire('session')->get('doubleSubmission-' . $this->getID());
            } else {
                $this->doubleSubmission = uniqid();
                $this->wire('session')->set('doubleSubmission-' . $this->getID(), $this->doubleSubmission);
            }
        } else {
            // remove the session if present
            $this->wire('session')->remove('doubleSubmission-' . $this->getID());
        }
    }

    /**
     * This method creates an inner wrapper over all form elements if set to true, or it removes the wrapper if set to
     * false
     * So it adds a <div> tag after the opening form tag and a </div> tag before the closing form tag
     * @param bool $useFormElementsWrapper
     * @return Wrapper
     */
    public function useFormElementsWrapper(int|string|bool $useFormElementsWrapper): Wrapper
    {
        $useFormElementsWrapper = $this->sanitizeValueToInt($useFormElementsWrapper); // sanitize to int
        $this->input_wrapperFormElements = $useFormElementsWrapper;
        return $this->formElementsWrapper;
    }

    /**
     * Return the wrapper object
     * @return Wrapper
     */
    public function getFormElementsWrapper(): Wrapper
    {
        return $this->formElementsWrapper;
    }

    /**
     * Set the success message for successfull form submission
     * Can be used to overwrite the default success message
     * @param string $successMsg
     * @return void
     */
    public function setSuccessMsg(string $successMsg): void
    {
        if ($successMsg === '') {
            $successMsg = $this->_('Thank you for your message.');
        }
        $this->input_alertSuccessText = trim($successMsg);
    }

    /**
     * Set the error message if errors occur after form submission
     * Can be used to overwrite the default error message
     * @param string $errorMsg
     * @return void
     */
    public function setErrorMsg(string $errorMsg): void
    {
        if ($errorMsg === '') {
            $errorMsg = $this->_('Sorry, some errors occur. Please check your inputs once more.');
        }
        $this->input_alertErrorText = trim($errorMsg);
    }

    /**
     * Static method to check if SeoMaestro is installed or not
     * returns the SeoMaestro object on true, otherwise null
     * @return Field|null
     */
    public static function getSeoMaestro(): ?Field
    {
        if (wire('modules')->isInstalled("SeoMaestro")) {
            // grab seo maestro input field
            $seoField = wire('fields')->find('type=FieldtypeSeoMaestro');
            if ($seoField) {
                return $seoField->first();
            }
        }
        return null;
    }

    /**
     * Get the value of a specific formfield after form submission by its name
     * Can be used to send fe this value via email to a recipient or store it inside the db
     * @param string $name - the name attribute of the input field
     * @return string|array|null
     */
    public function getValue(string $name): string|array|null
    {
        $name = $this->createElementName(trim($name));
        return !$this->getValues() ? $this->getValues() : $this->getValues()[$name];
    }

    /**
     * Add the form id as prefix to the name attribute
     * @param string $name - the name attribute of the element
     * @return string - returns the name attribute including the form id as prefix
     */
    private function createElementName(string $name): string
    {
        $name = trim($name);
        $formID = $this->getID();
        if (!str_starts_with($name, $formID)) {
            $name = $formID . '-' . $name;
        }
        return $name;
    }

    /**
     * Get all form values after form submission as an array
     * @param bool $buttonValue
     * @return array|null
     */
    public function getValues(bool $buttonValue = false): array|null
    {
        if ($buttonValue) {
            return $this->values;
        }
        $result = array_intersect($this->getNamesOfInputFields(), $this->getNamesOfInputFields());
        $values = [];
        foreach ($result as $key) {
            // check if inputfield is a file upload field
            $formElement = $this->getFormelementByName($key);
            if ($formElement instanceof InputFile) {
                $values[$key] = $_FILES[$key]['name'];
            } else {
                $values[$key] = $this->values[$key];
            }
        }
        return $values; // array
    }

    /**
     * Get a specific element of the form by entering the name of the element as parameter
     * With this method you can grab and manipulate a specific element
     * @param string $name - the name attribute of the element (fe email)
     * @param boolean $checkPrefix - true to check if form id is added for inputfield name or false to ignore this
     * @return object - the form element object
     */
    public function getFormelementByName(string $name, bool $checkPrefix = true): object
    {
        //check if id of the form was added as prefix of the element name
        if ($checkPrefix) {
            $name = $this->createElementName($name);
        }
        return current(array_filter($this->formElements, function ($e) use ($name) {
            return $e->getAttribute('name') == $name;
        }));
    }

    /**
     * Overwrite the global setting for the required text position on per form base
     * @param string $position - has to be 'top' or 'bottom'
     * @return void
     */
    public function setRequiredTextPosition(string $position): void
    {
        $position = trim($position);
        $this->defaultRequiredTextPosition = in_array($position, ['none', 'top', 'bottom']) ? $position : 'top';
    }

    /**
     * Get the alert object for further manipulations
     * @return Alert
     */
    public function getAlert(): Alert
    {
        return $this->alert;
    }

    /**
     * If you want to disable it, add this method to the form object - not recommended
     * @param int|string|bool $honeypot
     * @return void
     */
    public function useHoneypot(int|string|bool $honeypot): void
    {
        $this->input_useHoneypot = $this->sanitizeValueToInt($honeypot);
    }

    /**
     * Method to rearrange the multiple files array $_FILES
     * @param array $file_post
     * @return array
     */
    private function reArrayFiles(array $file_post): array
    {
        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);
        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }
        return $file_ary;
    }

    /**
     * Internal method to store uploaded files via InputFile field in the choosen folder
     * @param array $formElements
     * @return void
     */
    private function storeUploadedFiles(array $formElements): void
    {
        if ($_FILES) {
            // get all upload fields inside the form
            foreach ($formElements as $element) {
                if ($element instanceof InputFile) {
                    $fieldName = $element->getAttribute('name'); // the name of the upload field
                    if ($element->getAllowMultiple()) {
                        // multiple files
                        $files = $this->reArrayFiles($_FILES[$fieldName]);
                        foreach ($files as $file) {
                            $target_file = $this->input_uploadPath . basename($file['name']);
                            move_uploaded_file($file['tmp_name'], $target_file);
                        }
                    } else {
                        // single file
                        $file = $_FILES[$fieldName];
                        $target_file = $this->input_uploadPath . basename($file['name']);
                        move_uploaded_file($file['tmp_name'], $target_file);
                    }
                }
            }
        }
    }

    /**
     * Process the form after form submission
     * Includes sanitization and validation
     * @return bool - true: form is valid, false: form has errors
     * @throws WireException
     * @throws Exception
     */
    public function isValid(): bool
    {
        $formMethod = $this->getAttribute('method'); // grab the method (get or post)
        $input = $this->wire('input')->$formMethod; // get the GET or POST values after submission
        $formElements = $this->formElements; //grab all form elements as an array of objects
        // instantiates the FormValidation object
        $validation = new FormValidation($input, $this, $this->alert);

        // 1) check if this form was submitted and no other form on the same page
        if ($validation->thisFormSubmitted()) {
            // 2) check if form was submitted in time range
            if ($validation->checkTimeDiff($formElements)) {
                // 3) check if max attempts were reached
                if ($validation->checkMaxAttempts($this->input_logFailedAttempts)) {
                    // 4) check for double form submission
                    if ($validation->checkDoubleFormSubmission($this, $this->useDoubleFormSubmissionCheck)) {
                        // 5) Check for CSRF attack
                        if ($this->wire('session')->CSRF->hasValidToken()) {

                            /* START PROCESSING THE FORM */

                            //add honeypotfield to the array because it will be rendered afterwards
                            if ($this->input_useHoneypot) {
                                $formElements[] = $this->createHoneypot();
                            }

                            // Get only input field for user inputs (no fieldsets, buttons,..)
                            $formElements = $validation->getRealInputFields($formElements);

                            // Run sanitizer on all POST values first
                            $sanitizedValues = [];
                            foreach ($formElements as $element) {
                                $sanitizedValues[$element->getAttribute('name')] = $validation->sanitizePostValue($element);
                            }
                            // Instantiate Valitron and start validation of the sanitized values
                            $v = new Validator($sanitizedValues);

                            foreach ($formElements as $element) {
                                // run validation only if there is at least one validation rule set
                                if (count($element->getRules()) > 0) {
                                    foreach ($element->getRules() as $validatorName => $parameters) {
                                        $v->rule($validatorName, $element->getAttribute('name'), ...
                                            $parameters['options']);
                                        // Add custom error message text if present
                                        if (isset($parameters['customMsg'])) {
                                            $v->message($parameters['customMsg']);
                                        }
                                        if (isset($parameters['customFieldName'])) {
                                            $v->label($parameters['customFieldName']);
                                        }
                                    }
                                }

                                // add honeypot validation if honeypot field is included
                                if ($this->input_useHoneypot) {
                                    if ($element->getAttribute('name') == 'seca') {
                                        $v->rule('length', 'seca',
                                            0)->message($this->_('Please do not fill out this field'));
                                    }
                                }
                                $this->setValues();
                            }
                            if ($v->validate()) {

                                $this->alert->setCSSClass('alert_successClass');
                                $this->alert->setText($this->getSuccessMsg());
                                $this->wire('session')->remove('attempts');
                                // remove attempt session
                                $this->wire('session')->remove('doubleSubmission-' . $this->getID());
                                // remove the session for checking for double form submission
                                $this->showForm = false;
                                // check if files were uploaded and store them inside the choosen folder
                                $this->storeUploadedFiles($formElements);
                                return true;
                            } else {
                                // set error alert
                                $this->formErrors = $v->errors();
                                $this->alert->setCSSClass('alert_dangerClass');
                                $this->alert->setText($this->getErrorMsg());
                                // add max attempts warning message to error message
                                if ($this->getMaxAttempts()) {
                                    $attemptDiff = $this->getMaxAttempts() - $this->wire('session')->attempts;
                                    if ($attemptDiff <= 3) {
                                        $plural = $this->_('attempts');
                                        $singular = $this->_('attempt');
                                        $attempts = $this->_n($singular, $plural, $attemptDiff);
                                        $attemptWarningText = '<br>' . sprintf($this->_('You have %s %s left until you will be blocked due to security reasons.'),
                                                $attemptDiff, $attempts);
                                        $this->alert->setText($this->alert->getText() . $attemptWarningText);
                                    }
                                }

                                // create session for max attempts if set, otherwise add 1 attempt.
                                //this session contains the number of failed attempts and will be increased by 1 on each failed attempt
                                if ($this->getMaxAttempts()) {
                                    if ($this->wire('session')->attempts) {
                                        $this->wire('session')->attempts = $this->wire('session')->attempts + 1;
                                    } else {
                                        $this->wire('session')->attempts = 1;
                                    }
                                }
                                return false;
                            }
                            /* END PROCESSING THE FORM */
                        }
                        // CSRF attack
                        die();
                        // live a great life and die() gracefully.
                    }
                    //double form submission
                    return false;
                }
                //max attempts were reached
                return false;
            }
            // submission time was too short or to long
            return false;
        }
        // this form was not submitted
        return false;
    }

    /**
     * Create a honeypot field for spam protection
     * @return InputText
     */
    private function createHoneypot(): InputText
    {
        $honeypot = new InputText('seca');
        $honeypot->setLabel($this->_('Please do not fill out this field'))->setAttribute('class', 'seca');
        // Remove or add wrappers depending on settings
        $honeypot->useInputWrapper($this->useInputWrapper);
        $honeypot->useFieldWrapper($this->useFieldWrapper);
        $honeypot->getFieldWrapper()->setAttribute('class', 'seca');
        $honeypot->getInputWrapper()->setAttribute('class', 'seca');
        $honeypot->setAttributes(['class' => 'seca', 'tabindex' => '-1']);
        return $honeypot;
    }

    /**
     * Add the input wrapper to all fields of this form in general
     * @param bool $useInputWrapper
     * @return void
     */
    public function useInputWrapper(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;
    }

    /**
     * Add the field wrapper to all fields of this form in general
     * @param bool $useFieldWrapper
     * @return void
     */
    public function useFieldWrapper(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;
    }

    /**
     * Internal method to add all form values to the values array
     * @return void
     */
    private function setValues(): void
    {
        $values = [];
        foreach ($this->formElements as $element) {
            $values[$element->getAttribute('name')] = $element->getAttribute('value');
        }
        $this->values = $values;
    }

    /**
     * Get the success message
     * @return string
     */
    protected function getSuccessMsg(): string
    {
        return $this->input_alertSuccessText;
    }

    /**
     * Get the error message
     * @return string
     */
    protected function getErrorMsg(): string
    {
        return $this->input_alertErrorText;
    }

    /**
     * Get the max attempts
     * @return int
     */
    protected function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    /** Public methods to add or remove input wrapper and field wrapper on each form field in general */

    /**
     * Set the max attempts
     * @param int $maxAttempts
     * @return void
     */
    public function setMaxAttempts(int $maxAttempts): void
    {
        if ($maxAttempts < 1) {
            $this->input_logFailedLogins = 0;
        } //disable logging of failed attempts
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Method to run if a user has taken too much attempts
     * This method has to be before the render method of the form
     * You can use it fe to save some data to the database -> you got the idea
     * @return bool -> returns true if the user is blocked, otherwise false
     * @throws WireException
     */
    public function isBlocked(): bool
    {
        if ($this->wire('session')->get('blocked')) {
            return true;
        }
        return false;
    }

    /**
     * Render the form markup (inluding alerts if present) on the frontend
     * @return string
     * @throws WireException
     */
    public function render(): string
    {
        /* Check if form contains file upload fields and add enctype
        independent whether it is present or not */
        foreach ($this->formElements as $obj) {
            if ($obj instanceof InputFile) {
                $this->setAttribute('enctype', 'multipart/form-data');
                break;
            }
        }


        if (!$this->allowFormViewByIP()) {
            $this->alert->setCSSClass('alert_warningClass');
            $this->alert->setText(sprintf($this->_('We are sorry, but your IP address %s is on the list of forbidden IP addresses. Therefore the form will not be displayed. If you think your IP address is mistakenly on the list, please contact the administrator of the site.'),
                $this->visitorIP));
        }

        $out = $this->prepend;
        $out .= $this->append;
        // allow only get or post - if value is not get or post set post as default value
        if (!in_array(strtolower($this->getAttribute('method')), self::FORMMETHODS)) {
            $this->setAttribute('method', 'post');
        }

        // get token for CSRF protection
        $tokenName = $this->wire('session')->CSRF->getTokenName();
        $tokenValue = $this->wire('session')->CSRF->getTokenValue();
        // get keys of all inputfields (excluding buttons, fieldsets,.. only inputfields that collect user data)
        $inputfieldKeys = [];

        foreach ($this->formElements as $key => $inputfield) {
            if (is_subclass_of($inputfield, 'FrontendForms\Inputfields')) {
                $inputfieldKeys[] = $key;
            }
        }
        // Add honeypot field only if at least 1 input field is present
        if (count($inputfieldKeys)) {
            // Choose an inputfield randomly by its key value
            shuffle($inputfieldKeys);
            $randomFieldNumber = $inputfieldKeys[0];
            // add honeypot on the random number field position
            if ($this->input_useHoneypot) {
                array_splice($this->formElements, $randomFieldNumber, 0, [$this->createHoneypot()]);
            }
        }

        //create CSRF hidden field and add it to the form at the end
        $hiddenField = new InputHidden('post_token');
        $hiddenField->setAttribute('name', $tokenName);
        $hiddenField->setAttribute('value', $tokenValue);
        $this->add($hiddenField);
        //create hidden field to prevent double form submission if it was not disabled
        if ($this->useDoubleFormSubmissionCheck) {
            $hiddenField2 = new InputHidden('doubleSubmission_token');
            $hiddenField2->setAttribute('name', 'doubleSubmission_token');
            $hiddenField2->setAttribute('value', $this->doubleSubmission);
            $this->add($hiddenField2);
        }
        //create hidden field to send form id to check if this form was submitted
        //this is only there for the case if other forms are present on the same page
        $hiddenField3 = new InputHidden('form_id');
        $hiddenField3->setAttribute('name', 'form_id');
        $hiddenField3->setAttribute('value', $this->getID());
        $this->add($hiddenField3);
        //create hidden field to send the timestamp (encoded) when the form was loaded
        if (($this->getMinTime()) || $this->getMaxTime()) {
            $hiddenField4 = new InputHidden('load_time');
            $hiddenField4->setAttribute('value', $this->encryptDecrypt((string)time()));
            $this->add($hiddenField4);
        }

        /* BLOCKING ALERTS */
        if ($this->wire('session')->get('blocked')) {
            // set danger alert for blocking messages
            $this->alert->setCSSClass('alert_dangerClass');
            // return blocking text for too much failed attempts
            if ($this->wire('session')->get('blocked') == 'maxAttempts') {
                if ($this->wire('session')->get('attempts') == $this->getMaxAttempts()) {
                    $this->alert->setText($this->_('You have reached the max. number of allowed attempts and therefore you cannot submit the form once more. To reset the blocking and to submit the form anyway you have to close this browser, open it again and visit this page once more.'));
                }
            }
        }

        // Output the form markup
        $out .= $this->alert->___render();
        // render the alert box on top for success or error message
        // show form only if user is not blocked
        if ($this->showForm && (($this->wire('session')->get('blocked') == null))) {

            //add required texts
            $this->prepend($this->renderRequiredText('top')); // required text hint at top
            $this->append($this->renderRequiredText('bottom')); // required text hint at bottom
            $formElements = '';
            foreach ($this->formElements as $element) {
                //create input ID as a combination of form id and input name
                $oldId = $element->getAttribute('id');
                $element->setAttribute('id', $this->getID() . '-' . $oldId);
                // change the name attribute of the CSRF field
                if ($element->getID() == $this->getID() . '-post_token') {
                    $element->setAttribute('name', $tokenName);
                }

                // Label (Only on inputfields)
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                    // add unique id to the fieldwrapper if present
                    $element->getFieldWrapper()?->setAttribute('id',
                        $this->getID() . '-' . $oldId . '-fieldwrapper');
                    // add unique id to the inputwrapper if present
                    $element->getInputWrapper()?->setAttribute('id',
                        $this->getID() . '-' . $oldId . '-inputwrapper');
                    $element->getLabel()?->setAttribute('for', $element->getAttribute('id'));
                }
                $name = $element->getAttribute('id');
                /*
                 * Enable/disable wrap of the checkboxes by its label tag by appending the label after the input tag
                 * by using the appendLabel() method .
                 */

                if (($element instanceof InputCheckbox) || ($element instanceof InputCheckboxMultiple)) {
                    $element->appendLabel($this->getAppendLabelOnCheckboxes());
                }
                if (($element instanceof InputRadio) || ($element instanceof InputRadioMultiple)) {
                    $element->appendLabel($this->getAppendLabelOnRadios());
                }
                //add the form id as prefix to name attributes of multiple radios and checkboxes
                if (($element instanceof InputCheckboxMultiple) || ($element instanceof InputRadioMultiple)) {
                    foreach ($element->getOptions() as $cb) {
                        $brackets = ($element instanceof InputCheckboxMultiple) ? '[]' : '';
                        $cb->setAttribute('name', $name . $brackets);
                    }
                }

                if (array_key_exists($name, $this->formErrors)) {
                    $element->setCSSClass('input_errorClass');
                    // set error class for input element
                    $element->setErrorMessage($this->formErrors[$name][0]);
                    //get first error message
                }
                $formElements .= $element->render() . PHP_EOL;
            }

            // add formElementsWrapper -> add the div container after the form tag
            if ($this->input_wrapperFormElements) {
                $this->getformElementsWrapper()->setContent($formElements);
                $formElements = $this->formElementsWrapper->___render() . PHP_EOL;
            }
            // render the form with all its fields
            $this->setContent($formElements);
            $out .= $this->renderNonSelfclosingTag($this->getTag());
        }
        return $out;
    }

    /**
     * Append a field object to the form
     * @param object $field - object of inputfield, fieldset, button,...
     * @return void
     */
    public function add(object $field): void
    {
        // add or remove wrapper divs on each form element
        if (is_subclass_of($field, 'FrontendForms\Inputfields')) {
            $field->useInputWrapper($this->useInputWrapper);
            $field->useFieldWrapper($this->useFieldWrapper);
        }
        // Add id of the form as prefix for the name attribute of the field
        $field->setAttribute('name', $this->getID() . '-' . $field->getId());
        $this->formElements = array_merge($this->formElements, [$field]); // array must be numeric for honeypot field
    }

    /**
     * Get the min time value
     * @return int
     */
    protected function getMinTime(): int
    {
        return $this->minTime;
    }

    /**
     * Set the min time in seconds before the form should be submitted
     * @param int $minTime
     * @return $this
     */
    public function setMinTime(int $minTime): self
    {
        $this->minTime = $minTime;
        return $this;
    }

    /**
     * Get the max time value
     * @return int
     */
    protected function getMaxTime(): int
    {
        return $this->maxTime;
    }

    /**
     * Set the max time in seconds until the form should be submitted
     * @param int $maxTime
     * @return $this
     */
    public function setMaxTime(int $maxTime): self
    {
        $this->maxTime = $maxTime;
        return $this;
    }

    /**
     * Encrypt/Decrypt Function
     * @param string $string - the value that should be encrypted/decrypted
     * @param string $action - encrypt or decrypt
     * @return string - the encrypted/decrypted string
     */
    protected function encryptDecrypt(string $string, string $action = 'encrypt'): string
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282';
        // user define private key
        $secret_iv = '5fgf5HJ5g27';
        // user define secret key
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        // sha256 is hash_hmac_algo
        $output = '';
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } elseif ($action == 'decrypt') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }
        return $output;
    }

    /**
     * Create required hint text element if showTextHint is set to true
     * @param string $position - has to be 'top' or 'bottom'
     * @return string
     */
    private function renderRequiredText(string $position): string
    {
        if ($this->defaultRequiredTextPosition === $position) {
            return $this->requiredHint->___render();
        }
        return ''; // return empty string
    }

    /**
     * Create a random string with a certain lenght for usage in URL query strings
     * @param int $charLength - the length of the random string - default is 100
     * @return string - returns a slug version of the generated random string that can be used inside an url
     */
    protected function createQueryCode(int $charLength = 100): string
    {
        $pass = new \ProcessWire\Password();
        if ($charLength <= 0) {
            $charLength = 10;
        }
        // instantiate a password object to use the methods
        $string = $pass->randomBase64String($charLength);
        return $this->generateSlug($string);
    }

    /**
     * Generate a slug out of a string for usage in urls (fe querystrings)
     * This is only a helper function
     * @param $string - the string
     * @return string
     */
    protected function generateSlug(string $string): string
    {
        return preg_replace('/[^A-Za-z\d-]+/', '-', $string);
    }

    /**
     * Return the names of all inputfields inside a form as an array
     * @return array
     */
    protected function getNamesOfInputFields(): array
    {
        $elements = [];
        if ($this->formElements) {
            foreach ($this->formElements as $element) {
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                    $elements[] = $element->getAttribute('name');
                }
            }
        }
        return array_filter($elements);
    }

    /**
     * Output an error message that email could not be sent due to possible wrong email configuration settings
     * This is a general message that could be used for all forms
     * @return void
     */
    protected function generateEmailSentErrorAlert(): void
    {
        $this->alert->setCSSClass('alert_dangerClass');
        $this->alert->setText($this->_('Email could not be sent due to possible wrong email configuration settings.'));
    }
}
