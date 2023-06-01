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

use DateTime;
use Exception;
use ProcessWire\Field as Field;
use ProcessWire\HookEvent;
use ProcessWire\Language;
use ProcessWire\Module;
use ProcessWire\User;
use ProcessWire\Page;
use ProcessWire\Wire;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use ProcessWire\WireException;
use ProcessWire\WireMail;
use ProcessWire\WirePermissionException;
use Valitron\Validator;
use function ProcessWire\wire as wire;
use function ProcessWire\wirePopulateStringTags;
use function ProcessWire\_n;

class Form extends CustomRules
{

    /* constants */
    const FORMMETHODS = ['get', 'post']; // array that holds allowed action methods (get, post)

    /* properties */
    protected string $doubleSubmission = ''; // value hold by the double form submission session
    protected string $defaultRequiredTextPosition = 'top'; // the default required text position
    protected string $doNotReply = ''; // Text for do not reply to automatically generated emails
    protected array $formElements = []; //array that contains all elements of a form element as objects
    protected array $formErrors = []; // holds the array containing all form errors after submission
    protected array $values = []; // array of all form values (key = name of the inputfield)
    protected bool $showForm = true; // show the form on the page
    protected string $visitorIP = ''; // the IP of the visitor who is visiting this page
    protected string $captchaCategory = ''; // the category of the captcha (text or image)
    protected string $langAppendix = ''; // string, which will be appended to multi-lang config fields inside the db
    protected string|int $useDoubleFormSubmissionCheck = 1; // Enable checking of multiple submissions

    // Mail properties - only needed if FrontendForms will be used to send emails
    protected array $mailPlaceholder = []; // associative array for usage in emails (['placeholdername' => 'text',...])
    protected string $defaultDateFormat = 'Y-m-d'; // the default format for date strings
    protected string $defaultTimeFormat = 'H:i a'; // the default format for time strings
    protected string $receiverAddress = ''; // the email address of the receiver of the mails
    protected string $mail_subject = ''; // the subject for a mail sent after form validation
    protected string $emailTemplatesDirPath = ''; // the path to the email templates directory
    protected string $emailCustomTemplatesDirPath = ''; // the path to the email custom templates directory
    protected string $emailTemplate = ''; // the filename of the email template including extension (fe. template.html)
    protected string $emailTemplatePath = ''; // the path to the body template
    protected string $emailCustomTemplatePath = ''; // the path to the custom body template
    protected array $uploaded_files = []; // array which holds all currently uploaded files with path as value
    protected int|null $mail_language_id = null; // property for setting the language for mail templates manually
    protected int|null $site_language_id = null; // internal property containing the current site language
    /* objects */
    protected Alert $alert; // alert box
    protected RequiredTextHint $requiredHint; // hint to inform that all required fields have to be filled out
    protected Wrapper $formElementsWrapper; // the wrapper object over all form elements
    protected User $user; // the user, who views the form (the page)
    protected Language $userLang; // the language object of the user/visitor
    protected Page $page; // the current page object, where the form is used
    protected object $captcha; // the captcha object

    /**
     * Every form must have an id. You can set it custom via the constructor - otherwise a random ID will be generated.
     * The id will be taken for further automatic id generation of the input fields
     * @throws WireException
     */
    public function __construct(string $id)
    {
        parent::__construct();

        // set path to the template folder for the email templates
        $this->emailTemplatesDirPath = $this->wire('config')->paths->siteModules . 'FrontendForms/email_templates/';
        // set path to the custom template folder for the email templates
        $this->emailCustomTemplatesDirPath = $this->wire('config')->paths->site . 'frontendforms-custom-templates/';

        // set the path to the email template from the module config
        if ($this->frontendforms['input_emailTemplate'] != 'none') {
            $this->emailTemplate = $this->frontendforms['input_emailTemplate']; // set filename
            $this->emailTemplatePath = $this->emailTemplatesDirPath . $this->emailTemplate; // set file path
            $this->emailCustomTemplatePath = $this->emailCustomTemplatesDirPath . $this->emailTemplate; // set file path
        }

        // set the current user
        $this->user = $this->wire('user');

        if ($this->wire('languages')) {
            // set the current site language as language for mails
            $this->mail_language_id = $this->user->language->id;

            // set the id of the current site language
            $this->site_language_id = $this->user->language->id;
        }

        // set the current page
        $this->page = $this->wire('page');

        // check if LanguageSupport module is installed and multi-language is enabled
        if ($this->wire('modules')->isInstalled('LanguageSupport') && isset($this->wire('user')->language)) {
            $this->userLang = $this->user->language; // the language object
        }
        $this->setLangAppendix(); // set the appendix for multi-language module configuration fields (fe. __1012)

        // instantiate all objects first
        $this->alert = new Alert();
        $this->requiredHint = new RequiredTextHint();
        $this->formElementsWrapper = new Wrapper();

        // set default properties
        $this->visitorIP = $this->wire('session')->getIP();
        $this->showForm = $this->allowFormViewByIP(); // show or hide the form depending on IP ban
        $this->setAttribute('method', 'post'); // default is post
        $this->setAttribute('action', $this->page->url); // stay on the same page - needs to run after API is ready
        $this->setAttribute('id', $id); // set the id
        $this->setAttribute('name', $this->getID() . '-' . time());
        $this->setHtml5Validation($this->frontendforms['input_html5_validation']);
        $this->setAttribute('autocomplete', 'off'); // set autocomplete off by default
        $this->setTag('form'); // set the form tag
        $this->setCSSClass('formClass'); // add the CSS class
        $this->setSuccessMsg($this->getLangValueOfConfigField('input_alertSuccessText'));
        $this->setErrorMsg($this->getLangValueOfConfigField('input_alertErrorText'));
        $this->setRequiredTextPosition($this->frontendforms['input_requiredHintPosition']); // set the position for the required text
        $this->getFormElementsWrapper()->setAttribute('id',
            $this->getAttribute('id') . '-formelementswrapper'); // add id
        $this->getFormElementsWrapper()->setAttribute('class',
            $this->frontendforms['input_wrapperFormElementsCSSClass']); // add css class to wrapper element
        $this->useDoubleFormSubmissionCheck($this->useDoubleFormSubmissionCheck);
        $this->setRequiredText($this->getLangValueOfConfigField('input_requiredText'));
        $this->logFailedAttempts($this->frontendforms['input_logFailedAttempts']); // enable or disable the logging of blocked visitor's IP depending on config settings
        $this->setMaxAttempts($this->frontendforms['input_maxAttempts']); // set max attempts
        $this->setMinTime($this->frontendforms['input_minTime']); // set min time
        $this->setMaxTime($this->frontendforms['input_maxTime']); // set max time
        $this->setCaptchaType($this->frontendforms['input_captchaType']); // enable or disable the captcha and set type of captcha
        // set the folder of the page in assets/files as default target folder for file uploads
        $this->setUploadPath($this->wire('config')->paths->assets . 'files/' . $this->page->id . '/');

        // Global text for auto-generated emails
        $this->doNotReply = $this->_('This email was generated automatically. So please do not reply to this email.');

        // create and set all general placeholder variables
        $this->createGeneralPlaceholders();

        // add a hook method to render mail templates before sending the mail
        $this->addHookBefore('WireMail::send', $this, 'renderTemplate');

    }

