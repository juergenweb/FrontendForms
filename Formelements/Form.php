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
    use DOMDocument;
    use DOMException;
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
    use Valitron\Validator;

    use function ProcessWire\wire as wire;
    use function ProcessWire\wirePopulateStringTags;
    use function ProcessWire\_n;
    use function ProcessWire\wireClassNamespace;
    use function ProcessWire\wireMail;
    use function ProcessWire\wireClassName;

    class Form extends CustomRules
    {

        /* constants */
        const FORMMETHODS = ['get', 'post']; // array that holds allowed action methods (get, post)

        /* properties */

        protected string|int $load_time = ''; // the time, when the form was loaded
        protected array $storedFiles = []; // array that holds all files (including overwritten filenames)
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
        protected string|int|bool $useCSRFProtection = 1; // Enable/disable CSRF-Protection
        protected string $general_desc_position = 'afterInput'; // The position of the input field description -> beforeLabel, afterLabel or afterInput
        protected string $captcha_value = '';

        // properties for the simple question Captcha
        protected string|null $question = ''; // the question as string
        protected array|null $answers = []; // all acceptable answers as an array
        protected array|null $captchaPosition = null; // array that holds the reference field as the key and the position as value
        protected string|null $captchaSuccessMsg = ''; // set a success message for the captcha field
        protected string|null $captchaErrorMsg = ''; // overwrite the default error message for the CAPTCHA validation
        protected string|null $captchaRequiredErrorMsg = ''; // overwrite the default error message for the CAPTCHA required validation
        protected string|null $captchaNotes = ''; // notes text for the Captcha
        protected string|null $captchaDescription = ''; // description text for the Captcha
        protected string|null $captchaDescriptionPosition = ''; // description position for the Captcha
        protected string|null $captchaPlaceholder = ''; // set a placeholder for the Captcha input
        protected int|null $random_question = null;
        protected array|null $random_question_array = null;
        protected array|null $question_array = [];
        protected bool $removeCaptchaLabel = false;
        protected bool $useCaptchaLabelAsPlaceholder = false;
        protected bool $showValueOnSameQuestionAgain = false;
        protected InputText|InputRadioMultiple|InputCheckbox|null $captchafield;
        protected string|int|bool $useAriaAttributes = true; // use accessibility attributes
        // Mail properties - only needed if FrontendForms will be used to send emails
        protected array $mailPlaceholder = []; // associative array for usage in emails (['placeholdername' => 'text',...])
        protected string $defaultDateFormat = 'Y-m-d'; // the default format for date strings
        protected string $defaultTimeFormat = 'H:i a'; // the default format for time strings
        protected string $receiverAddress = ''; // the email address of the receiver of the mails
        protected string $mail_subject = ''; // the subject for a mail sent after form validation
        protected string $emailTemplatesDirPath = ''; // the path to the email templates directory
        protected string $emailCustomTemplatesDirPath = ''; // the path to the email custom templates directory
        protected string $emailTemplate = ''; // the filename of the email template including the extension (fe. template.html)
        protected string $emailTemplatePath = ''; // the path to the body template
        protected string $emailCustomTemplatePath = ''; // the path to the custom body template
        protected array $uploaded_files = []; // array which holds all currently uploaded files with the path as value
        protected int|null $mail_language_id = null; // property for setting the language for mail templates manually
        protected int|null $site_language_id = null; // internal property containing the current site language
        protected string|int|null|bool $submitAjax = 0; // whether to submit the form via Ajax (1) or not (0)
        protected string|null $ajaxRedirect = null; // redirect to this URL after a valid form has been submitted - only for Ajax submission
        protected string $redirectURL = ''; // the URL for the redirect after successful for form validation
        protected string $validated = '0'; // the form is validated (1) or not (0)
        protected string|null|int|bool $showProgressbar = true;

        protected string $labeltag = 'label'; // set the default global tag for the label element
        protected string $desctag = 'p'; // set the default global tag for the description element
        protected string $notestag = 'p'; // set the default global tag for the notes element
        protected string $msgtag = 'p'; // set the default global tag for the message elements (success and error message)
        protected string|null $segments = null;
        protected string|null|bool|int $stopHoneypotRotation = false; // Honeypotfield will be positioned randomly (false) or stays at the top of the form (true)

        /* objects */
        protected Page $page; // the current page object, where the form is integrated
        protected Alert $alert; // alert box
        protected RequiredTextHint $requiredHint; // hint to inform that all required fields have to be filled out
        protected Wrapper $formElementsWrapper; // the wrapper object over all form elements
        protected User $user; // the user, who views the form (the page)
        protected Language $userLang; // the language object of the user/visitor

        protected object $captcha; // the captcha object

        /**
         * Every form must have an id. You can set it custom via the constructor - otherwise a random ID will be
         * generated. The id will be taken for further automatic id generation of the input fields
         * @throws WireException
         */
        public function __construct(string $id)
        {
            parent::__construct();

            $this->load_time = time(); // set the load time

            // set the path to the template folder for the email templates
            $this->emailTemplatesDirPath = $this->wire('config')->paths->siteModules . 'FrontendForms/email_templates/';
            // set the path to the custom template folder for the email templates
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

            // set Ajax form submission according to the configuration settings
            if (array_key_exists('input_ajaxformsubmission', $this->frontendforms)) {
                $this->setSubmitWithAjax((int)$this->frontendforms['input_ajaxformsubmission']);
            }

            // set show/hide progressbar during Ajax form submission according to the configuration settings
            if (array_key_exists('input_hideProgressBar', $this->frontendforms)) {
                $this->showProgressbar = !(($this->frontendforms['input_hideProgressBar'] == '1'));
            }
            $this->showProgressbar($this->showProgressbar);

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
            $this->showForm = $this->allowFormViewByIP(); // show or hide the form depending on the IP ban
            $this->setAttribute('method', 'post'); // default is post
            // take care about url segments if enabled
            $this->segments = ($this->wire('input')->urlSegmentStr()) ?? '';
            $this->setAttribute('action', $this->page->url . $this->segments); // stay on the same page - needs to run after the API is ready
            $this->setAttribute('id', $id); // set the id
            $this->setAttribute('name', $this->getID() . '-' . time());
            $this->setHtml5Validation($this->frontendforms['input_html5_validation']);
            $this->setAttribute('autocomplete', 'off'); // set autocomplete off by default
            $this->setTag('form'); // set the form tag
            $this->setCSSClass('formClass'); // add the CSS class
            $this->removeCSSClass('formClassValidated'); // remove the "is validated" form class by default
            $this->setSuccessMsg($this->getLangValueOfConfigField('input_alertSuccessText'));
            $this->setErrorMsg($this->getLangValueOfConfigField('input_alertErrorText'));
            $this->setRequiredTextPosition($this->frontendforms['input_requiredHintPosition']); // set the position for the required text
            $this->getFormElementsWrapper()->setAttribute('id',
                $this->getAttribute('id') . '-formelementswrapper'); // add id
            $this->getFormElementsWrapper()->setAttribute('class',
                $this->frontendforms['input_wrapperFormElementsCSSClass']); // add CSS class to the wrapper element
            $this->useDoubleFormSubmissionCheck($this->useDoubleFormSubmissionCheck);
            $this->setRequiredText($this->getLangValueOfConfigField('input_requiredText'));
            $this->logFailedAttempts($this->frontendforms['input_logFailedAttempts']); // enable or disable the logging of blocked visitor's IP depending on config settings
            $this->setMaxAttempts($this->frontendforms['input_maxAttempts']); // set max attempts
            $this->setMinTime($this->frontendforms['input_minTime']); // set min time
            $this->setMaxTime($this->frontendforms['input_maxTime']); // set max time
            $this->setCaptchaType($this->frontendforms['input_captchaType']); // enable or disable the captcha and set type of captcha
            // set the folder of the page in assets/files as default target folder for file uploads
            $this->setUploadPath($this->wire('config')->paths->assets . 'files/' . $this->page->id . '/');

            $ajaxMsg = '';
            if (array_key_exists('input_ajaxMsg', $this->frontendforms)) {
                $ajaxMsg = $this->getLangValueOfConfigField('input_ajaxMsg');
            }
            $this->setAjaxMessage($ajaxMsg);

            // Global tags for label, description,...

            // 1) Label
            $labelTag = (!empty($this->frontendforms['input_global_label_tag'])) ? $this->frontendforms['input_global_label_tag'] : 'label';
            $this->setLabelTag($labelTag);

            // 2) Description
            $descTag = (!empty($this->frontendforms['input_global_desc_tag'])) ? $this->frontendforms['input_global_desc_tag'] : 'p';
            $this->setDescriptionTag($descTag);

            // 3) Notes
            $defaultNotesTag = ($this->frontendforms['input_framework'] === 'pico2.json') ? 'small' : 'p';
            $notesTag = (!empty($this->frontendforms['input_global_notes_tag'])) ? $this->frontendforms['input_global_notes_tag'] : $defaultNotesTag;
            $this->setNotesTag($notesTag);

            // 4) Messages
            $defaultMsgTag = 'p';
            if ($this->frontendforms['input_framework'] === 'bootstrap5.json')
                $defaultMsgTag = 'div';
            if ($this->frontendforms['input_framework'] === 'pico2.json')
                $defaultMsgTag = 'small';
            $messagesTag = (!empty($this->frontendforms['input_global_msg_tag'])) ? $this->frontendforms['input_global_msg_tag'] : $defaultMsgTag;
            $this->setMessageTag($messagesTag);

            // Global text for auto-generated emails
            $this->doNotReply = $this->_('This email was generated automatically. So please do not reply to this email.');

            // create and set all general placeholder variables
            $this->createGeneralPlaceholders();

            // set global description position according to the module configuration

            if (array_key_exists('input_descPosition', $this->frontendforms)) {
                $this->general_desc_position = $this->frontendforms['input_descPosition'];
                // set the global description position for the CAPTCHA description position as default value
                $this->captchaDescriptionPosition = $this->general_desc_position;
            }

            // add a hook method to render mail templates before sending the mail
            $this->addHookBefore('WireMail::send', $this, 'renderTemplate');
            // add a hook method after sending the mail to remove the session variable "templateloaded"
            $this->addHookAfter('WireMail::send', $this, 'removeTemplateSession');

            // create the questions array for the simple text CAPTCHA
            $this->question_array = $this->getCaptchaQuestions();


            // set default values for loading JS from the module config
            $useJS = $useCSS = '1';
            if (isset($this->frontendforms['input_removeJS']) && ($this->frontendforms['input_removeJS'] != '')) {
                $useJS = '0';
            }
            if (isset($this->frontendforms['input_removeCSS']) && ($this->frontendforms['input_removeCSS'] != '')) {
                $useCSS = '0';
            }

            // check if property useJS exists
            if (isset($this->page->useJS)) {
                $jsArray = $this->page->useJS;
                $jsArray[$this->getID()] = $useJS;
                $this->page->useJS = $jsArray;
            } else {
                $this->page->useJS = [$this->getID() => $useJS];
            }

            // check if property useJS exists
            if (isset($this->page->useCSS)) {
                $cssArray = $this->page->useCSS;
                $cssArray[$this->getID()] = $useCSS;
                $this->page->useCSS = $cssArray;
            } else {
                $this->page->useCSS = [$this->getID() => $useCSS];
            }

            // set default value for field conditions to false if it was not set before
            if (!isset($this->page->field_conditions))
                $this->page->field_conditions = false;

            // add this form to the property ff_forms of the page array, which contains the id of all forms of this page
            if (isset($this->page->ff_forms)) {
                $forms = $this->page->ff_forms;
                $forms[] = $this->getID();
                $this->page->ff_forms = $forms;
            } else {
                $this->page->ff_forms = [$this->getID()];
            }

        }

        /**
         * Remove all the FrontendForms JS files on per form base
         * @return $this
         */
        public function useJS(bool $use = true): self
        {
            $value = $use ? '1' : '0';
            $removeJS = $this->page->useJS;
            $removeJS[$this->getID()] = $value;
            $this->page->useJS = $removeJS;
            return $this;
        }

        /**
         * Remove all the FrontendForms CSS files on per form base
         * @return $this
         */
        public function useCSS(bool $use = true): self
        {
            $value = $use ? '1' : '0';
            $removeCSS = $this->page->useCSS;
            $removeCSS[$this->getID()] = $value;
            $this->page->useCSS = $removeCSS;
            return $this;
        }

        /**
         * Set a custom info message, that will be displayed during an AJAX call to inform the user
         * @param string $ajaxmsg
         * @return $this
         */
        public function setAjaxMessage(string $ajaxmsg): void
        {
            if ($ajaxmsg === '') {
                $ajaxmsg = $this->_('Please be patient... the form will be validated!');
            }
            $this->frontendforms['input_ajaxMsg'] = trim($ajaxmsg);
        }

        /**
         * Change the label tag for all elements inside the form
         * @param string $labeltag
         * @return void
         */
        public function setLabelTag(string $labeltag): void
        {
            $this->labeltag = $labeltag;
        }

        /**
         * Change the description tag for all elements inside the form
         * @param string $desctag
         * @return void
         */
        public function setDescriptionTag(string $desctag): void
        {
            $this->desctag = $desctag;
        }

        /**
         * Change the notes tag for all elements inside the form
         * @param string $notestag
         * @return void
         */
        public function setNotesTag(string $notestag): void
        {
            $this->notestag = $notestag;
        }

        /**
         * Change the message tag for all elements inside the form
         * @param string $msgtag
         * @return void
         */
        public function setMessageTag(string $msgtag): void
        {
            $this->msgtag = $msgtag;
        }


        /**
         *  Add a single question to the simple question captcha on per form base
         * @param string|null $question
         * @param array|null $answers
         * @param array $options - add all other question parameters like notes, description, placeholder to the
         * question
         * @return $this
         */
        public function setSecurityQuestion(string|null $question, array|null $answers, array $options = []): self
        {

            // add question sign if it is not present in the question
            if (!is_null($question)) {
                if (substr($question, -1) != '?') {
                    $question = $question . '?';
                }
            }

            // add this question to the question array
            $array = [
                'question' => $question,
                'answers' => $answers
            ];
            if ($options) {
                foreach ($options as $k => $v) {
                    $array[$k] = $v;
                }
            }
            $this->question_array[] = array_filter($array);
            return $this;
        }

        /**
         * Add multiple questions as a multidimensional assoc array for the Simple question CAPTCHA
         * @param array $questions
         * @return $this
         */
        public function setSecurityQuestions(array $questions): self
        {
            foreach ($questions as $question) {
                $q = $question['question'];
                $a = $question['answers'];
                $options = [];
                // check if options are set
                unset($question['question']);
                unset($question['answers']);
                if ($question) {
                    $options = $question;
                }
                $this->setSecurityQuestion($q, $a, $options);

            }
            return $this;
        }

        /**
         * Method to change the position of the CAPTCHA inside the form
         * @param string $ref_field_name -> the name attribute of the reference field
         * @param string $pos -> the position relative to the reference field: could be before or after
         * @return $this
         */
        public function setCaptchaPosition(string $ref_field_name, string $pos = 'after'): self
        {
            $pos = (in_array($pos, ['before', 'after'])) ? $pos : 'after'; // only before and after is allowed
            $this->captchaPosition = [$ref_field_name => $pos];
            return $this;
        }

        /**
         * Get the CAPTCHA position if a valid reference field was set
         * @return array|null
         */
        protected function getCaptchaPosition(): array|null
        {
            if ($this->captchaPosition) {
                // check if a field with the given name exists inside the form object, otherwise return null
                if (!$this->getFormelementByName(array_key_first($this->captchaPosition)))
                    return null;
            }
            return $this->captchaPosition;
        }

        /**
         * Add a success message to the Captcha after successful validation
         * @param string $successmsg
         * @return $this
         */
        public function setCaptchaSuccessMsg(string $successmsg): self
        {
            $this->captchaSuccessMsg = $successmsg;
            return $this;
        }

        /**
         * Add a placeholder to the Captcha input
         * @param string $placeholder
         * @return $this
         */
        public function setCaptchaPlaceholder(string $placeholder): self
        {
            $this->captchaPlaceholder = $placeholder;
            return $this;
        }

        /**
         * Remove the label tag from the CAPTCHA if needed
         * Optionally, you can display the value of the label as placeholder text by setting the parameter to true
         * @param bool $usePlaceholder -> true: the label text will be displayed as placeholder text, false: not
         * @return $this
         */
        public function removeCaptchaLabel(bool $usePlaceholder = false): self
        {
            $this->removeCaptchaLabel = true;
            $this->useCaptchaLabelAsPlaceholder = $usePlaceholder;
            return $this;
        }

        /**
         * Show the entered value inside a multi-question CAPTCHA again if the question is the same as before and the
         * value was valid, this method is only designed for the simple question CAPTCHA with multiple random questions
         * @param bool $show
         * @return $this
         */
        public function showValueOnSameQuestionAgain(bool $show): self
        {
            $this->showValueOnSameQuestionAgain = $show;
            return $this;
        }


        /**
         * Enable or disable the usage of ARIA attributes on form elements
         * Can be true/empty or false
         * If set to true then ARIA attributes will be added to input tags
         * @param bool $use
         * @return $this
         */
        public function useAriaAttributes(bool $use = true): self
        {
            $this->useAriaAttributes = $use;
            return $this;
        }


        /**
         * Set the description position on per form base
         * @param string $pos
         * @return $this
         */
        public function setDescPosition(string $pos): self
        {
            if (in_array($pos, ['beforeLabel', 'afterLabel', 'afterInput'])) {
                $this->general_desc_position = $pos; // set new position property
            }
            return $this;
        }


        /**
         * Create a new mail instance of a given custom mail module if set
         * Otherwise a new WireMail object will be instantiated
         * This method is only for other modules based on FrontendForms
         * @param string|null $class
         * @return \ProcessWire\WireMail|\FrontendForms\WireMailPostmark|\FrontendForms\WireMailPostmarkApp
         * @throws \ProcessWire\WireException
         */
        //protected function newMailInstance(string|null $class = null): WireMail|WireMailPostmark|WireMailPostmarkApp|WireMailSmtp|WireMailPHPMailer
        protected function newMailInstance(string|null $class = null)
        {

            // if $class is null, set WireMail() object by default
            if (is_null($class))
                return new WireMail();

            // just to play safe - check if the given module is installed first
            if (!$this->wire('modules')->getModuleID($class))
                return new WireMail();

            // create a new instance of the given module
            switch ($class) {
                case('WireMailPostmark'):
                case('WireMailPostmarkApp'):
                    return $this->wire('mail')->new();
                    break;
                case('WireMailSmtp'):
                    return wireMail();
                case('WireMailPHPMailer'):
                    return wire("modules")->get("WireMailPHPMailer");
                default:
                    return new WireMail();
            }
        }

        /**
         * Get all files that were uploaded
         */
        public function getUploadedFiles(): array
        {
            return $this->uploaded_files;
        }

        /**
         * Enable/disable HTML5 form validation
         * @param bool $validation
         * @return $this
         */
        public function setHtml5Validation(string|int|bool|null $validation): self
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
        public function getHTML5Validation(): bool
        {
            return $this->frontendforms['input_html5_validation'];
        }

        /**
         * THIS METHOD IS DEPRECATED - USE useAjax() METHOD INSTEAD
         * Enable/disable form submission via ajax
         * @param bool|int|null $ajax - true => form will be submitted via Ajax
         * @return $this
         */
        public function setSubmitWithAjax(bool|int|null|string $ajax = true): self
        {
            $this->submitAjax = boolval($ajax);
            return $this;
        }

        /**
         * Submit a form via AJAX
         * @param bool|int|string|null $ajax
         * @return self
         */
        public function useAjax(bool|int|null|string $ajax = true): self
        {
            $this->setSubmitWithAjax($ajax);
            return $this;
        }

        /**
         * Whether to show the progressbar during the Ajax form submission or not
         * @param bool|int|string|null $showProgressbar
         * @return $this
         */
        public function showProgressbar(bool|int|null|string $showProgressbar = true): self
        {
            $this->showProgressbar = boolval($showProgressbar);
            return $this;
        }

        /**
         * Whether to rotate the Honeypot field randomly or not
         * @param bool|int|string|null $stop
         * @return $this
         */
        public function stopHoneypotRotation(bool|int|null|string $stop = false): self
        {
            $this->stopHoneypotRotation = boolval($stop);
            return $this;
        }

        /**
         * Get the setting value if Ajax should be used to submit the form
         * @return bool
         */
        public function getSubmitWithAjax(): bool
        {
            return (bool)$this->submitAjax;
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
         * Enable/Disable CSRF-protection check
         * @param int|string|bool $csrf
         * @return void
         */
        public function useCSRFProtection(int|string|bool $csrf): void
        {
            $this->useCSRFProtection = $this->sanitizeValueToInt($csrf);
        }

        public function getCSRFProtection(): bool
        {
            return (bool)$this->useCSRFProtection;
        }

        /**
         * Method to disable some methods if form is used inside an iframe on a different domain (crossdomain)
         * @return void
         * @throws \ProcessWire\WireException
         */
        public function useFormInCrossDomainIframe(): void
        {
            $this->useDoubleFormSubmissionCheck(false); // disable double submission check
            $this->useCSRFProtection(false); // disable CSRF-Attack check
            // disable the CAPTCHA because it does not work in crossdomain iframes
            $this->disableCaptcha();
        }

        /**
         * Should the form be displayed after a successful submission (true or 1) or not (false or 0)
         * By default, only the success-message will be displayed after valid form submission and not the whole form
         * This prevents double form submissions
         * @param bool|int $show
         * @return void
         */
        public function showForm(bool|int $show): void
        {
            $this->showForm = $show;
        }

        /**
         * Get the value whether the form should be displayed after successful submission or not
         * @return bool
         */
        public function getShowForm(): bool
        {
            return $this->showForm;
        }

        /**
         * Method, that holds an array with all general placeholders
         * These placeholders can be used in mail templates or mail body templates/texts
         * The array contains the placeholder name as the key and its value (placeholder name => value)
         * @return array
         * @throws WireException
         */
        public function generalPlaceholders(): array
        {

            // check if $_SERVER['HTTP_USER_AGENT'] key exists, otherwise add 'n/a' as value
            if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
                $browser = $_SERVER['HTTP_USER_AGENT'];
            } else {
                $browser = $this->_('n/a');
            }

            return [
                'domainlabel' => $this->_('Domain'),
                'domainvalue' => $this->wire('config')->urls->httpRoot,
                'currenturllabel' => $this->_('Visited page'),
                'currenturlvalue' => $this->wire('input')->httpUrl(),
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
                'browservalue' => $browser,
                'donotreplayvalue' => $this->_('This is an auto generated message, please do not reply.')
            ];
        }

        /**
         * Create the default progress bar
         * @return string
         */
        public function createProgressbar(): string
        {
            return '
            <div class="cssProgress">
          <div class="progress1">
            <div class="cssProgress-bar cssProgress-active cssProgress-success" data-percent="100" style="width: 100%; transition: none 0s ease 0s;">
            </div>
          </div>
        </div>
            ';
        }

        /**
         * Method to add all general placeholders als name => value pair to the placeholder array
         * @return void
         * @throws WireException
         */
        protected function createGeneralPlaceholders(): void
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
        protected function setLangAppendix(): void
        {
            if ($this->wire('languages')) {
                $this->langAppendix = $this->userLang->isDefault() ? '' : '__' . $this->userLang->id;
            }
        }

        /**
         * Special general methods for sending emails
         */

        public static function checkForPath(string $pathfilename): bool
        {
            $pathInfo = pathinfo($pathfilename);
            if ($pathInfo['dirname'] !== '.') return true;
            return false;
        }

        /**
         * Include the template in the mail if it was set in the configuration or directly on the WireMail object
         * Takes the input_emailTemplate property to check whether a template should be used or not
         * @param Module|wire|WireArray|WireData $mail
         * @return void
         * @throws WireException
         * @throws DOMException
         * @throws Exception
         */
        protected function includeMailTemplate(Module|Wire|WireArray|WireData $mail): void
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
                    $mail->email_template = $this->frontendforms['input_emailTemplate'];
                }
                if ($mail->email_template != 'none') {

                    // check if template name or template path has been added
                    if (self::checkForPath($mail->email_template)) {
                        $this->emailTemplatesDirPath = $this->emailCustomTemplatesDirPath = '';
                    }

                    if ($this->wire('files')->exists($this->emailTemplatesDirPath . $mail->email_template)) {
                        $body = $this->loadTemplate($this->emailTemplatesDirPath . $mail->email_template);
                    } else if ($this->wire('files')->exists($this->emailCustomTemplatesDirPath . $mail->email_template)) {
                        $body = $this->loadTemplate($this->emailCustomTemplatesDirPath . $mail->email_template);
                    } else {
                        throw new Exception(sprintf('Mail could not be sent, because the mail template with the name %s does not exist.',
                            $mail->email_template));
                    }

                    // add pre-header text (if present) right after the opening body tag
                    if ($mail->title) {
                        $doc = new DOMDocument();
                        $doc->loadHTML($body);
                        $bodyTags = $doc->getElementsByTagName('body');
                        if ($bodyTags->length > 0) {
                            $bodyElement = $bodyTags->item(0);
                            $preheader = $doc->createElement('div', $mail->title . $this->getLitmusHack());
                            $preheader->setAttribute('style', $this->getPreheaderStyle());
                            $bodyElement->insertBefore($preheader, $bodyElement->firstChild);
                            $body = $doc->saveHTML();
                        }
                    }


                    // if bodyHTML is set, set a body placeholder by default out of the content
                    switch ($mail->className()) {
                        case('WireMailPHPMailer'):
                            $this->setMailPlaceholder('body', $mail->Body);
                            break;
                        default:
                            // default WireMail class used
                            $this->setMailPlaceholder('body', $mail->bodyHTML);
                    }

                    // render [[BODY]] placeholder if it is present and convert all placeholders inside it
                    if ($this->getMailPlaceholder('body')) {
                        $bodyPlaceholder = $this->getMailPlaceholder('body');
                        $bodyPlaceholder = wirePopulateStringTags($bodyPlaceholder, $this->getMailPlaceholders(), ['tagOpen' => '[[', 'tagClose' => ']]']);
                        $this->setMailPlaceholder('body', $bodyPlaceholder);
                    }

                    $body = wirePopulateStringTags($body, $this->getMailPlaceholders(), ['tagOpen' => '[[', 'tagClose' => ']]']);
                    // set the result as the bodyHTML of the email


                    // if bodyHTML is set, set a body placeholder by default out of the content
                    switch ($mail->className()) {
                        case('WireMailPHPMailer'):
                            $mail->Body = $body;
                            break;
                        default:
                            // default WireMail class used
                            $mail->bodyHTML($body);
                    }
                }
            } else {
                // add invisible div with email pre-header to the top of the email body
                switch ($mail->className()) {
                    case('WireMailPHPMailer'):
                        $mail->Body = $this->generateEmailPreHeader($mail) . $mail->Body;
                        break;
                    default:
                        // default WireMail class used
                        $mail->bodyHTML($this->generateEmailPreHeader($mail) . $mail->bodyHTML);
                }

            }
        }

        /**
         * Check if the form has at least one file upload field
         * Needs to be called after all fields were added
         * @return bool -> true: a file upload field was found, false: no file upload field found
         */
        protected function hasFileUploadField(): bool
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
        protected function getFileUploadFields(): array
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
         * @param \ProcessWire\HookEvent $event
         * @return \ProcessWire\Module|\ProcessWire\Wire|\ProcessWire\WireArray|\ProcessWire\WireData
         * @throws \DOMException
         * @throws \ProcessWire\WireException
         * @throws \ProcessWire\WirePermissionException
         */

        public function renderTemplate(HookEvent $event): Module|Wire|WireArray|WireData
        {
            $mail = $event->object;

            // do not add a template if the template is set to "none"
            if ($mail->email_template !== 'none') {

                // set the placeholder for the title if present
                $this->setMailPlaceholder('title', $mail->title);

                // set the placeholder for the body
                if (($mail->bodyHTML) || ($mail->bodyHtml) || ($mail->body)) {

                    // set HTML as preferred value
                    if ($mail->bodyHTML) {
                        $content = $mail->bodyHTML;
                    } else if ($mail->bodyHtml) {
                        $content = $mail->bodyHtml;
                    } else {
                        $content = $mail->body;
                    }

                    $body = wirePopulateStringTags($content, $this->getMailPlaceholders(),
                        ['tagOpen' => '[[', 'tagClose' => ']]']);

                    $this->setMailPlaceholder('body', $body);
                    $mail->bodyHTML($body);
                    $mail->body($body);

                }
                if ($this->wire('session')->get('templateloaded') != '1') {
                    $this->includeMailTemplate($mail); // include/use mail template if set
                    $this->wire('session')->set('templateloaded', '1');
                }
            } else {
                // populate Placeholders even if no template is used to send emails
                switch ($mail->className()) {
                    case('WireMailPHPMailer'):
                        $mail->Body = wirePopulateStringTags($mail->bodyHTML, $this->getMailPlaceholders(), ['tagOpen' => '[[', 'tagClose' => ']]']);
                        break;
                    default:
                        // default WireMail class used
                        $mail->bodyHTML(wirePopulateStringTags($mail->bodyHTML, $this->getMailPlaceholders(), ['tagOpen' => '[[', 'tagClose' => ']]']));
                }

            }

            return $mail;
        }

        /**
         * Set the mail body property depending on the custom mail module set
         * @param $mail
         * @param string|null $body
         * @param string $mailModule
         * @return void
         */
        public static function setBody($mail, string|null $body, string $mailModule): void
        {
            if (is_null($body)) $body = '';
            // add support for WireMailPHPMailer - has a different name for the bodyHTML property
            if ($mailModule === 'WireMailPHPMailer') {
                $mail->Body = $body;
            } else {
                $mail->bodyHTML($body);
            }
        }

        /**
         * This method prevents the multiple embedding of the email template if there are multiple forms on one page.
         * @return void
         * @throws \ProcessWire\WireException
         */
        public function removeTemplateSession(): void
        {
            $this->wire('session')->remove('templateloaded');
        }

        /**
         * Load a template file from the given path including php code and output it as a string
         * @param string $templatePath - the path to the template that should be rendered
         * @return string - the HTML template
         */
        protected function loadTemplate(string $templatePath): string
        {
            ob_start();
            include($templatePath);
            $var = ob_get_contents();
            ob_end_clean();
            return $var;
        }

        /**
         * Set the recipient email address on per-form base
         * In this case, the recipient can be set/changed on per-form base instead of directly on the WireMail object
         * Needed in every case, where the WireMail object is not directly reachable
         * @param string $email
         * @return $this
         * @throws WireException
         * @throws Exception
         */
        public function to(string $email): self
        {
            if ($this->wire('sanitizer')->email($email)) {
                $this->receiverAddress = $email;
            } else {
                throw new Exception("Email address for the recipient is not a valid email address.", 1);
            }
            return $this;
        }

        /**
         * Set the subject for the email on per-form base
         * In this case, the subject can be set/changed on per-form base instead of directly on the WireMail object
         * Needed in every case, where the WireMail object is not directly reachable
         * @param string $subject
         * @return $this
         */
        public function subject(string $subject): self
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
        public function getDate(string|null $dateTime = null): string
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
        public function getTime(string|null $dateTime = null): string
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
        public function getDateTime(string|null $dateTime = null): string
        {
            return $this->getDate($dateTime) . ' ' . $this->getTime($dateTime);
        }

        /**
         * Set a new placeholder variable with a specific value to the mailPlaceholder array
         * @param string $placeholderName
         * @param string|array|null $placeholderValue
         * @return $this
         */
        public function setMailPlaceholder(string $placeholderName, string|array|null $placeholderValue): self
        {

            if (!is_null($placeholderValue)) {
                $placeholderName = strtoupper(trim($placeholderName));
                if (is_array($placeholderValue)) {
                    // check if the array is multidimensional like multiple file uploads
                    if (count($placeholderValue) == count($placeholderValue, COUNT_RECURSIVE)) {
                        // one-dimensional: convert the array of values to comma separated string
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
        public function removePlaceholder(string $placeholderName): void
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
        public function getMailPlaceholders(): array
        {
            return $this->mailPlaceholder;
        }

        /**
         * Get the value of a certain placeholder by its name
         * @param string $placeholderName
         * @return string
         */
        public function getMailPlaceholder(string $placeholderName): string
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
        protected function getFormFieldClasses(): array
        {
            $classes = [];
            foreach ($this->formElements as $fieldObject) {
                $classes[] = $fieldObject->className();
            }
            return $classes;
        }

        /**
         * Check if an input field with a specific name is present the current form (but not if it has a value)
         * @param string $fieldName
         * @return bool
         */
        public function formfieldExists(string $fieldName): bool
        {
            $fieldName = (trim($fieldName));
            return (in_array(strtolower($fieldName), array_map("strtolower", $this->getFormFieldClasses())));
        }

        /**
         * Output the value of multilang fields from the module configuration
         * @param string $fieldName
         * @param array|null $modulConfig
         * @param int|null $lang_id
         * @return string
         */
        protected function getLangValueOfConfigField(
            string   $fieldName,
            array    $modulConfig = null,
            int|null $lang_id = null
        ): string
        {
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
         * If no path is selected, then the files will be stored inside the dir of this page in site/assets/files
         * @param string $path_to_folder
         * @return Form
         */
        public function setUploadPath(string $path_to_folder): self
        {
            $this->uploadPath = trim($path_to_folder);
            return $this;
        }

        /**
         * Get the upload path for files
         * @return string
         */
        public function getUploadPath(): string
        {
            return $this->uploadPath;
        }

        /**
         * This method is only for testing of ip addresses that should be banned
         * Enter ip addresses as a numeric array
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
            $this->frontendforms['input_useIPBan'] = $this->sanitizeValueToInt($enabled);
        }

        /**
         * Disable/enable and set type of captcha on per-form base
         * @param string $captchaType
         * @return void
         */
        protected function setCaptchaType(string $captchaType): void
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
        public function disableCaptcha(): void
        {
            $this->setCaptchaType('none');
        }

        /**
         * Get the captcha type set
         * @return string
         */
        protected function getCaptchaType(): string
        {
            return $this->frontendforms['input_captchaType'];
        }

        /**
         * Set the captcha category (text, image) depending on the captcha type
         * @param string $captchaType
         * @return void
         */
        protected function setCaptchaCategory(string $captchaType): void
        {
            $this->captchaCategory = AbstractCaptchaFactory::getCaptchaTypeFromClass($captchaType);
        }

        /**
         * Get the captcha category
         * @return string
         */
        public function getCaptchaCategory(): string
        {
            return $this->captchaCategory;
        }

        /**
         * Get the captcha object for further manipulations
         * @return object|null
         */
        protected function getCaptcha(): object|null
        {
            return $this->captcha;
        }

        /**
         * Check if the visitor is on the blacklist or not
         * @return bool - true if the visitor is not on the blacklist
         */
        protected function allowFormViewByIP(): bool
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
         * Will be needed for the list of banned IP in the module configuration
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
            $this->frontendforms['input_logFailedAttempts'] = $this->sanitizeValueToInt($logFailedAttempts);
        }

        /**
         * Convert post-values to a string
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
         * Enable/disable the wrapping of radios by its label
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
         * This method creates an inner wrapper over all form elements if set to true, or it removes the wrapper if set
         * to false So it adds a <div> tag after the opening form tag and a </div> tag before the closing form tag
         * @param bool $useFormElementsWrapper
         * @return Wrapper
         */
        public function useFormElementsWrapper(int|string|bool $useFormElementsWrapper): Wrapper
        {
            $useFormElementsWrapper = $this->sanitizeValueToInt($useFormElementsWrapper); // sanitize to int
            $this->frontendforms['input_wrapperFormElements'] = $useFormElementsWrapper;
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
         * Set the success message for successful form submission
         * Can be used to overwrite the default success message
         * @param string $successMsg
         * @return void
         */
        public function setSuccessMsg(string $successMsg): void
        {
            if ($successMsg === '') {
                $successMsg = $this->_('Thank you for your message.');
            }
            $this->frontendforms['input_alertSuccessText'] = trim($successMsg);
        }

        /**
         * Method to overwrite the default CAPTCHA error message
         * @param string $errormsg
         * @return $this
         */
        public function setCaptchaErrorMsg(string $errormsg): self
        {
            $this->captchaErrorMsg = $errormsg;
            return $this;
        }

        /**
         * Method to overwrite the default CAPTCHA required error message
         * @param string $errormsg
         * @return $this
         */
        public function setCaptchaRequiredErrorMsg(string $errormsg): self
        {
            $this->captchaRequiredErrorMsg = $errormsg;
            return $this;
        }

        /**
         * Method to overwrite the default CAPTCHA notes
         * @param string $notes
         * @return $this
         */
        public function setCaptchaNotes(string $notes): self
        {
            $this->captchaNotes = $notes;
            return $this;
        }

        /**
         * Method to add a description text to the CAPTCHA input field
         * @param string $desc
         * @return $this
         */
        public function setCaptchaDescription(string $desc): self
        {
            $this->captchaDescription = $desc;
            return $this;
        }

        /**
         * Set the position of the description text of the CAPTCHA input field
         * Can be 'beforeLabel', 'afterLabel' or 'afterInput'
         * @param string $pos
         * @return $this
         */
        public function setCaptchaDescriptionPosition(string $pos): self
        {
            if (in_array($pos, ['beforeLabel', 'afterLabel', 'afterInput'])) {
                $this->captchaDescriptionPosition = $pos; // set new position property
            }
            return $this;
        }

        /**
         * DEPRICATED: This method has been replaced by the setSecurityQuestions method
         * @param array $questions
         * @return void
         */
        public function setSimpleQuestionCaptchaRandomRotation(array $questions): void
        {
            $this->setSecurityQuestions($questions);
        }

        /**
         * Take a random question for the CAPTCHA out of an array of several questions
         * @param array $questions
         * @return void
         * @throws \ProcessWire\WireException
         */
        protected function getRandomQuestion(array $questions): void
        {
            if ($questions) {
                // check if the chosen CAPTCHA type is the simple question CAPTCHA
                if ($this->getCaptchaType() == 'SimpleQuestionCaptcha') {

                    $random_question = array_rand($questions);
                    $random_question_array = $questions[$random_question];

                    // only set a random question if at least the question and the answer keys are present
                    if ($random_question_array['question'] && $random_question_array['answers']) {
                        // only set a random question if question is a string and the answers is an array
                        if (is_string($random_question_array['question']) && is_array($random_question_array['answers'])) {
                            $this->setSecurityQuestion($random_question_array['question'], $random_question_array['answers']);

                            $question_array_array['question'] = $random_question_array['question'];
                            $question_array_array['answers'] = $random_question_array['answers'];
                            // unset the question and the answer keys from the array
                            unset($random_question_array['question']);
                            unset($random_question_array['answers']);
                            // unset values that will be displayed only after post
                            unset($random_question_array['successMsg']);
                            unset($random_question_array['errorMsg']);

                            // set additional properties if present
                            foreach ($random_question_array as $name => $value) {
                                $methodName = 'setCaptcha' . ucfirst($name);
                                if (method_exists($this, $methodName)) {
                                    $this->$methodName($value);
                                }
                            }

                        }
                    }

                    $this->random_question = $random_question; // returns integer
                    $this->random_question_array = $random_question_array;
                    $this->question_array = $questions;

                }
            }
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
            $this->frontendforms['input_alertErrorText'] = trim($errorMsg);
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
         * You can enter pure name or name attribute including form prefix
         * @param string $name - the name attribute of the input field
         * @return string|array|null
         * @throws \ProcessWire\WireException
         */
        public function getValue(string $name): string|array|null
        {
            $name = $this->createElementName(trim($name));
            if ($this->getValues()) {
                // first check if the name exists
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
         * Get all sanitized form values after form submission as an array
         * If there are sanitizers set for the form values, they will be applied
         * @param bool $buttonValue : If there are buttons set the value of the buttons will be applied too
         * @return array|null
         * @throws \ProcessWire\WireException
         */
        public function getValues(bool $buttonValue = false): array|null
        {

            // add button elements to inputfields if set
            $elements = $this->getNamesOfInputFields();
            foreach ($this->getFormElementsByClass('Button') as $button) {
                if ($button->hasAttribute('value')) {
                    if ($button->hasAttribute('name')) {
                        if ($buttonValue) {
                            $elements[] = $button->getAttribute('name');
                        }
                    }
                }
            }

            $values = [];
            foreach ($elements as $key) {

                // remove [] from name attribute if present
                $key = str_replace('[]', '', $key);

                // check if inputfield is a file upload field
                $formElement = $this->getFormelementByName($key);
                if ($formElement && $formElement instanceof InputFile) {
                    $files = [];
                    if ($this->storedFiles) {
                        $pathFileArray = $this->storedFiles;
                        $filesArray = [];
                        foreach ($pathFileArray as $path) {
                            // output only the basename without the whole path
                            $filesArray[] = pathinfo($path, PATHINFO_BASENAME);
                        }
                        $values[$key] = $filesArray;
                    } else {
                        if (is_array($_FILES[$key]['name'])) {
                            // multiple upload field
                            foreach ($_FILES[$key]['name'] as $filename) {
                                $files[] = strtolower($this->wire('sanitizer')->filename($filename));
                            }
                            $values[$key] = $files;
                        } else {
                            // single upload field
                            $values[$key] = $_FILES[$key]['name'];
                        }

                    }

                } else {

                    if (array_key_exists($key, $this->values)) {
                        $values[$key] = $this->values[$key];
                    }
                }
            }
            return $values; // array
        }

        /**
         * Get all Elements (inputs, buttons, ...) that are added to the form object
         * @return array - returns an array of all form element objects
         */
        public function getFormElements(): array
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
        public function getFormelementByName(string $name, bool $checkPrefix = true): object|bool
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
         * Get all elements of the form that are an object of a specific class
         * Returns an array containing all objects of the given class (e.g., all Button elements)
         * @param string $class
         * @return array
         */
        public function getFormElementsByClass(string $class): array
        {
            // remove namespace first if set
            if (str_contains($class, '\\'))
                $class = substr(strrchr($class, '\\'), 1);

            $items = [];
            foreach ($this->formElements as $element) {

                $className = substr(strrchr(get_class($element), '\\'), 1);
                if ($className == $class) {
                    $items[] = $element;
                }
            }
            return $items;
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
            $this->frontendforms['input_useHoneypot'] = $this->sanitizeValueToInt($honeypot);
        }

        /**
         * Method to rearrange the multiple files array $_FILES
         * @param array $file_post
         * @return array
         */
        private function reArrayFiles(array $file_post): array
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
        private function storeUploadedFiles(array $formElements): array
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
                                    // sanitize file name and convert it to lowercase to prevent problems on certain servers
                                    $filename = $this->wire('sanitizer')->filename($file['name'], true);
                                    $target_file = $this->uploadPath . strtolower($filename);

                                    $uploaded_files[] = $target_file;
                                    move_uploaded_file($file['tmp_name'], $target_file);
                                }
                            }
                        } else {
                            // single file
                            $file = $_FILES[$fieldName];
                            if ($file['error'] == 0) {
                                // sanitize file name and convert it to lowercase to prevent problems on certain servers
                                $filename = $this->wire('sanitizer')->filename(basename($file['name']), true);
                                $target_file = $this->uploadPath . strtolower($filename);
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
        protected function simplifyMultiFileArray(array $files = []): array
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
        protected function putRequiredOnTop(array $rules): array
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
        public function ___isValid(): bool
        {

            // Add the multi-question array for the simple question CAPTCHA

            if ($this->question_array)
                $this->getRandomQuestion($this->question_array);

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

            // instantiate the Captcha field if set
            $useCaptcha = ($this->getCaptchaType() !== 'none');


            if ($useCaptcha) {

                // create the input field first
                $this->captchafield = $this->getCaptcha()->createCaptchaInputField($this->getID());

                // check if multi question -> if yes, add the question as label to the CAPTCHA
                if (($this->getCaptchaType() == 'SimpleQuestionCaptcha') && $this->question_array) {
                    $current_question = $this->question_array[$this->random_question];
                    $this->captchafield->setLabel($current_question['question']);
                }

                // add placeholder attribute if present
                if ($this->captchaPlaceholder && ($this->captchafield instanceof InputText))
                    $this->captchafield->setAttribute('placeholder', $this->captchaPlaceholder);

                // add notes to the captcha input field if set
                if ($this->captchaNotes) $this->captchafield->setNotes($this->captchaNotes);

                // add description and position to the captcha input field if set
                if ($this->captchaDescription) $this->captchafield->setDescription($this->captchaDescription)->setPosition($this->captchaDescriptionPosition);

                if ($this->captchaRequiredErrorMsg) {
                    $this->captchafield->setRule('required')->setCustomMessage($this->captchaRequiredErrorMsg);
                } else {
                    if ($this->getCaptchaType() == 'SliderCaptcha') {
                        $checkboxID = $this->getID() . '-' . $this->captchafield->getID();
                        // workaround for checked checkox with empty string as value
                        // will be needed for the required validator to work properly in this case
                        if (!is_null($input->$checkboxID) && ($input->$checkboxID == '')) {
                            $input->set($checkboxID, '1');
                        }
                        $this->captchafield->setRule('required')->setCustomMessage($this->_('Please verify that you are a human and not a bot.'));
                    } else {
                        $this->captchafield->setRule('required')->setCustomMessage($this->_('Please fill out the security question.'));
                    }
                }

            }

            // instantiates the FormValidation object

            $validation = new FormValidation($input, $this, $this->alert);

            // 1) check if this form was submitted and no other form on the same page
            if ($validation->thisFormSubmitted()) {

                // add a validation class to the form after it was submitted
                $this->setCSSClass('formClassValidated'); // add the CSS class
                // 2) check if form was submitted in time range
                if ($validation->checkTimeDiff($formElements)) {
                    // 3) check if max attempts were reached
                    if ($validation->checkMaxAttempts($this->wire('session')->attempts)) {
                        // 4) check for double form submission
                        if ($validation->checkDoubleFormSubmission($this, $this->useDoubleFormSubmissionCheck)) {
                            // 5) Check for CSRF attack
                            if ($validation->checkCSRFAttack($this->getCSRFProtection())) {

                                /* START PROCESSING THE FORM */

                                //add honeypotfield to the array because it will be rendered afterwards
                                if ($this->frontendforms['input_useHoneypot']) {
                                    $formElements[] = $this->createHoneypot();
                                }
                                //add captcha to the array because it will be rendered afterwards
                                if ($useCaptcha) {

                                    // special treatment for SimpleQuestionCaptcha
                                    if (wireClassName($this->captcha) === 'SimpleQuestionCaptcha') {
                                        // add custom answers if present
                                        if ($this->answers)
                                            $this->getCaptcha()->setCaptchaValidValue($this->answers);

                                        // set the appropriate validator
                                        if ($this->getCaptcha()->getCaptchaValidValue()) {

                                            $validValue = $this->getCaptcha()->getCaptchaValidValue();

                                            // overwrite the default value with the one from the session for random question if present
                                            if (array_key_exists($this->getID() . '-random_key', $_POST)) {
                                                $prev_question = $this->question_array[$_POST[$this->getID() . '-random_key']];
                                                $validValue = $prev_question['answers'];
                                                if (array_key_exists('errorMsg', $prev_question)) {
                                                    // set the custom error message
                                                    $errormsg = $prev_question['errorMsg'];
                                                    // set the valid values from the session

                                                } else {
                                                    $errormsg = $this->captchaErrorMsg ?? $this->_('The answer is wrong!');
                                                }
                                            } else {
                                                $errormsg = $this->captchaErrorMsg ?? $this->_('The answer is wrong!');
                                            }

                                            $this->captchafield->setRule('compareTexts', $validValue)->setCustomMessage($errormsg);
                                        }

                                    }

                                    if ($this->getCaptchaType() == 'SliderCaptcha') {

                                        // add the servers side validation of the slider CAPTCHA
                                        $this->captchafield->setRule('checkSliderCaptcha', $input[$this->getID() . '-xPos'], $input[$this->getID() . '-yPos'], $this->getID())->setCustomMessage($this->_('The Slider-Captcha was not solved correctly.'));
                                    }

                                    $formElements[] = $this->captchafield;
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
                                    } else {
                                        // remove all validation rules from this element
                                        $element->removeAllRules();
                                    }


                                    // check if the field is inside the POST array
                                    // if not (e.g., field is disabled), then remove all validation rules, because no user input can be entered
                                    if ($element instanceof InputFile) {
                                        $fieldValue = $_FILES; // files are not inside the post array
                                    } else {
                                        $fieldValue = $this->wire('input')->post($this->getID() . '-' . $element->getID());
                                    }

                                    if (is_null($fieldValue) && (!$element instanceof InputCheckbox) && (!$element instanceof InputCheckboxMultiple)) { // exclude checkboxes because they are allowed to have no value
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

                                            $v->rule($validatorName, $element->getAttribute('name'), ...$parameters['options']);

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

                                    if ($useCaptcha) {

                                        if ($element->getAttribute('name') == $this->createElementName('captcha')) {

                                            // exclude this CAPTCHA types from using the checkCaptcha rule
                                            $nonCheckCaptchaTypes = ['SimpleQuestionCaptcha'];
                                            if (!in_array($this->getCaptchaType(), $nonCheckCaptchaTypes)) {
                                                // check if the custom error message has been set
                                                if ($this->captchaErrorMsg) {
                                                    $v->rule('checkCaptcha', $element->getAttribute('name'),
                                                        $this->wire('session')->get('captcha_' . $this->getID()))->message($this->captchaErrorMsg);
                                                } else {
                                                    $v->rule('checkCaptcha', $element->getAttribute('name'),
                                                        $this->wire('session')->get('captcha_' . $this->getID()))->label($this->_('The CAPTCHA'));
                                                }
                                            }
                                        }
                                    }
                                    $this->setValues();
                                }

                                if ($v->validate()) {
                                    $this->validated = '1';
                                    $this->alert->setCSSClass('alert_successClass');
                                    $this->alert->setText($this->getSuccessMsg());
                                    $this->wire('session')->remove('attempts');
                                    $this->wire('session')->remove('submitted');
                                    // remove attempt session
                                    $this->wire('session')->remove('doubleSubmission-' . $this->getID());
                                    // remove the session for checking for double form submission
                                    $this->showForm = false;
                                    // check if files were uploaded and store them inside the chosen folder
                                    $this->uploaded_files = $this->storeUploadedFiles($formElements);
                                    // remove session added by matchUser or matchEmail validation rule if present
                                    $this->wire('session')->remove($this->getAttribute('id') . '-email');
                                    $this->wire('session')->remove($this->getAttribute('id') . '-username');

                                    // finally, add the files including the overwritten filenames to the array
                                    if ($this->storedFiles) {
                                        $this->uploaded_files = $this->storedFiles;
                                    }

                                    return true;
                                } else {
                                    // set error alert
                                    $this->wire('session')->set('errors', '1');
                                    $this->formErrors = $v->errors();


                                    // check if a CAPTCHA is enabled
                                    if ($this->getCaptchaType() != 'none') {

                                        $captchaName = $this->getID() . '-captcha';
                                        // if Captcha value was valid -> add it to the captcha_value property
                                        if ($this->getCaptchaType() === 'SimpleQuestionCaptcha') {

                                            if (!array_key_exists($captchaName, $this->formErrors)) {
                                                // captcha was valid

                                                // check if it is multi-question
                                                if (array_key_exists($this->getID() . '-random_key', $_POST)) {

                                                    $prev_question = $this->question_array[$_POST[$this->getID() . '-random_key']];
                                                    if (array_key_exists('successMsg', $prev_question)) {
                                                        $this->captchafield->setSuccessMessage($prev_question['successMsg']);
                                                    } else {
                                                        // output the global success message if set
                                                        $this->captchafield->setSuccessMessage($this->captchaSuccessMsg);
                                                    }

                                                    // check if the current question is the same as before -> otherwise remove the CAPTCHA value on multi question CAPTCHA
                                                    if ($this->showValueOnSameQuestionAgain) {
                                                        // question is not the same as before -> delete the value
                                                        if ($prev_question['question'] != $this->question) {
                                                            $this->captchafield->setAttribute('value', '');
                                                        }
                                                    } else {// false
                                                        $this->captchafield->setAttribute('value', '');
                                                    }

                                                } else {
                                                    // single question CAPTCHA
                                                    // add the value back to this field on success if there is only a single question set (not an array)
                                                    $this->captchafield->setAttribute('value', $_POST[$this->getID() . '-captcha']);
                                                    $this->captchafield->setSuccessMessage($this->captchaSuccessMsg);

                                                }

                                            } else {
                                                // entered CAPTCHA value was wrong
                                                $this->captchafield->setAttribute('value', '');
                                            }
                                        } else {
                                            // all other CAPTCHA types
                                            if (!array_key_exists($captchaName, $this->formErrors)) {
                                                $this->captchafield->setSuccessMessage($this->captchaSuccessMsg);
                                            }
                                            // CAPTCHA value will be deleted in any way
                                            $this->captchafield->setAttribute('value', '');
                                        }
                                    }

                                    $this->alert->setCSSClass('alert_dangerClass');
                                    $this->alert->setText($this->getErrorMsg());

                                    // add a max attempts warning message to the error message
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

                                    // set the submitted session to 1, which means the form is submitted at least 1 time
                                    $this->wire('session')->submitted = 1;

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
        private function createHoneypot(): InputText
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
         * Internal method to add all form values to the values' array
         * All sanitizers applied to an input element will be used before they will be added to the array
         * @return void
         * @throws \ProcessWire\WireException
         */
        private function setValues(): void
        {
            $post_values = $this->wire('input')->post;
            // get buttons
            foreach ($this->getFormElementsByClass('Button') as $button) {
                if ($button->hasAttribute('value')) {
                    if ($button->hasAttribute('name')) {
                        $post_values[$button->getAttribute('name')] = $button->getAttribute('value');
                    }
                }
            }

            $values = [];
            foreach ($post_values as $name => $value) {

                // grab formelement by its name attribute
                $element = $this->getFormelementByName($name);

                if ($element) {

                    // Run all sanitizer methods over the value
                    if (method_exists($element, 'getSanitizers')) {
                        $sanitizers = $element->getSanitizers();
                        foreach ($sanitizers as $sanitizer) {
                            $value = $element->wire('sanitizer')->$sanitizer($post_values->$name);
                        }
                    } else {
                        $value = $post_values->$name;
                    }
                } else {
                    // no element exists -> get the value directly from the POST array
                    $value = $post_values->$name;

                }
                $values[$name] = $value;

                // set all form values to a placeholder
                $fieldName = str_replace($this->getID() . '-', '', $name) . 'value';

                $this->setMailPlaceholder($fieldName, $value);

            }

            $this->values = $values;
        }

        /**
         * Get the success message
         * @return string
         */
        protected function getSuccessMsg(): string
        {
            return $this->frontendforms['input_alertSuccessText'];
        }

        /**
         * Get the error message
         * @return string
         */
        protected function getErrorMsg(): string
        {
            return $this->frontendforms['input_alertErrorText'];
        }

        /**
         * Get the max attempts
         * @return int
         */
        public function getMaxAttempts(): int
        {
            return $this->frontendforms['input_maxAttempts'];
        }


        /**
         * Set the max attempts
         * @param int $maxAttempts
         * @return void
         */
        public function setMaxAttempts(int $maxAttempts): void
        {
            if ($maxAttempts < 1) {
                $this->frontendforms['input_logFailedLogins'] = 0;
            } //disable logging of failed attempts
            $this->frontendforms['input_maxAttempts'] = $maxAttempts;
        }

        /**
         * Method to run if a user has taken too many attempts
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
         * Set a redirect url after the form has been submitted successfully via Ajax
         * This forces a JavaScript redirect after the form has been validated without errors
         * @param string|null $url
         * @return $this
         */
        public function setRedirectUrlAfterAjax(string|null $url = null): self
        {
            if (!is_null($url)) {
                $this->ajaxRedirect = $url . $this->segments;
            }
            return $this;
        }

        /**
         * Set the URL for a redirect after successful form validation
         * @param string $url - the URL, where the redirect should go to
         * @return $this
         */
        public function setRedirectURL(string $url): self
        {
            $this->setRedirectUrlAfterAjax($url);
            $this->redirectURL = $url;
            return $this;
        }

        /**
         * Get the URL for a redirect if set, otherwise NULL
         * @return string|null
         */
        protected function getRedirectURL(): string|null
        {
            return $this->redirectURL;
        }


        /**
         * Check if the form contains an element from the given class
         * @param string $className
         * @return int -> the number of fields found
         */
        public function formContainsElementByClass(string $className): int
        {

            //$className = wireClassNamespace($className, true); // leads to problem with Phalcon framework
            $className = '\\FrontendForms\\' . $className;
            $number = (count(array_filter($this->formElements, function ($entry) use ($className) {
                return ($entry instanceof $className);
            })));
            return $number;
        }

        /**
         * If there are multiple instances of a given class, remove all except the last one
         * This is useful if only one instance is allowed, but there are multiple instances
         * Returns the key of the last item, which will not be deleted (unset)
         * @param string $className
         * @return int|null
         */
        public function removeMultipleEntriesByClass(string $className): null|int
        {
            // there are too many PrivacyText elements, only one is allowed per form -> remove all except the last one
            $elements = $this->getElementsbyClass($className);
            $k = null;
            if ($elements) {
                foreach ($elements as $key => $e) {
                    if ($key !== array_key_last($elements)) {
                        unset($this->formElements[key($e)]);
                    } else {
                        $k = key($e);
                    }
                }
            }
            return $k;
        }

        /**
         * Get all form element objects of a given class as an array
         * @param string $className
         * @return array
         */
        public function getElementsbyClass(string $className): array
        {
            $elements = [];
            if ($this->formContainsElementByClass($className)) {
                //$className = wireClassNamespace($className, true); // leads to problem with Phalcon framework
                $className = '\\FrontendForms\\' . $className;
                $elements[] = array_filter($this->formElements, function ($entry) use ($className) {
                    return ($entry instanceof $className);
                });
            }
            return $elements;
        }

        /**
         * Move an array item from one to another position
         * @param array $array
         * @param $key
         * @param int $order
         * @return void
         * @throws \Exception
         */
        protected function repositionArrayElement(array &$array, $key, int $order): void
        {
            if (($a = array_search($key, array_keys($array))) === false) {
                throw new Exception("The $key cannot be found in the given array.");
            }
            $p1 = array_splice($array, $a, 1);
            $p2 = array_splice($array, 0, $order);
            $array = array_merge($p2, $p1, $array);
        }

        protected function changeElementTag(object $element, string $tagProperty): void
        {
            if ($element) {
                if ($element->getCustomTag()) {
                    $element->setTag($element->getCustomTag());
                } else {
                    $element->setTag($tagProperty);
                }
            }
        }

        /**
         * Render the form markup (including alerts if present) on the frontend
         * @return string
         * @throws WireException
         * @throws \Exception
         */
        public function render(): string
        {

            // redirect after successful form validation if set
            if ($this->getRedirectURL() && $this->validated && !$this->getSubmitWithAjax()) {
                $this->wire('session')->redirect($this->getRedirectURL());
            }

            // check if slider Captcha has been selected and was not disabled on per form base
            if ($this->frontendforms['input_captchaType'] == 'SliderCaptcha') {
                $this->page->sliderCaptcha = true;
            }

            $out = '';

            // if Ajax submit was selected, add an aditional data attribute to the form tag
            if ($this->getSubmitWithAjax()) {

                // check if a user has JavaScript enabled, otherwise show a warning message inside an alert box
                $warningAlert = new Alert();
                $warningAlert->setCSSClass('alert_warningClass');
                $warningAlert->prepend('<noscript>');
                $warningAlert->append('</noscript>');
                $warningAlert->setText($this->_('You do not have Javascript enabled. This could cause problems. Please enable Javascript to submit the form without any problems.'));
                $out .= $warningAlert->render();

                $this->setAttribute('data-submitajax', $this->getID());

                // add special div container for Ajax form submission
                $out .= '<div id="' . $this->getID() . '-ajax-wrapper" data-validated="' . $this->validated . '">';
            }

            // Check if the form contains file upload fields, then add enctype attribute
            foreach ($this->formElements as $obj) {

                // check if the field contains a field condition
                if (($obj instanceof Element) && ($obj->containsConditions())) {
                    $this->page->field_conditions = true;
                }

                if ($obj instanceof InputFile) {
                    $this->setAttribute('enctype', 'multipart/form-data');
                    break;
                }

                //$class = __NAMESPACE__.'\Label';
                if (is_subclass_of($obj, 'FrontendForms\Inputfields')) {
                    // Label
                    $this->changeElementTag($obj->getLabel(), $this->labeltag);
                    $this->changeElementTag($obj->getDescription(), $this->desctag);
                    $this->changeElementTag($obj->getNotes(), $this->notestag);
                    $this->changeElementTag($obj->getErrorMessage(), $this->msgtag);
                    $this->changeElementTag($obj->getSuccessMessage(), $this->msgtag);

                }
            }

            if (!$this->allowFormViewByIP()) {
                $this->alert->setCSSClass('alert_warningClass');
                $this->alert->setText(sprintf($this->_('We are sorry, but your IP address %s is on the list of forbidden IP addresses. Therefore the form will not be displayed. If you think your IP address is mistakenly on the list, please contact the administrator of the site.'),
                    $this->visitorIP));
                // do not display form to banned visitors
                $this->showForm = false;
            }

            $out .= $this->prepend;
            $out .= $this->append;

            // allow only get or post - if value is not get or post set post as default value
            if (!in_array(strtolower($this->getAttribute('method')), Form::FORMMETHODS)) {
                $this->setAttribute('method', 'post');
            }

            // get token for CSRF protection
            $tokenName = $this->wire('session')->CSRF->getTokenName();
            $tokenValue = $this->wire('session')->CSRF->getTokenValue();

            // remove all instances of form elements where only one instance per form is allowed, but there are multiple
            $singleClassObjects = ['PrivacyText', 'Privacy'];
            foreach ($singleClassObjects as $className) {
                $this->removeMultipleEntriesByClass($className);
            }

            // reindex array
            $this->formElements = array_values($this->formElements);

            $buttons = $this->getElementsbyClass('Button');
            // get the first button
            if ($buttons) {

                $refKey = $firstButtonPos = key($buttons[0]);

                // add captcha field as last element before the button element
                if ($this->getCaptchaType() != 'none') {

                    // position in the form fields array to insert
                    $captchaPosition = $refKey;

                    if (wireClassName($this->captcha) === 'SimpleQuestionCaptcha') {

                        // add custom question as label if present
                        if ($this->question)
                            $this->captchafield->setLabel($this->question);

                        // check if a question and accepted answers have been set
                        $missing_msg = [];
                        // output a warning message if question for question CAPTCHA is missing
                        if (!$this->captchafield->getLabel()->getText()) {
                            $missing_msg[] = $this->_('You have not added a question for your question CAPTCHA!');
                        }
                        // output a warning message if answers for question CAPTCHA are missing
                        if (!$this->getCaptcha()->getCaptchaValidValue()) {
                            $missing_msg[] = $this->_('You have not added some answers for your question CAPTCHA!');
                        }

                        if ($missing_msg) {
                            $missing_msg[] = $this->_('If you do not correct this error, you cannot use the simple question CAPTCHA and the Captcha will not be displayed.');
                            $missingtext = implode('<br>', $missing_msg);
                            $this->alert->setCSSClass('alert_warningClass');
                            $this->alert->setText($missingtext);
                        }

                    }

                    // add the Captcha to the form array if everything is ok
                    if (((wireClassName($this->captcha) === 'SimpleQuestionCaptcha') && (!$missing_msg)) || (wireClassName($this->captcha) !== 'SimpleQuestionCaptcha')) {

                        // insert the captcha input field after the last input field
                        $this->formElements = array_merge(array_slice($this->formElements, 0, $captchaPosition),
                            array($this->captchafield), array_slice($this->formElements, $captchaPosition));

                        // re-index the formElements array
                        $this->formElements = array_values($this->formElements);

                    }

                    // remove label and set it as placeholder if set
                    if ($this->useCaptchaLabelAsPlaceholder && ($this->captchafield instanceof InputText)) {
                        $this->captchafield->setAttribute('placeholder', $this->captchafield->getLabel()->getText());
                        $this->captchafield->setLabel(''); // remove the label tag
                    }
                    // remove Label if set
                    if ($this->removeCaptchaLabel) {
                        $this->captchafield->setLabel('');
                    }

                    // get the position of the first button element
                    if ($this->getElementsbyClass('Button')) {

                        $firstButtonPos = key($this->getElementsbyClass('Button')[0]);

                        // change the position of the CAPTCHA if position change was set via API
                        $customizeCaptchaPosition = $this->getCaptchaPosition();

                        if (($this->getCaptchaType() != 'none') && ($customizeCaptchaPosition)) {

                            // get the position of the reference field inside the field object array
                            $ref_field_name = array_key_first($customizeCaptchaPosition);

                            foreach ($this->formElements as $key => $element) {
                                if ($this->getID() . '-' . $ref_field_name == $element->getAttribute('name')) {
                                    $ref_position = $key;
                                }
                            }

                            // add correction for the "before" position if the reference object is the last item in the array
                            $before_pos = ($ref_position != array_key_last($this->formElements)) ? $ref_position : $ref_position - 1;

                            $new_pos = (reset($customizeCaptchaPosition) === 'before') ? $before_pos : $ref_position + 1;
                            $this->repositionArrayElement($this->formElements, $captchaPosition, $new_pos);
                        }
                    }

                } else {
                    // no Captcha is used
                    if ($this->getElementsbyClass('Button')) {
                        $firstButtonPos = key($this->getElementsbyClass('Button')[0]);
                    }
                }

                // sort the privacy elements that checkbox is before text, if both are used
                $privacyElements = [];
                $privacyCheckbox = $this->getElementsbyClass('Privacy');
                if ($privacyCheckbox) {
                    $privacyElements[] = key($privacyCheckbox[0]);
                }
                $privacyText = $this->getElementsbyClass('PrivacyText');
                if ($privacyText) {
                    $privacyElements[] = key($privacyText[0]);
                }

                if ($privacyElements) {
                    sort($privacyElements);
                    $newPos = $firstButtonPos - 1;

                    $this->repositionArrayElement($this->formElements, $privacyElements[0], $newPos);
                    if (array_key_exists(1, $privacyElements)) {
                        $newPos = array_key_last($this->formElements) - 1;
                        $this->repositionArrayElement($this->formElements, $privacyElements[1] - 1, $newPos);
                    }
                }


                // create the new array of inputfields only to position the honeypot field in between
                $inputfieldKeys = [];

                // only for the slider captcha
                if ($this->getCaptchaType() == 'SliderCaptcha') {

                    $xPos = (float)rand() / (float)getrandmax();
                    $yPos = (float)rand() / (float)getrandmax();

                    $this->wire('session')->set($this->getID() . '-captcha_x', $xPos);
                    $this->wire('session')->set($this->getID() . '-captcha_y', $yPos);

                    // add x and y positions as data attributes for JavaScript usage later on
                    $this->captchafield->setAttribute('data-x', $xPos);
                    $this->captchafield->setAttribute('data-y', $yPos);
                }

                foreach ($this->formElements as $key => $element) {
                    if (is_subclass_of($element, 'FrontendForms\Inputfields')) {

                        // exclude hidden input fields - add only visible fields
                        if ($element->className() !== 'InputHidden') {
                            $inputfieldKeys[] = $key;
                        }

                    }
                    // check if field conditions have been set
                    if (method_exists($element, 'getConditions') && (!is_null($element->getConditions()))) {
                        $conditions = $element->getConditions();

                        if (count($conditions['rules']) == count($conditions['rules'], COUNT_RECURSIVE)) {
                            $conditions['rules'] = [$conditions['rules']];
                        }

                        // get all name attributes
                        $modified_rules = [];

                        foreach ($conditions['rules'] as $rule) {

                            if (!str_starts_with($rule['name'], $this->getID() . '-')) {
                                $rule['name'] = $this->getID() . '-' . $rule['name'];
                            }

                            $modified_rules[] = $rule;

                        }
                        $conditions['rules'] = $modified_rules;
                        $conditions = json_encode($conditions);
                        $element->setAttribute('data-conditional-rules', htmlspecialchars($conditions));

                    }
                }

                if (($this->frontendforms['input_useHoneypot']) && ($inputfieldKeys)) {

                    $honeypot = $this->createHoneypot();
                    if ($this->stopHoneypotRotation) {
                        // add honeypot field on the first position of the form
                        array_unshift($this->formElements, $honeypot);
                    } else {
                        // add honeypot on the random number field position
                        shuffle($inputfieldKeys);
                        array_splice($this->formElements, $inputfieldKeys[0], 0, [$honeypot]);
                    }
                }

                // create hidden Ajax redirect input if set
                // this value can be grabbed afterwards via JavaScript to make a JS redirect
                if ($this->getSubmitWithAjax()) {
                    if ($this->ajaxRedirect) {
                        $ajaxredirectField = new InputHidden('ajax_redirect');
                        $ajaxredirectField->setAttribute('value', $this->ajaxRedirect);
                        $this->add($ajaxredirectField);
                    }
                }

                // only for the slider captcha -> add hidden fields for the x and y position

                if ($this->getCaptchaType() == 'SliderCaptcha') {

                    $hiddenFieldX = new InputHidden('xPos');
                    $hiddenFieldX->setAttribute('name', 'xPos');
                    $hiddenFieldX->setAttribute('value', '-1');
                    $this->add($hiddenFieldX);

                    $hiddenFieldY = new InputHidden('yPos');
                    $hiddenFieldY->setAttribute('name', 'yPos');
                    $hiddenFieldY->setAttribute('value', '-1');
                    $this->add($hiddenFieldY);

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
                    $hiddenField4->setAttribute('value', self::encryptDecrypt((string)$this->load_time));
                    $this->add($hiddenField4);
                }

                // if a random question array is set, add the random item key to this field
                if (!is_null($this->random_question)) {
                    $hiddenField5 = new InputHidden('random_key');
                    $hiddenField5->setAttribute('value', $this->random_question);
                    $this->add($hiddenField5);
                }

                /* BLOCKING ALERTS */
                if ($this->wire('session')->get('blocked')) {
                    // set danger alert for blocking messages
                    $this->alert->setCSSClass('alert_dangerClass');
                    // return blocking text for too many failed attempts
                    if ($this->wire('session')->get('blocked') == 'maxAttempts') {
                        if (($this->getMaxAttempts()) && ($this->wire('session')->get('attempts') == $this->getMaxAttempts())) {
                            $this->alert->setText($this->_('You have reached the max. number of allowed attempts and therefore you cannot submit the form once more. To reset the blocking and to submit the form anyway you have to close this browser, open it again and visit this page once more.'));
                        }
                    }
                }

                // Output the form markup
                $out .= $this->alert->render();
                // render the alert box on top for success or error message

                // show form only if user is not blocked
                if ($this->showForm && (($this->wire('session')->get('blocked') == null))) {

                    //add required texts
                    $this->prepend($this->renderRequiredText('top')); // required a text hint at the top
                    $this->append($this->renderRequiredText('bottom')); // required text hint at bottom
                    $formElements = '';

                    $elementsClassNames = (array_map("get_class", $this->formElements));
                    $position = array_search('FrontendForms\Button', $elementsClassNames);

                    foreach ($this->formElements as $key => $element) {

                        //create input ID as a combination of form id and input name
                        $oldId = $element->getAttribute('id');
                        $element->setAttribute('id', $this->getID() . '-' . $oldId);

                        // change the name attribute of the CSRF field
                        if ($element->getID() == $this->getID() . '-post_token') {
                            $element->setAttribute('name', $tokenName);
                        }

                        // enable/disable usage of Aria attributes
                        if (method_exists($element, 'useAttributes'))
                            $element->useAriaAttributes($this->useAriaAttributes);

                        // Label and description (Only on input fields)
                        if (is_subclass_of($element, 'FrontendForms\Inputfields')) {

                            // set the description position on per form base if the description text is present
                            if ($element->getDescription()->getText()) {
                                // set position from form setting if no individual position has been set
                                if (is_null($element->getDescription()->getPosition())) {
                                    $element->getDescription()->setPosition($this->general_desc_position);
                                }
                            }

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

                        // add an element (progressbar, text,...) before the first button element for Ajax submit
                        if ($this->getSubmitWithAjax()) {

                            if ($key === $position) { // add it only before the first button inside the form
                                if ($this->showProgressbar) {
                                    // create progressbar and info text for form submission
                                    $submitInfo = new Alert();
                                    $submitInfo->setCSSClass('alert_primaryClass');
                                    $submitInfo->setContent($this->createProgressbar() . $this->frontendforms['input_ajaxMsg']);
                                    $formElements .= '<div id="' . $this->getID() . '-form-submission" class="progress-submission" style="display:none">' . $submitInfo->render() . '</div>';
                                }
                            }
                        }

                        if (array_key_exists($name, $this->formErrors)) {
                            $element->setCSSClass('input_errorClass');
                            // add Aria attributes
                            if (($this->useAriaAttributes) || ($this->frontendforms['input_framework'] === 'pico2.json')) {
                                $element->setAttribute('aria-invalid', 'true');
                                $element->setAttribute('aria-errormessage', $element->getID() . '-errormsg');
                            }

                            // set error class for input element
                            $element->setErrorMessage($this->formErrors[$name][0])->setAttribute('id', $element->getID() . '-errormsg');

                            //get a first error message
                        } else {
                            if (is_subclass_of($element, 'FrontendForms\Inputfields')) {

                                // ids to description, notes, successmessage for Aria attributes
                                $element->getSuccessMessage()->setAttribute('id', $element->getID() . '-successmsg');
                                $element->getDescription()->setAttribute('id', $element->getID() . '-desc');
                                $element->getNotes()->setAttribute('id', $element->getID() . '-notes');

                                if ($this->isSubmitted()) {
                                    if (($this->useAriaAttributes) || ($this->frontendforms['input_framework'] === 'pico2.json') && $this->isSubmitted()) {
                                        // add only on input elements with values
                                        if (!empty($element->getAttribute('value'))) {
                                            $element->setAttribute('aria-invalid', 'false');
                                            $element->setAttribute('aria-describedby', $element->getID() . '-successmsg');

                                        }
                                    }
                                }

                            }

                        }
                        $formElements .= $element->render() . PHP_EOL;
                    }

                    // add formElementsWrapper -> add the div container after the form tag
                    if ($this->frontendforms['input_wrapperFormElements']) {
                        $this->getformElementsWrapper()->setContent($formElements);
                        $formElements = $this->formElementsWrapper->render() . PHP_EOL;
                    }

                    // render the form with all its fields
                    $this->setContent($formElements);
                    $out .= $this->renderNonSelfclosingTag($this->getTag());

                }

                if ($this->getSubmitWithAjax()) {
                    $out .= '</div>';
                }

            }

            return $out;
        }

        /**
         * Append a field object to the form
         * @param object $field - object of inputfield, fieldset, button, ...
         * @return void
         */

        /**
         * Append a field object to the form
         * The 2 optional parameters are only for the creation of 2 new methods: addBefore() and addAfter()
         * These 2 methods can be used to add new form elements (inputs, text elements, fieldsets,…) to a formElements
         * array at a certain position These 2 methods are especially designed for the future usage in module dev - no
         * need to use it if you are creting the form by your own
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\Button|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose $field -
         *     the current form field which should be appended to the form
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\Button|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose|bool|null $otherfield -
         *     optional: another form field
         * @param bool $add_before - optional: current should be inserted before or after this (another) form field
         * @return void
         * @throws \Exception
         */
        public function add(
            Markup|Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose    $field,
            Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose|null|bool $otherfield = null,
            bool                                                                 $add_before = false
        ): void
        {

            // add or remove wrapper divs on each form element
            if (is_subclass_of($field, 'FrontendForms\Inputfields')) {

                // check if the usage of inputwrapper is set on per field base
                if (is_null($field->getUsageOfInputWrapper())) {
                    $useinputwrapper = $this->useInputWrapper;
                } else {
                    $useinputwrapper = $field->getUsageOfInputWrapper();
                }

                $field->useInputWrapper($useinputwrapper);

                // check if the usage of fieldwrapper is set on per field base
                if (is_null($field->getUsageOfFieldWrapper())) {
                    $usefieldwrapper = $this->useFieldWrapper;
                } else {
                    $usefieldwrapper = $field->getUsageOfFieldWrapper();
                }
                $field->useFieldWrapper($usefieldwrapper);

                // create a placeholder for the label of this field
                $fieldname = $field->getAttribute('name');
                $this->setMailPlaceholder($fieldname . 'label', $field->getLabel()->getText());
                $this->setMailPlaceholder($fieldname . 'value', $field->getAttribute('value'));
            }
            // if the field is not a text element and not a markup, set the name attribute if not set before
            if ((!is_subclass_of($field, 'FrontendForms\TextElements')) && (get_class($field) != 'FrontendForms\Markup')) {

                // Add id of the form as prefix for the name attribute of the field
                if ($field->hasAttribute('name')) {
                    $fieldName = $field->getAttribute('name');
                } else {
                    $fieldName = $field->getId();
                }
                $field->setAttribute('name', $this->getID() . '-' . $fieldName);
            }

            if (!is_null($otherfield)) {
                // check if another field exists
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
         * Can be used if you have not created the form by your own, but you need to add a new field to a created
         * formElements array at a certain position
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\Button|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose $field -
         *     the current form field
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose|\FrontendForms\Button|bool $before_field -
         *     the form field object before which the current form field object should be inserted
         * @return void
         * @throws \Exception
         */
        public
        function addBefore(
            Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose      $field,
            Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $before_field
        ): void
        {
            // if a field is present inside the formelements array, remove it first
            if (($field->getAttribute('name')) && ($this->getFormelementByName($field->getAttribute('name')))) {
                $this->remove($field);
            }
            $this->add($field, $before_field, true);
        }

        /**
         * Insert a form field after another form field
         * Can be used if you have not created the form by your own, but you need to add a new field to a created
         * formElements array at a certain position
         *                    *
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\Button|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose $field -
         *     the current form field
         * @param \FrontendForms\Inputfields|\FrontendForms\Textelements|\FrontendForms\FieldsetOpen|\FrontendForms\FieldsetClose|\FrontendForms\Button|bool $after_field -
         *     the form field object after which the current form field object should be inserted
         * @return void
         * @throws \Exception
         */
        public
        function addAfter(
            Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose      $field,
            Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $after_field
        ): void
        {
            // if a field is present inside the formelements array, remove it first
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
        public
        function remove(object $field): void
        {
            if (($key = array_search($field, $this->formElements)) !== false) {
                unset($this->formElements[$key]);
                // remove the placeholders too if they are present
                $fieldname = $field->getAttribute('name');
                $this->removePlaceholder(strtoupper($fieldname . 'label'));
                $this->removePlaceholder(strtoupper($fieldname . 'value'));
            }
        }

        /**
         * Get the min time value
         * @return int
         */
        public
        function getMinTime(): int
        {
            return $this->frontendforms['input_minTime'];
        }

        /**
         * Set the min time in seconds before the form should be submitted
         * @param int $minTime
         * @return $this
         */
        public
        function setMinTime(int $minTime): self
        {
            $this->frontendforms['input_minTime'] = $minTime;
            return $this;
        }

        /**
         * Get the max time value
         * @return int
         */
        protected
        function getMaxTime(): int
        {
            return $this->frontendforms['input_maxTime'];
        }

        /**
         * Set the max time in seconds until the form should be submitted
         * @param int $maxTime
         * @return $this
         */
        public
        function setMaxTime(int $maxTime): self
        {
            $this->frontendforms['input_maxTime'] = $maxTime;
            return $this;
        }

        /** Static method to encrypt/decrypt a string according to the encryption settings
         * @param string $string
         * @param string $method
         * @return string
         */
        public
        static function encryptDecrypt(string $string, string $method = 'encrypt'): string
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
         * Create a required hint text element if showTextHint is set to true
         * @param string $position - has to be 'top' or 'bottom'
         * @return string
         */
        private
        function renderRequiredText(string $position): string
        {
            if ($this->defaultRequiredTextPosition === $position) {
                return $this->requiredHint->render();
            }
            return ''; // return empty string
        }

        /**
         * Create a random string with a certain length for usage in URL query strings
         * @param int $charLength - the length of the random string - default is 100
         * @return string - returns a slug version of the generated random string that can be used inside an url
         */
        protected
        function createQueryCode(int $charLength = 100): string
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
        protected
        function generateSlug(string $string): string
        {
            return preg_replace('/[^A-Za-z\d-]+/', '-', $string);
        }

        /**
         * Make a readable string from a number of seconds
         * @param int $seconds - a number of seconds which should be converted to a readable string
         * @return string|null - a readable string of the time (fe 1 day instead of 86400 seconds)
         * @throws Exception
         */
        protected
        function readableTimestringFromSeconds(int $seconds = 0): ?string
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
        public
        function getNamesOfInputFields(): array
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
        protected
        function generateEmailSentErrorAlert(): void
        {
            $this->alert->setCSSClass('alert_dangerClass');
            $this->alert->setText($this->_('Email could not be sent due to possible wrong email configuration settings.'));
        }

        /**
         * Return placeholders for email pre-header to prevent showing up other text
         * The Litmus hack adds empty spaces after the mail placeholder to prevent the display of other text inside the
         * pre-header
         * @return string
         */
        protected
        function getLitmusHack(): string
        {
            return '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279;';
        }


        protected function getPreheaderStyle(): string
        {
            return 'display:none;font-size:1px; color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;';
        }

        /**
         * Generate an invisible pre-header text after the subject for an email
         * @param \ProcessWire\WireMail $mail
         * @return string
         */
        protected
        function generateEmailPreHeader(WireMail $mail): string
        {
            if ($mail->title) { // check if title property was set
                // generate an invisible div container
                return '<div id="preheader-text" style="' . $this->getPreheaderStyle() . '">' . $mail->title . $this->getLitmusHack() . '</div>';
            }
            return '';
        }

        /**
         * Get all Captcha questions as a PageArray
         * @return \ProcessWire\PageArray|null
         * @throws \ProcessWire\WireException
         */
        protected function getCaptchaQuestions(): array
        {
            // need to include all, otherwise pages under the admin tree will not be listed
            $questions = $this->wire('pages')->find('template=ff_question,include=all,status=published,status!=hidden');

            $questionArray = [];

            $numberOfQuestions = $questions->count;
            if (!$numberOfQuestions) return $questionArray;

            // just the case there are more than 25 pages, pick only 25 pages randomly to prevent a too large array
            if ($numberOfQuestions > 25) {
                $questions = $questions->findRandom(25);
            }

            // check if multi-language site
            $multilang = false;
            if ($this->wire('languages')) {
                $multilang = true;
                $lang = $this->wire('user')->language;
            }
            // create the multidim. array
            foreach ($questions as $key => $question) {

                if ($multilang) {
                    $title = $question->getLanguageValue($lang, 'title');
                    // check if field "title" contains a value in this language, otherwise take the value from the default lanugage
                    if (!$title) {
                        $title = $question->getLanguageValue('default', 'title');
                    }
                    $answers = explode("\n", $question->getLanguageValue($lang, 'ff_answers'));
                    // check if field "answers" contains values in this language, otherwise take the values from the default lanugage
                    if ($answers) {
                        $answers = explode("\n", $question->getLanguageValue('default', 'ff_answers'));
                    }
                    $successmsg = $question->getLanguageValue($lang, 'ff_successmsg');
                    $errormsg = $question->getLanguageValue($lang, 'ff_errormsg');
                    $placeholder = $question->getLanguageValue($lang, 'ff_placeholder');
                    $notes = $question->getLanguageValue($lang, 'ff_notes');
                    $description = $question->getLanguageValue($lang, 'ff_description');
                    $descriptionPosition = $question->getLanguageValue($lang, 'ff_descposition')->value;
                } else {
                    $title = $question->title;
                    $answers = explode("\n", $question->ff_answers);
                    $successmsg = $question->ff_successmsg;
                    $errormsg = $question->ff_errormsg;
                    $placeholder = $question->ff_placeholder;
                    $notes = $question->ff_notes;
                    $description = $question->description;
                    $descriptionPosition = $question->ff_descposition->value;
                }

                $questionArray[$key]['question'] = $title;
                $questionArray[$key]['answers'] = $answers;
                $questionArray[$key]['successMsg'] = $successmsg;
                $questionArray[$key]['errorMsg'] = $errormsg;
                $questionArray[$key]['placeholder'] = $placeholder;
                $questionArray[$key]['notes'] = $notes;
                $questionArray[$key]['description'] = $description;
                $questionArray[$key]['descriptionPosition'] = $descriptionPosition;

            }
            return array_filter($questionArray);

        }

    }