    /**
     * Get all files that were uploaded
     */
    public function getUploadedFiles():array
    {
        return $this->uploaded_files;
    }

    /**
     * Enable/disable HTML5 form validation
     * @param bool $validation
     * @return $this
     */
    public function setHtml5Validation(string|int|bool|null $validation):self
    {
        $validation = (bool)$validation;
        $this->frontendforms['input_html5_validation'] = $validation;
        if ($validation) {
            $this->removeAttribute('novalidate');
        } else {
            $this->setAttribute('novalidate');
        }
        return $this;
    }

    /**
     * Return if HTML5 form validation is enabled or not
     * @return bool
     */
    public function getHTML5Validation():bool
    {
        return $this->frontendforms['input_html5_validation'];
    }

    /**
     * Enable/disable checking of double form submissions
     * True: enabled
     * False: disabled
     * @param bool $useDoubleFormSubmissionCheck
     * @return void
     * @throws WireException
     */
    public function useDoubleFormSubmissionCheck(int|string|bool $useDoubleFormSubmissionCheck):void
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
     * Should the form be displayed after a successful submission (true or 1) or not (false or 0)
     * By default only the success-message will be displayed after valid form submission and not the whole form
     * This prevents double form submissions
     * @param bool|int $show
     * @return void
     */
    public function showForm(bool|int $show):void
    {
        $this->showForm = $show;
    }

    /**
     * Get the value whether the form should be displayed after successful submission or not
     * @return bool
     */
    public function getShowForm():bool
    {
        return (bool)$this->showForm;
    }

    /**
     * Method, that holds an array with all general placeholders
     * These placeholders can be used in mail templates or mail body templates/texts
     * The array contains the placeholder name as key and its value (placeholder name => value)
     * @return array
     * @throws WireException
     */
    public function generalPlaceholders():array
    {
        return [
            'domainlabel' => $this->_('Domain'),
            'domainvalue' => $this->wire('config')->urls->httpRoot,
            'currenturllabel' => $this->_('Visited page'),
            'currenturlvalue' => $this->wire('input')->httpHostUrl(),
            'iplabel' => $this->_('IP'),
            'ipvalue' => $this->wire('session')->getIP(),
            'currentdatetimelabel' => $this->_('Date/time'),
            'currentdatetimevalue' => $this->getDateTime(),
            'currenttimelabel' => $this->_('Time'),
            'currenttimevalue' => $this->getTime(),
            'currentdatelabel' => $this->_('Date'),
            'currentdatevalue' => $this->getDate(),
            'usernamelabel' => $this->_('Username'),
            'usernamevalue' => $this->user->name,
            'browserlabel' => $this->_('Browser'),
            'browservalue' => $_SERVER['HTTP_USER_AGENT'],
            'donotreplayvalue' => $this->_('This is an auto generated message, please do not reply.')
        ];
    }

    /**
     * Method to add all general placeholders als name => value pair to the placeholder array
     * @return void
     * @throws WireException
     */
    protected function createGeneralPlaceholders():void
    {
        foreach ($this->generalPlaceholders() as $placeholderName => $placeholderValue) {
            $this->setMailPlaceholder($placeholderName, $placeholderValue);
        }
    }

    /**
     * Set the appendix for usage in multi-language configuration fields
     * fe if user has default language, the appendix is an empty string
     * if the user has another language chosen, than the appendix consists of 2 underscores and the lang id (__1012)
     * @return void
     * @throws WireException
     */
    protected function setLangAppendix():void
    {
        if ($this->wire('languages')) {
            $this->langAppendix = $this->userLang->isDefault() ? '' : '__' . $this->userLang->id;
        }
    }

    /**
     * Special general methods for sending emails
     */


    /**
     * Include the body template in the mail if it was set in the configuration or directly on the WireMail object
     * Takes the input_emailTemplate property to check whether a template should be used or not
     * @param Module|wire|WireArray|WireData $mail
     * @return void
     * @throws WireException
     */
    protected function includeMailTemplate(Module|Wire|WireArray|WireData $mail):void
    {
        // set email_template property if it was not set before
        if (!$mail->email_template) {
            $mail->email_template = $this->frontendforms['input_emailTemplate'];
        }


        // check if email template is set
        if ($mail->email_template != 'none') {
            // set body as placeholder
            if ($mail->email_template == 'inherit') {
                // use the value from the FrontendForms module configuration
                $mail->email_template = $this->wire('modules')->getConfig('FrontendForms')['input_emailTemplate'];
            }
            if ($mail->email_template != 'none') {

                if($this->wire('files')->exists($this->emailTemplatesDirPath . $mail->email_template)){
                    $body = $this->loadTemplate($this->emailTemplatesDirPath . $mail->email_template);
                } else if ($this->wire('files')->exists($this->emailCustomTemplatesDirPath . $mail->email_template)){
                    $body = $this->loadTemplate($this->emailCustomTemplatesDirPath . $mail->email_template);
                } else {
                    throw new Exception(sprintf($this->_('Mail could not be sent, because the mail template with the name %s does not exist. Please contact the webmaster and inform him about this error message.'),
                        $mail->email_template));
                }


                // add pre-header text (if present) right after the opening body tag
                if($mail->title){
                    $doc = new \DOMDocument();
                    $doc->loadHTML($body);
                    $bodyTags = $doc->getElementsByTagName('body');
                    if ($bodyTags and $bodyTags->length > 0) {
                        $bodyElement = $bodyTags->item(0);
                        $preheader = $doc->createElement('div', $mail->title.$this->getLitmusHack());
                        $preheader->setAttribute('style', $this->getPreheaderStyle());
                        $bodyElement->insertBefore($preheader, $bodyElement->firstChild);
                        $body = $doc->saveHTML();
                    }
                }

                $placeholders = $this->getMailPlaceholders();
                $body = wirePopulateStringTags($body, $placeholders, ['tagOpen' => '[[', 'tagClose' => ']]']);
                // set the result as the bodyHTML of the email
                $mail->bodyHTML($body);
            }
        } else {
            // add invisible div with email pre-header to the top of the email body
            $mail->bodyHTML($this->generateEmailPreHeader($mail).$mail->bodyHTML);
        }
    }

    /**
     * Check if the form has at least one file upload field
     * Needs to be called after all fields were added
     * @return bool -> true: a file upload field was found, false: no file upload field found
     */
    protected function hasFileUploadField():bool
    {
        if (($this->hasAttribute('enctype')) && ($this->getAttribute('enctype') == 'multipart/form-data')) {
            return true;
        }
        return false;
    }

    /**
     * If file upload fields are present in a form - get an array of objects containing all file upload fields
     * @return array
     */
    protected function getFileUploadFields():array
    {
        $fields = [];
        if ($this->hasFileUploadField()) {
            foreach ($this->formElements as $uploadfield) {
                if (($uploadfield instanceof InputFile) || (is_subclass_of($uploadfield, 'InputFile'))) {
                    $fields[] = $uploadfield;
                }
            }
        }
        return $fields;
    }

    /**
     * Render the mail template: replace placeholders and use HTML email template if set
     * @throws WireException
     */

    public function renderTemplate(HookEvent $event):Module|Wire|WireArray|WireData
    {
        $mail = $event->object;

        // set the placeholder for the title if present
        $this->setMailPlaceholder('title', $mail->title);

        // set the placeholder for the body
        if (($mail->bodyHTML) || ($mail->body)) {

            // set $mail->bodyHTML as prefered value
            $content = $mail->bodyHTML ?: $mail->body;

            if ($content) {

                $body = wirePopulateStringTags($content, $this->getMailPlaceholders(),
                    ['tagOpen' => '[[', 'tagClose' => ']]']);
                $this->setMailPlaceholder('body', $body);
            }

            $mail->bodyHTML($body);
            $mail->body($body);

        }

        $this->includeMailTemplate($mail); // include/use mail template if set
        return $mail;
    }

    /**
     * To prevent double form submissions after the form was valid, this method redirects to the same page
     * This only happens, if the form was validated and there were no errors
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     */
    protected function redirectAfterSubmission():void
    {
        if ($this->wire('session')->get('valid')) {
            $this->wire('session')->remove('valid');
            $this->wire('session')->set('valid-message', '1');
            $this->wire('session')->redirect($this->wire('page')->url);
        }
        if ($this->wire('session')->get('valid-message')) {
            // output success msg
            $this->alert->setCSSClass('alert_successClass');
            $this->alert->setText($this->getSuccessMsg());
            $this->showForm = $this->getShowForm();
            $this->wire('session')->remove('valid-message');
        }
    }

    /**
     * Load a template file from the given path including php code and output it as a string
     * @param string $templatePath - the path to the template that should be rendered
     * @return string - the html template
     */
    protected function loadTemplate(string $templatePath):string
    {
        ob_start();
        include($templatePath);
        $var = ob_get_contents();
        ob_end_clean();
        return $var;
    }

    /**
     * Set the recipient email address on per form base
     * In this case the recipient can be set/changed on per form base instead of directly on the WireMail object
     * Needed in every case, where the WireMail object is not directly reachable
     * @param string $email
     * @return $this
     * @throws WireException
     * @throws Exception
     */
    public function to(string $email):self
    {
        if ($this->wire('sanitizer')->email($email)) {
            $this->receiverAddress = $email;
        } else {
            throw new Exception("Email address for the recipient is not a valid email address.", 1);
        }
        return $this;
    }

    /**
     * Set the subject for the email on per form base
     * In this case the subject can be set/changed on per form base instead of directly on the WireMail object
     * Needed in every case, where the WireMail object is not directly reachable
     * @param string $subject
     * @return $this
     */
    public function subject(string $subject):self
    {
        $this->mail_subject = $subject;
        return $this;
    }

    /**
     * Get a date string in the given format as set in the config of the module
     * If no value is entered as parameter, the current date will be displayed
     * @param string|null $dateTime
     * @return string
     * @throws WireException
     */
    public function getDate(string|null $dateTime = null):string
    {
        $dateTime = (is_null($dateTime)) ? time() : $dateTime;
        // get user language
        if ($this->wire('languages')) {
            $langID = '__' . $this->user->language->id;
        } else {
            $langID = '';
        }
        $fieldName = 'input_dateformat' . $langID;
        $format = $this->frontendforms[$fieldName] ?? $this->defaultDateFormat;
        return $this->wire('datetime')->date($format, $dateTime);
    }

    /**
     * Get a time string in the given format as set in the config of the module
     * If no value is entered as parameter, the current time will be displayed
     * @param string|null $dateTime
     * @return string
     * @throws WireException
     */
    public function getTime(string|null $dateTime = null):string
    {
        $dateTime = (is_null($dateTime)) ? time() : $dateTime;
        // get user language
        if ($this->wire('languages')) {
            $langID = '__' . $this->user->language->id;
        } else {
            $langID = '';
        }
        $fieldName = 'input_timeformat' . $langID;
        $format = $this->frontendforms[$fieldName] ?? $this->defaultTimeFormat;
        return $this->wire('datetime')->date($format, $dateTime);
    }

    /**
     * Get a combined date and time string in the given format as set in the config of the module
     * If no value is entered as parameter, the current date and time will be displayed
     * @param string|null $dateTime
     * @return string
     * @throws WireException
     */
    public function getDateTime(string|null $dateTime = null):string
    {
        return $this->getDate($dateTime) . ' ' . $this->getTime($dateTime);
    }

    /**
     * Set a new placeholder variable with a specific value to the mailPlaceholder array
     * @param string $placeholderName
     * @param string|array|null $placeholderValue
     * @return $this
     */
    public function setMailPlaceholder(string $placeholderName, string|array|null $placeholderValue):self
    {

        if (!is_null($placeholderValue)) {
            $placeholderName = strtoupper(trim($placeholderName));
            if (is_array($placeholderValue)) {
                // check if array is multidimensional like multiple file uploads
                if (count($placeholderValue) == count($placeholderValue, COUNT_RECURSIVE)) {
                    // one-dimensional: convert array of values to comma separated string
                    $placeholderValue = implode(', ', $placeholderValue);
                } else {
                    $file_names = [];
                    // multi-dimensional $_FILES array
                    foreach ($placeholderValue as $file) {
                        // adding all file names to the array - independent if the name exists or not
                        $file_names[] = $file['name'];
                    }
                    // clean the array by removing empty array elements
                    $placeholderValue = implode(',', array_filter($file_names));
                }
            }
            // trim and merge it to the mailPlaceholder array
            $this->mailPlaceholder = array_merge($this->getMailPlaceholders(),
                [$placeholderName => trim($placeholderValue)]);
        }
        return $this;
    }

    /**
     * Remove a placeholder by its name from the placeholder array if it is present
     * @param string $placeholderName
     * @return void
     */
    public function removePlaceholder(string $placeholderName):void
    {
        $key = strtoupper(trim($placeholderName));
        if (array_key_exists($key, $this->getMailPlaceholders())) {
            unset($this->getMailPlaceholders()[$key]);
        }
    }

    /**
     * Get all placeholder variables and their values
     * For usage in body template of emails
     * @return array
     */
    public function getMailPlaceholders():array
    {
        return $this->mailPlaceholder;
    }

    /**
     * Get the value of a certain placeholder by its name
     * @param string $placeholderName
     * @return string
     */
    public function getMailPlaceholder(string $placeholderName):string
    {
        $content = '';
        $placeholderName = strtoupper($placeholderName);
        if (array_key_exists($placeholderName, $this->mailPlaceholder)) {
            $content = $this->mailPlaceholder[$placeholderName];
        }
        return $content;
    }

    /**
     * Get all included classes of the form fields
     * For usage in body template of emails
     * @return array
     */
    protected function getFormFieldClasses():array
    {
        $classes = [];
        foreach ($this->formElements as $fieldObject) {
            $classes[] = $fieldObject->className();
        }
        return $classes;
    }

    /**
     * Checks if an input field with a specific name is present the current form (but not if it has a value)
     * @param string $fieldName
     * @return bool
     */
    public function formfieldExists(string $fieldName):bool
    {
        $fieldName = (trim($fieldName));
        return (in_array(strtolower($fieldName), array_map("strtolower", $this->getFormFieldClasses())));
    }

    /**
     * Output the value of multilang fields from the module configuration
     * @param string $fieldName
     * @return string
     */
    protected function getLangValueOfConfigField(
        string $fieldName,
        array $modulConfig = null,
        int|null $lang_id = null
    ):string {
        $modulConfig = (is_null($modulConfig)) ? $this->frontendforms : $modulConfig;
        $langAppendix = (is_null($lang_id)) ? $this->langAppendix : '__' . $lang_id;
        $fieldNameLang = $fieldName . $langAppendix;
        if (isset($modulConfig[$fieldNameLang])) {
            return $modulConfig[$fieldNameLang] != '' ? $modulConfig[$fieldNameLang] : $modulConfig[$fieldName];
        }
        return $modulConfig[$fieldName];
    }

    /**
     * Method to sanitize string, integer or boolean value to integer value 1 and 0
     * This is necessary, because configuration values of checkboxes are stored as integers in the db
     * @param string|int|bool $value
     * @return int
     */
    protected function sanitizeValueToInt(string|int|bool $value):int
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
     * If no path is selected, then the files will be stored inside the dir of this page in site/assets/files
     * @param string $path_to_folder
     * @return Form
     */
    public function setUploadPath(string $path_to_folder):self
    {
        $this->uploadPath = trim($path_to_folder);
        return $this;
    }

    /**
     * This method is only for testing of ip addresses that should be banned
     * Enter ip addresses as a numeric array
     * @param string $ip
     * @return void
     * @throws Exception
     */
    public function testIPBan(string $ip):void
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
    public function useIPBan(int|string|bool $enabled):void
    {
        $this->frontendforms['input_useIPBan'] = $this->sanitizeValueToInt($enabled);
    }

    /**
     * Disable/enable and set type of captcha on per form base
     * @param string $captchaType
     * @return void
     */
    protected function setCaptchaType(string $captchaType):void
    {
        $this->frontendforms['input_captchaType'] = $captchaType;
        if ($this->frontendforms['input_captchaType'] !== 'none') {
            $this->setCaptchaCategory($captchaType); //
            $this->captcha = AbstractCaptchaFactory::make($this->getCaptchaCategory(),
                $this->frontendforms['input_captchaType']);
        }
    }

    /**
     * Public method to disable the captcha on per form base if needed
     * @return void
     */
    public function disableCaptcha():void
    {
        $this->setCaptchaType('none');
    }

    /**
     * Get the captcha type set
     * @return string
     */
    protected function getCaptchaType():string
    {
        return $this->frontendforms['input_captchaType'];
    }

    /**
     * Set the captcha category (text, image) depending on the captcha type
     * @param string $captchaType
     * @return void
     */
    protected function setCaptchaCategory(string $captchaType):void
    {
        $this->captchaCategory = AbstractCaptchaFactory::getCaptchaTypeFromClass($captchaType);
    }

    /**
     * Get the captcha category
     * @return string
     */
    public function getCaptchaCategory():string
    {
        return $this->captchaCategory;
    }

    /**
     * Get the captcha object for further manipulations
     * @return object|null
     */
    protected function getCaptcha():object|null
    {
        return $this->captcha;
    }

    /**
     * Check if visitor is on the black list or not
     * @return bool - true if visitor is not on the black list
     */
    protected function allowFormViewByIP():bool
    {
        if (!$this->frontendforms['input_useIPBan']) {
            return true;
        }
        if ($this->frontendforms['input_preventIPs'] === '') {
            return true;
        }
        $ipaddresses = $this->newLineToArray($this->frontendforms['input_preventIPs']);
        return !in_array($this->visitorIP, $ipaddresses);
    }

    /**
     * Convert the values of a textbox to an array
     * Will be needed for list of banned IP in the module configuration
     * @param string|null $textarea - the value of the textarea field
     * @return array
     */
    protected function newLineToArray(string $textarea = null):array
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
    public function logFailedAttempts(string|bool|int $logFailedAttempts):void
    {
        $this->frontendforms['input_logFailedAttempts'] = $this->sanitizeValueToInt($logFailedAttempts);
    }

    /**
     * Convert post values to a string
     * @param bool $showButtonValues
     * @return string
     */
    public function getValuesAsString(bool $showButtonValues = false):string
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
    protected function getAllAvailableLanguages():array
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
     * Enable/disable the wrapping of checkboxes by its label
     * This is useful for some cases where you need to add the label after the input (fe. some CSS frameworks
     * @param bool $wrap
     * @return void
     */
    public function appendLabelOnCheckboxes(bool $wrap):void
    {
        $this->appendcheckbox = $wrap;
    }

    /**
     * Get the value of appendcheckbox
     * @return bool
     */
    protected function getAppendLabelOnCheckboxes():bool
    {
        return $this->appendcheckbox;
    }

    /**
     * Enable/disable the wrapping of radios by its label
     * This is useful for some cases where you need to add the label after the input (fe. some CSS frameworks
     * @param bool $wrap
     * @return void
     */
    public function appendLabelOnRadios(bool $wrap):void
    {
        $this->appendradio = $wrap;
    }

    /**
     * Get the value of appendradio
     * @return bool
     */
    protected function getAppendLabelOnRadios():bool
    {
        return $this->appendradio;
    }

    /**
     * Set your own text for required fields
     * @param string $requiredText
     * @return RequiredTextHint
     */
    public function setRequiredText(string $requiredText):RequiredTextHint
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
    public function getRequiredText():RequiredTextHint
    {
        return $this->requiredHint;
    }

    /**
     * This method creates an inner wrapper over all form elements if set to true, or it removes the wrapper if set to
     * false
     * So it adds a <div> tag after the opening form tag and a </div> tag before the closing form tag
     * @param bool $useFormElementsWrapper
     * @return Wrapper
     */
    public function useFormElementsWrapper(int|string|bool $useFormElementsWrapper):Wrapper
    {
        $useFormElementsWrapper = $this->sanitizeValueToInt($useFormElementsWrapper); // sanitize to int
        $this->frontendforms['input_wrapperFormElements'] = $useFormElementsWrapper;
        return $this->formElementsWrapper;
    }

    /**
     * Return the wrapper object
     * @return Wrapper
     */
    public function getFormElementsWrapper():Wrapper
    {
        return $this->formElementsWrapper;
    }

    /**
     * Set the success message for successful form submission
     * Can be used to overwrite the default success message
     * @param string $successMsg
     * @return void
     */
    public function setSuccessMsg(string $successMsg):void
    {
        if ($successMsg === '') {
            $successMsg = $this->_('Thank you for your message.');
        }
        $this->frontendforms['input_alertSuccessText'] = trim($successMsg);
    }

    /**
     * Set the error message if errors occur after form submission
     * Can be used to overwrite the default error message
     * @param string $errorMsg
     * @return void
     */
    public function setErrorMsg(string $errorMsg):void
    {
        if ($errorMsg === '') {
            $errorMsg = $this->_('Sorry, some errors occur. Please check your inputs once more.');
        }
        $this->frontendforms['input_alertErrorText'] = trim($errorMsg);
    }

    /**
     * Static method to check if SeoMaestro is installed or not
     * returns the SeoMaestro object on true, otherwise null
     * @return Field|null
     */
    public static function getSeoMaestro():?Field
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
     * You can enter pure name or name attribute including form prefix
     * @param string $name - the name attribute of the input field
     * @return string|array|null
     */
    public function getValue(string $name):string|array|null
    {
        $name = $this->createElementName(trim($name));
        if ($this->getValues()) {
            // first check if name exists
            if (isset($this->getValues()[$name])) {
                return $this->getValues()[$name];
            } else {
                if (isset($this->getValues()[$this->getID() . '-' . $name])) {
                    // check if name including form id prefix exists
                    return $this->getValues()[$this->getID() . '-' . $name];
                }
            }
            return null;
        }

        return null;
    }

    /**
     * Add the form id as prefix to the name attribute
     * @param string $name - the name attribute of the element
     * @return string - returns the name attribute including the form id as prefix
     */
    private function createElementName(string $name):string
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
    public function getValues(bool $buttonValue = false):array|null
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
                if (array_key_exists($key, $this->values)) {
                    $values[$key] = $this->values[$key];
                }
            }
        }
        return $values; // array
    }

    /**
     * Get all Elements (inputs, buttons,...) that are added to the form object
     * @return array - returns an array of all form element objects
     */
    public function getFormElements():array
    {
        return $this->formElements;
    }

    /**
     * Get a specific element of the form by entering the name of the element as parameter
     * With this method you can grab and manipulate a specific element
     * @param string $name - the name attribute of the element (fe email)
     * @param boolean $checkPrefix - true to check if form id is added for inputfield name or false to ignore this
     * @return object|bool - the form element object or false if not found
     */
    public function getFormelementByName(string $name, bool $checkPrefix = true):object|bool
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
    public function setRequiredTextPosition(string $position):void
    {
        $position = trim($position);
        $this->defaultRequiredTextPosition = in_array($position, ['none', 'top', 'bottom']) ? $position : 'top';
    }

    /**
     * Get the alert object for further manipulations
     * @return Alert
     */
    public function getAlert():Alert
    {
        return $this->alert;
    }

    /**
     * If you want to disable it, add this method to the form object - not recommended
     * @param int|string|bool $honeypot
     * @return void
     */
    public function useHoneypot(int|string|bool $honeypot):void
    {
        $this->frontendforms['input_useHoneypot'] = $this->sanitizeValueToInt($honeypot);
    }

    /**
     * Method to rearrange the multiple files array $_FILES
     * @param array $file_post
     * @return array
     */
    private function reArrayFiles(array $file_post):array
    {
        $file_ary = array();
        if ($file_post['error'] != 4) {
            $file_count = count($file_post['name']);
            $file_keys = array_keys($file_post);
            for ($i = 0; $i < $file_count; $i++) {
                foreach ($file_keys as $key) {
                    $file_ary[$i][$key] = $file_post[$key][$i];
                }
            }
        }
        return $file_ary;
    }

    /**
     * Internal method to store uploaded files via InputFile field in the chosen folder
     * @param array $formElements
     * @return array
     * @throws WireException
     */
    private function storeUploadedFiles(array $formElements):array
    {
        $uploaded_files = [];
        if ($_FILES) {
            // create directory if it does not exist
            $this->wire('files')->mkdir($this->uploadPath);
            // get all upload fields inside the form
            foreach ($formElements as $element) {

                if (($element instanceof InputFile) || (is_subclass_of($element, 'InputFile'))) {
                    $fieldName = $element->getAttribute('name'); // the name of the upload field

                    if ($element->getMultiple()) {
                        // multiple files
                        $files = $this->reArrayFiles($_FILES[$fieldName]);
                        foreach ($files as $file) {
                            if ($file['error'] == 0) {
                                $target_file = $this->uploadPath . basename($file['name']);
                                $uploaded_files[] = $target_file;
                                move_uploaded_file($file['tmp_name'], $target_file);
                            }
                        }
                    } else {
                        // single file
                        $file = $_FILES[$fieldName];
                        if ($file['error'] == 0) {
                            $target_file = $this->uploadPath . basename($file['name']);
                            $uploaded_files[] = $target_file;
                            move_uploaded_file($file['tmp_name'], $target_file);
                        }
                    }
                }
            }
        }
        return $uploaded_files;
    }

    /**
     * Convert the complicated $_FILES array to a simpler one
     * @param array $files
     * @return array
     */
    protected function simplifyMultiFileArray(array $files = []):array
    {
        $sFiles = [];
        if (is_array($files) && $files['error'] != '4') {
            foreach ($files as $key => $file) {
                foreach ($file as $index => $attr) {
                    $sFiles[$index][$key] = $attr;
                }
            }
        }
        return $sFiles;
    }

    /**
     * Internal method to put required rule always on the first place of validation
     * Checking if value is present is always logical the first step before checking for other things
     * @param array $rules
     * @return array
     */
    protected function putRequiredOnTop(array $rules):array
    {
        if (count($rules) > 1) {
            if (array_key_exists('required', $rules)) {
                $rules = ['required' => $rules['required']] + $rules;
            }
        }
        return $rules;
    }

    /**
     * Process the form after form submission
     * Includes sanitization and validation
     * @return bool - true: form is valid, false: form has errors
     * @throws WireException
     * @throws Exception
     */
    public function ___isValid():bool
    {
        // set WireInput array depth to 2 because auf multiple file uploads
        $this->wire('config')->wireInputArrayDepth = 2;
        $formMethod = $this->getAttribute('method'); // grab the method (get or post)
        $input = $this->wire('input')->$formMethod; // get the GET or POST values after submission
        $formElements = $this->formElements; //grab all form elements as an array of objects

        // check for file upload fields inside the form
        $file_upload_fields = $this->getFileUploadFields();
        if ($file_upload_fields) {
            foreach ($file_upload_fields as $field) {
                $name = $field->getAttribute('name');
                if (!empty($_FILES)) {
                    if ($field->hasAttribute('multiple')) {
                        // convert $_FILES array to a simpler one
                        $input[$name] = $this->simplifyMultiFileArray($_FILES[$name]);
                    } else {
                        $input[$name] = $_FILES[$name];
                    }
                }
            }
        }

        // instantiates the FormValidation object
        $validation = new FormValidation($input, $this, $this->alert);

        // 1) check if this form was submitted and no other form on the same page
        if ($validation->thisFormSubmitted()) {
            // 2) check if form was submitted in time range
            if ($validation->checkTimeDiff($formElements)) {
                // 3) check if max attempts were reached
                if ($validation->checkMaxAttempts($this->wire('session')->attempts)) {
                    // 4) check for double form submission
                    if ($validation->checkDoubleFormSubmission($this, $this->useDoubleFormSubmissionCheck)) {
                        // 5) Check for CSRF attack
                        if ($this->wire('session')->CSRF->hasValidToken()) {

                            /* START PROCESSING THE FORM */

                            //add honeypotfield to the array because it will be rendered afterwards
                            if ($this->frontendforms['input_useHoneypot']) {
                                $formElements[] = $this->createHoneypot();
                            }
                            //add captcha to the array because it will be rendered afterwards

                            if ($this->getCaptchaType() !== 'none') {
                                $formElements[] = $this->getCaptcha()->createCaptchaInputField($this->getID());
                            }

                            // Get only input field for user inputs (no fieldsets, buttons,..)
                            $formElements = $validation->getRealInputFields($formElements);

                            // Run sanitizer on all POST values first
                            $sanitizedValues = [];

                            foreach ($formElements as $element) {
                                // remove all form elements which have the disabled attribute, because they do not send values
                                if (!$element->hasAttribute('disabled')) {
                                    if ($element instanceof InputFile) {
                                        $file_upload_name = $element->getAttribute('name');
                                        if ($element->getMultiple()) {
                                            $sanitizedValues[$file_upload_name] = $this->reArrayFiles($_FILES[$file_upload_name]);
                                        } else {
                                            $sanitizedValues[$file_upload_name] = [$_FILES[$file_upload_name]];
                                        }
                                    } else {
                                        $sanitizedValues[$element->getAttribute('name')] = $validation->sanitizePostValue($element);
                                    }
                                    //$sanitizedValues[$element->getAttribute('name')] = $validation->sanitizePostValue($element);
                                } else {
                                    // remove all validation rules from this element
                                    $element->removeAllRules();
                                }
                            }

                            $v = new Validator($sanitizedValues);

                            foreach ($formElements as $element) {
                                // run validation only if there is at least one validation rule set
                                if (count($element->getRules()) > 0) {
                                    // add required validation to be the first
                                    $rules = $this->putRequiredOnTop($element->getRules());
                                    foreach ($rules as $validatorName => $parameters) {
                                        $v->rule($validatorName, $element->getAttribute('name'), ...
                                            $parameters['options']);
                                        // Add custom error message text if present
                                        if (isset($parameters['customMsg'])) {
                                            $v->message($parameters['customMsg']);
                                        }
                                        if (isset($parameters['customFieldName'])) {
                                            $v->label($parameters['customFieldName']);
                                        } else {
                                            if ($element->getLabel()->getText()) {
                                                // use the label if present, otherwise use the name attribute
                                                $v->label($element->getLabel()->getText());
                                            }
                                        }
                                    }
                                }

                                // add honeypot validation if honeypot field is included
                                if ($this->frontendforms['input_useHoneypot']) {
                                    if ($element->getAttribute('name') == $this->createElementName('seca')) {
                                        $v->rule('length', $element->getAttribute('name'),
                                            0)->message($this->_('Please do not fill out this field'));
                                    }
                                }
                                // add captcha validation if captcha field is included

                                if ($this->getCaptchaType() !== 'none') {

                                    if ($element->getAttribute('name') == $this->createElementName('captcha')) {
                                        $v->rule('required',
                                            $element->getAttribute('name'))->label($this->_('The captcha')); // captcha is always required
                                        $v->rule('checkCaptcha', $element->getAttribute('name'),
                                            $this->wire('session')->get('captcha_' . $this->getID()))->label($this->_('The value entered from the captcha'));
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
                                // check if files were uploaded and store them inside the chosen folder
                                $this->uploaded_files = $this->storeUploadedFiles($formElements);
                                // remove session added by matchUser or matchEmail validation rule if present
                                $this->wire('session')->remove($this->getAttribute('id') . '-email');
                                $this->wire('session')->remove($this->getAttribute('id') . '-username');
                                return true;
                            } else {
                                // set error alert
                                $this->formErrors = $v->errors();
                                $this->alert->setCSSClass('alert_dangerClass');
                                $this->alert->setText($this->getErrorMsg());

                                // add max attempts warning message to error message
                                if ($this->getMaxAttempts() && isset($this->wire('session')->attempts)) {

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
                                    $this->wire('session')->attempts += 1; // increase session on each invalid attempt

                                    if (($this->getMaxAttempts() - $this->wire('session')->attempts) == 0) {
                                        $this->alert->setCSSClass('alert_warningClass');
                                        $this->alert->setText(sprintf($this->_('This is failed attempt number %s. This is your last attempt to send the form. After that you will be blocked due to security reasons.'),
                                            ($this->wire('session')->attempts)));
                                    }
                                } else {
                                    // remove the session for attempts if set to 0
                                    $this->wire('session')->remove('attempts');
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
    private function createHoneypot():InputText
    {
        $honeypot = new InputText('seca');
        $honeypot->setAttribute('name', $this->createElementName('seca'));
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
    public function useInputWrapper(bool $useInputWrapper):void
    {
        $this->useInputWrapper = $useInputWrapper;
    }

    /**
     * Add the field wrapper to all fields of this form in general
     * @param bool $useFieldWrapper
     * @return void
     */
    public function useFieldWrapper(bool $useFieldWrapper):void
    {
        $this->useFieldWrapper = $useFieldWrapper;
    }

    /**
     * Internal method to add all form values to the values array
     * @return void
     */
    private function setValues():void
    {
        $values = [];
        foreach ($this->formElements as $element) {
            if ($element->getAttribute('value')) {
                $values[$element->getAttribute('name')] = $element->getAttribute('value');
                // set all form values to a placeholder
                $fieldName = str_replace($this->getID() . '-', '', $element->getAttribute('name')) . 'value';

                $this->setMailPlaceholder($fieldName, $element->getAttribute('value'));
            }
        }
        $this->values = $values;
    }

    /**
     * Get the success message
     * @return string
     */
    protected function getSuccessMsg():string
    {
        return $this->frontendforms['input_alertSuccessText'];
    }

    /**
     * Get the error message
     * @return string
     */
    protected function getErrorMsg():string
    {
        return $this->frontendforms['input_alertErrorText'];
    }

    /**
     * Get the max attempts
     * @return int
     */
    public function getMaxAttempts():int
    {
        return $this->frontendforms['input_maxAttempts'];
    }


    /**
     * Set the max attempts
     * @param int $maxAttempts
     * @return void
     */
    public function setMaxAttempts(int $maxAttempts):void
    {
        if ($maxAttempts < 1) {
            $this->frontendforms['input_logFailedLogins'] = 0;
        } //disable logging of failed attempts
        $this->frontendforms['input_maxAttempts'] = $maxAttempts;
    }

    /**
     * Method to run if a user has taken too much attempts
     * This method has to be before the render method of the form
     * You can use it fe to save some data to the database -> you got the idea
     * @return bool -> returns true if the user is blocked, otherwise false
     * @throws WireException
     */
    public function isBlocked():bool
    {
        if ($this->wire('session')->get('blocked')) {
            return true;
        }
        return false;
    }

    /**
     * Render the form markup (including alerts if present) on the frontend
     * @return string
     * @throws WireException
     */
    public function render():string
    {

        /* Check if form contains file upload fields, then add enctype attribute */
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
            // do not display form to banned visitors
            $this->showForm = false;
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

        // get keys of all input fields (excluding buttons, fieldsets,.. only input fields that collect user data)
        $inputfieldKeys = [];
        foreach ($this->formElements as $key => $inputfield) {
            if (is_subclass_of($inputfield, 'FrontendForms\Inputfields')) {
                $inputfieldKeys[] = $key;
            }
        }

        // Add honeypot field only if at least 1 input field is present
        if (count($inputfieldKeys)) {
            // add captcha field as last input field
            if ($this->getCaptchaType() != 'none') {
                // position in form fields array to insert
                $captchaPosition = end($inputfieldKeys) + 1;
                $captchafield = $this->getCaptcha()->createCaptchaInputField($this->getID());
                // insert the captcha input field after the last input field
                $this->formElements = array_merge(array_slice($this->formElements, 0, $captchaPosition),
                    array($captchafield), array_slice($this->formElements, $captchaPosition));
            }

            // add honeypot on the random number field position
            if ($this->frontendforms['input_useHoneypot']) {
                shuffle($inputfieldKeys);
                array_splice($this->formElements, $inputfieldKeys[0], 0, [$this->createHoneypot()]);
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
            $hiddenField4->setAttribute('value', self::encryptDecrypt((string)time()));
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

                // Label (Only on input fields)
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                    // add unique id to the field-wrapper if present
                    $element->getFieldWrapper()->setAttribute('id', $this->getID() . '-' . $oldId . '-fieldwrapper');
                    // add unique id to the input-wrapper if present
                    $element->getInputWrapper()->setAttribute('id', $this->getID() . '-' . $oldId . '-inputwrapper');
                    $element->getLabel()->setAttribute('for', $element->getAttribute('id'));
                }
                $name = $element->getAttribute('id');

                //Enable/disable wrap of the checkboxes by its label tag by appending the label after the input tag
                // by using the appendLabel() method
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
            if ($this->frontendforms['input_wrapperFormElements']) {
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

    /**
     * Append a field object to the form
     * The 2 optional parameters are only for the creation of 2 new methods: addBefore() and addAfter()
     * These 2 methods can be used to add new form elements (inputs, text elements, fieldsets,..) to a formElements array at a certain position
     * These 2 methods are especially designed for the future usage in module dev - no need to use it if you are creting the form by your own
     * @param object $field - the current form field which should be appended to the form
     * @param object|null $otherfield - optional: another form field
     * @param bool $add_before - optional: current should be inserted before or after this (another) form field
     * @return void
     */
    public function add(
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field,
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose|null|bool $otherfield = null,
        bool $add_before = false
    ):void {

        // add or remove wrapper divs on each form element
        if (is_subclass_of($field, 'FrontendForms\Inputfields')) {
            $field->useInputWrapper($this->useInputWrapper);
            $field->useFieldWrapper($this->useFieldWrapper);
            // create a placeholder for the label of this field
            $fieldname = $field->getAttribute('name');
            $this->setMailPlaceholder($fieldname . 'label', $field->getLabel()->getText());
            $this->setMailPlaceholder($fieldname . 'value', $field->getAttribute('value'));
        }
        // if field is not a text element, set the name attribute
        if (!is_subclass_of($field, 'FrontendForms\TextElements')) {
            // Add id of the form as prefix for the name attribute of the field
            $field->setAttribute('name', $this->getID() . '-' . $field->getId());
        }

        if (!is_null($otherfield)) {
            // check if other field exist
            if (is_bool($otherfield)) {
                throw new Exception("The reference field (argument 2) where you want to add this field before or after does not exist. Please check if you have written the name attribute correctly.",
                    1);
            } else {
                // check if the field with this id exists inside the formElements array
                if ($this->getFormelementByName($otherfield->getAttribute('name'))) {
                    $ref_position = null;
                    // get the key of this field inside the formElements array
                    $this->formElements = array_values($this->formElements);
                    foreach ($this->formElements as $key => $element) {
                        if ($element == $otherfield) {
                            $ref_position = $key;
                        }
                    }

                    // insert field to the new position
                    if (is_int($ref_position)) {
                        if (!$add_before) { // add after
                            $ref_position = $ref_position + 1;
                        }
                        $this->formElements = array_merge(array_slice($this->formElements, 0, $ref_position), [$field],
                            array_slice($this->formElements, $ref_position));
                    }
                }
            }
        } else {
            // no other element is present -> so add it to formElements array as next element
            $this->formElements = array_merge($this->formElements,
                [$field]); // array must be numeric for honeypot field
        }

    }

    /**
     * Insert a form field before another form field
     * Can be used if you have not created the form by your own, but you need to add a new field to a created formElements
     * array at a certain position
     * @param object $field - the current form field
     * @param object $before_field - the form field object before which the current form field object should be inserted
     * @return void
     */
    public function addBefore(
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field,
        Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $before_field
    ):void {
        // if field is present inside the formelements array, remove it first
        if (($field->getAttribute('name')) && ($this->getFormelementByName($field->getAttribute('name')))) {
            $this->remove($field);
        }
        $this->add($field, $before_field, true);
    }

    /**
     * Insert a form field after another form field
     * Can be used if you have not created the form by your own, but you need to add a new field to a created formElements
     * array at a certain position                                                                                        * @param object $field - the current form field
     * @param object $after_field - the form field object after which the current form field object should be inserted
     * @return void
     */
    public function addAfter(
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field,
        Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $after_field
    ):void {
        // if field is present inside the formelements array, remove it first
        if (($field->getAttribute('name')) && ($this->getFormelementByName($field->getAttribute('name')))) {
            $this->remove($field);
        }
        $this->add($field, $after_field);
    }

    /**
     * Remove a form field from the fields array
     * @param object $field
     * @return void
     */
    public function remove(object $field):void
    {
        if (($key = array_search($field, $this->formElements)) !== false) {
            unset($this->formElements[$key]);
            // remove the placeholders too, if they are present
            $fieldname = $field->getAttribute('name');
            $this->removePlaceholder(strtoupper($fieldname . 'label'));
            $this->removePlaceholder(strtoupper($fieldname . 'value'));
        }
    }

    /**
     * Get the min time value
     * @return int
     */
    public function getMinTime():int
    {
        return $this->frontendforms['input_minTime'];
    }

    /**
     * Set the min time in seconds before the form should be submitted
     * @param int $minTime
     * @return $this
     */
    public function setMinTime(int $minTime):self
    {
        $this->frontendforms['input_minTime'] = $minTime;
        return $this;
    }

    /**
     * Get the max time value
     * @return int
     */
    protected function getMaxTime():int
    {
        return $this->frontendforms['input_maxTime'];
    }

    /**
     * Set the max time in seconds until the form should be submitted
     * @param int $maxTime
     * @return $this
     */
    public function setMaxTime(int $maxTime):self
    {
        $this->frontendforms['input_maxTime'] = $maxTime;
        return $this;
    }

    /** Static method to encrypt/decrypt a string according to the encryption settings
     * @param string $string
     * @param string $method
     * @return string
     */
    public static function encryptDecrypt(string $string, string $method = 'encrypt'):string
    {
        // encryption settings
        $encrypt_method = 'AES-256-CBC';
        $secret_key = 'd0a7e7997b6d5fcd55f4b5c32611b87cd923e88837b63bf2941ef819dc8ca282';
        $secret_iv = '5fgf5HJ5g27';
        $algo = 'sha256';
        // user define secret key
        $key = hash($algo, $secret_key);
        $iv = substr(hash($algo, $secret_iv), 0, 16);
        $methods = ['encrypt', 'decrypt'];
        if (in_array($method, $methods)) {
            if ($method === 'encrypt') {
                $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
                return base64_encode($output);
            } else {
                return openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }
        }
        return $string;
    }

    /**
     * Create required hint text element if showTextHint is set to true
     * @param string $position - has to be 'top' or 'bottom'
     * @return string
     */
    private function renderRequiredText(string $position):string
    {
        if ($this->defaultRequiredTextPosition === $position) {
            return $this->requiredHint->___render();
        }
        return ''; // return empty string
    }

    /**
     * Create a random string with a certain length for usage in URL query strings
     * @param int $charLength - the length of the random string - default is 100
     * @return string - returns a slug version of the generated random string that can be used inside an url
     */
    protected function createQueryCode(int $charLength = 100):string
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
     * Generate a slug out of a string for usage in urls (fe query strings)
     * This is only a helper function
     * @param $string - the string
     * @return string
     */
    protected function generateSlug(string $string):string
    {
        return preg_replace('/[^A-Za-z\d-]+/', '-', $string);
    }

    /**
     * Make a readable string from a number of seconds
     * @param int $seconds - a number of seconds which should be converted to a readable string
     * @return string|null - a readable string of the time (fe 1 day instead of 86400 seconds)
     * @throws Exception
     */
    protected function readableTimestringFromSeconds(int $seconds = 0):?string
    {
        $then = new DateTime(date('Y-m-d H:i:s', 0));
        $now = new DateTime(date('Y-m-d H:i:s', $seconds));
        $interval = $then->diff($now);

        if ($interval->y >= 1) {
            $thetime[] = $interval->y . ' ' . _n($this->_('year'),
                    $this->_('years'), $interval->y);
        }
        if ($interval->m >= 1) {
            $thetime[] = $interval->m . ' ' . _n($this->_('month'),
                    $this->_('months'), $interval->m);
        }
        if ($interval->d >= 1) {
            $thetime[] = $interval->d . ' ' . _n($this->_('day'),
                    $this->_('days'), $interval->d);
        }
        if ($interval->h >= 1) {
            $thetime[] = $interval->h . ' ' . _n($this->_('hour'),
                    $this->_('hours'), $interval->h);
        }
        if ($interval->i >= 1) {
            $thetime[] = $interval->i . ' ' . _n($this->_('minute'),
                    $this->_('minutes'), $interval->i);
        }
        if ($interval->s >= 1) {
            $thetime[] = $interval->s . ' ' . _n($this->_('second'),
                    $this->_('seconds'), $interval->s);
        }

        return isset($thetime) ? implode(' ', $thetime) : null;
    }

    /**
     * Return the names of all input fields inside a form as an array
     * @return array
     */
    public function getNamesOfInputFields():array
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
    protected function generateEmailSentErrorAlert():void
    {
        $this->alert->setCSSClass('alert_dangerClass');
        $this->alert->setText($this->_('Email could not be sent due to possible wrong email configuration settings.'));
    }

    /**
     * Return placeholders for email pre-header to prevent showing up other text
     * The Litmus hack adds empty spaces after the mail placeholder to prevent the display of other text inside the pre-header
     * @return string
     */
    protected function getLitmusHack(): string
    {
        return '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279;';
    }

    protected function getPreheaderStyle(): string
    {
        return 'display:none;font-size:1px; color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;';
    }

    /**
     * Generate an invisible pre-header text after the subject for an email
     * @param string|null $preheader
     * @return string|null
     */
    protected function generateEmailPreHeader(WireMail $mail): string
    {
        if($mail->title){ // check if title property was set
            // generate an invisible div container
            return '<div id="preheader-text" style="'.$this->getPreheaderStyle().'">'.$mail->title.$this->getLitmusHack().'</div>';
        }
        return '';
    }

}
