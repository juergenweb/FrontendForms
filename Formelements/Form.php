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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ProcessWire\Field as Field;
use ProcessWire\HookEvent;
use ProcessWire\Language;
use ProcessWire\Module;
use ProcessWire\Page;
use ProcessWire\User;
use ProcessWire\Wire;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use ProcessWire\WireException;
use ProcessWire\WireMail;
use ProcessWire\WirePermissionException;
use Valitron\Validator;

use function ProcessWire\_n;
use function ProcessWire\wire as wire;
use function ProcessWire\wireClassName;
use function ProcessWire\wireMail;
use function ProcessWire\wirePopulateStringTags;

class Form extends Tag
{
    /* constants */
    public const FORMMETHODS = ['get', 'post']; // array that holds allowed action methods (get, post)

    /* properties */
    protected string|int|bool $preventJumpToForm = false;
    protected string|int $load_time = ''; // the time, when the form was loaded
    protected array $storedFiles = []; // array that holds all files (including overwritten filenames)
    protected string $doubleSubmission = ''; // value hold by the double form submission session
    protected string $defaultRequiredTextPosition = 'top'; // the default required text position
    protected array $formElements = []; //array that contains all elements of a form element as objects
    protected array $formErrors = []; // holds the array containing all form errors after submission
    protected FormValueStore $valueStore; // collects and exposes submitted form values
    protected FormElementsFinder $elementsFinder; // searches/filters/counts entries in $formElements
    protected bool $showForm = true; // show the form on the page
    protected bool $ipCheckPassed = true; // result of the one-time IP blacklist check done in the constructor
    protected string $visitorIP = ''; // the IP of the visitor who is visiting this page
    protected string $langAppendix = ''; // string, which will be appended to multi-lang config fields inside the db
    protected string|int $useDoubleFormSubmissionCheck = 1; // Enable checking of multiple submissions
    protected string|int|bool $useCSRFProtection = 1; // Enable/disable CSRF-Protection
    protected string $general_desc_position = 'afterInput'; // The position of the input field description -> beforeLabel, afterLabel or afterInput
    protected bool $preventGetFileUploadWarning = false;

    // NOTE: $question and $answers below are legacy properties that are never assigned
    // anywhere in this class (dead code carried over from before the question_array/
    // CaptchaManager refactor). Left untouched here since they are outside the scope
    // of the CAPTCHA extraction - candidates for removal in a follow-up cleanup.
    protected string|null $question = ''; // the question as string
    protected array|null $answers = []; // all acceptable answers as an array

    protected string|int|bool $useAriaAttributes = true; // use accessibility attributes
    // Mail properties - only needed if FrontendForms will be used to send emails
    protected MailPlaceholderRegistry $mailPlaceholders; // holds placeholder values for use in emails
    protected MailTemplateRenderer $mailTemplateRenderer; // renders the HTML email template and preheader
    protected string $defaultDateFormat = 'Y-m-d'; // the default format for date strings
    protected string $defaultTimeFormat = 'H:i a'; // the default format for time strings
    protected string $receiverAddress = ''; // the email address of the receiver of the mails
    protected string $mail_subject = ''; // the subject for a mail sent after form validation
    protected FileUploadHandler $fileUploadHandler; // handles storage of validated uploaded files
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

    protected array $formFieldConditions = [];


    /**
     * Multi-step form
     */
    protected MultiStepController $stepController; // coordinates multi-step form state and slicing
    protected string $modulePath = '';

    /* objects */
    protected Page $page; // the current page object, where the form is integrated
    protected Alert $alert; // alert box
    protected RequiredTextHint $requiredHint; // hint to inform that all required fields have to be filled out
    protected Wrapper $formElementsWrapper; // the wrapper object over all form elements
    protected User $user; // the user, who views the form (the page)
    protected Language $userLang; // the language object of the user/visitor

    protected CaptchaManager $captchaManager; // coordinates CAPTCHA type, config, question pool and field
    protected CaptchaQuestionRepository $captchaQuestionRepository; // loads CAPTCHA questions from the database
    protected Progressbar $ajaxProgressbar; // the progressbar for AJAX form submission

    //protected CustomRules $customRules;
    /**
     * Every form must have an id. You can set it custom via the constructor - otherwise a random ID will be
     * generated. The id will be taken for further automatic id generation of the input fields
     * @throws WireException
     */
    public function __construct(string $id)
    {
        parent::__construct();

        $this->load_time = time(); // set the load time

        $this->initMailCollaborators();
        $this->initUserAndLanguage();
        $this->initAjaxConfig();
        $this->initPageAndLanguageSupport();
        $this->initCollaborators($id);
        $this->initSecurityAndCoreAttributes($id);
        $this->initElementTags();
        $this->initProgressbarStyling();
        $this->initPlaceholdersAndCaptchaHooks();
        $this->initPageJsCssFlags();

        $this->modulePath = $this->wire('config')->paths->siteModules . 'FrontendForms/';

        new CustomRules($this);
    }

    /**
     * Set up the mail placeholder registry and the mail template renderer,
     * including the email template path taken from the module configuration.
     * @return void
     */
    private function initMailCollaborators(): void
    {
        $this->mailPlaceholders = new MailPlaceholderRegistry();
        $this->mailTemplateRenderer = new MailTemplateRenderer($this, $this->mailPlaceholders);
        // set the path to the email template from the module config
        $this->mailTemplateRenderer->init($this->frontendforms['input_emailTemplate']);
    }

    /**
     * Set the current user and (if multi-language is active) the current site language id.
     * @return void
     */
    private function initUserAndLanguage(): void
    {
        // set the current user
        $this->user = $this->wire('user');

        if ($this->wire('languages')) {
            // set the id of the current site language
            $this->site_language_id = $this->user->language->id;
        }
    }

    /**
     * Apply the Ajax submission and Ajax progressbar visibility settings from the module configuration.
     * @return void
     */
    private function initAjaxConfig(): void
    {
        // set Ajax form submission according to the configuration settings
        if (array_key_exists('input_ajaxformsubmission', $this->frontendforms)) {
            $this->setSubmitWithAjax((int)$this->frontendforms['input_ajaxformsubmission']);
        }

        // set show/hide progressbar during Ajax form submission according to the configuration settings
        if (array_key_exists('input_hideProgressBar', $this->frontendforms)) {
            $this->showProgressbar = !(($this->frontendforms['input_hideProgressBar'] == '1'));
        }
        $this->showProgressbar($this->showProgressbar);
    }

    /**
     * Set the current page and, if the LanguageSupport module is installed and a
     * language is active for the visitor, the language object plus its db field appendix.
     * @return void
     */
    private function initPageAndLanguageSupport(): void
    {
        // set the current page
        $this->page = $this->wire('page');

        // check if LanguageSupport module is installed and multi-language is enabled
        if ($this->wire('modules')->isInstalled('LanguageSupport') && isset($this->wire('user')->language)) {
            $this->userLang = $this->user->language; // the language object
        }
        $this->setLangAppendix(); // set the appendix for multi-language module configuration fields (fe. __1012)
    }

    /**
     * Instantiate the UI helper objects and the domain collaborator objects
     * (CAPTCHA, file upload, multi-step, and value store handling).
     * @param string $id
     * @return void
     */
    private function initCollaborators(string $id): void
    {
        $this->alert = new Alert();
        $this->requiredHint = new RequiredTextHint();
        $this->formElementsWrapper = new Wrapper();
        $this->ajaxProgressbar = new Progressbar($id . '-form-submission-ajax');
        $this->stepController = new MultiStepController($this, $id);
        $this->captchaManager = new CaptchaManager($this);
        $this->captchaQuestionRepository = new CaptchaQuestionRepository(
            $this->wire('pages'),
            $this->wire('languages'),
            $this->wire('user')
        );
        $this->fileUploadHandler = new FileUploadHandler($this);
        $this->valueStore = new FormValueStore($this);
        $this->elementsFinder = new FormElementsFinder($this->formElements);
    }

    /**
     * Run the one-time IP blacklist check and set up the core form attributes
     * (method, action, id, name, HTML5 validation, CSS classes, messages,
     * security/timing settings, CAPTCHA type, and the default upload path).
     * @param string $id
     * @return void
     */
    private function initSecurityAndCoreAttributes(string $id): void
    {
        // set default properties
        $this->visitorIP = $this->wire('session')->getIP();
        $this->runIpBanCheck();
        $this->setAttribute('method', 'post'); // default is post
        // take care about url segments if enabled
        $this->segments = ($this->wire('input')->urlSegmentStr(true)) ?? '';

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
        $this->getFormElementsWrapper()->setAttribute(
            'id',
            $this->getAttribute('id') . '-formelementswrapper'
        ); // add id
        $this->getFormElementsWrapper()->setAttribute(
            'class',
            $this->frontendforms['input_wrapperFormElementsCSSClass']
        ); // add CSS class to the wrapper element
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
    }

    /**
     * Set the global HTML tags used for labels, descriptions, notes, and messages,
     * taking module-configured overrides (or CSS-framework-specific defaults) into account.
     * @return void
     */
    private function initElementTags(): void
    {
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
        if ($this->frontendforms['input_framework'] === 'bootstrap5.json') {
            $defaultMsgTag = 'div';
        }
        if ($this->frontendforms['input_framework'] === 'pico2.json') {
            $defaultMsgTag = 'small';
        }
        $messagesTag = (!empty($this->frontendforms['input_global_msg_tag'])) ? $this->frontendforms['input_global_msg_tag'] : $defaultMsgTag;
        $this->setMessageTag($messagesTag);
    }

    /**
     * Apply the CSS/tag/attribute styling for the steps progressbar and the Ajax progressbar,
     * adapting to the Bootstrap 5 framework where applicable.
     * @return void
     */
    private function initProgressbarStyling(): void
    {
        // 5) Steps-Progressbar
        $this->stepController->getProgressbar()->setCSSClass('progressbarClass');
        if ($this->frontendforms['input_framework'] === 'bootstrap5.json') {
            $this->stepController->getProgressbar()->setTag('div');
            $this->stepController->getProgressbar()->setAttribute('role', 'progressbar');
            $this->stepController->getProgressbar()->prepend('<div class="progress mb-4">')->append('</div>');
        }

        // 5) Ajax-Progressbar
        $this->ajaxProgressbar->setCSSClass('progressbarClass')->setTag('div');
        $this->ajaxProgressbar->setContent('<div class="progress-bar__indicator"></div>');
        $this->ajaxProgressbar->setAttribute('role', 'progressbar');
        $this->ajaxProgressbar->setAttribute('class', 'ajaxbar');
        if ($this->frontendforms['input_framework'] === 'bootstrap5.json') {
            $this->ajaxProgressbar->setAttribute('class', 'progress');
        }
    }

    /**
     * Create the general mail placeholders, apply the global description position
     * (including the CAPTCHA description position default), register the mail
     * template hooks, and load the CAPTCHA question pool.
     * @return void
     */
    private function initPlaceholdersAndCaptchaHooks(): void
    {
        // create and set all general placeholder variables
        $this->createGeneralPlaceholders();

        // set global description position according to the module configuration
        if (array_key_exists('input_descPosition', $this->frontendforms)) {
            $this->general_desc_position = $this->frontendforms['input_descPosition'];
            // set the global description position for the CAPTCHA description position as default value
            $this->captchaManager->config()->descriptionPosition = $this->general_desc_position;
        }

        // add a hook method to render mail templates before sending the mail
        $this->addHookBefore('WireMail::send', $this->mailTemplateRenderer, 'renderTemplate');
        // add a hook method after sending the mail to remove the session variable "templateloaded"
        $this->addHookAfter('WireMail::send', $this->mailTemplateRenderer, 'removeTemplateSession');

        // create the questions array for the simple text CAPTCHA
        $this->captchaManager->addQuestions($this->captchaQuestionRepository->getAll());
    }

    /**
     * Register this form's JS/CSS loading flags and field-condition/form-id bookkeeping
     * on the current page object, so the module's page-render hooks can pick them up.
     * @return void
     */
    private function initPageJsCssFlags(): void
    {
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
        if (!isset($this->page->field_conditions)) {
            $this->page->field_conditions = false;
        }

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
     * Disable the internal jump to the form container after form submission
     * @param bool $prevent
     * @return $this
     */
    public function preventJumpToForm(bool $prevent = true): self
    {
        $this->preventJumpToForm = $prevent;
        return $this;
    }

    /**
     * Get all values of all steps or of a certain step
     * @param int|null $stepNumber
     * @return array
     */
    public function getStepValues(int|null $stepNumber = null): array
    {
        return $this->stepController->getStepValues($stepNumber);
    }

    /**
     * Get the value of a specific form field of a multi-step form as stored inside the session of a multi-step form
     * Enter the form field name attribute as parameter to get the value of the field
     * @param string $name
     * @return string|null
     */
    public function getStepValueByName(string $name): ?string
    {
        return $this->stepController->getStepValueByName($name);
    }

    /**
     * Add the markup for a custom progress bar
     * This disables the default progressbar
     * @param string $customProgressbar
     * @return $this
     */
    public function setCustomProgressbar(string $customProgressbar): self
    {
        $this->stepController->setCustomProgressbar($customProgressbar);
        return $this;
    }

    /**
     * Get the current step number
     * @return int
     */
    public function getCurrentStepNumber(): int
    {
        return $this->stepController->getCurrentStepNumber();
    }

    /**
     * Get the number of total steps inside this form
     * @return int
     */
    public function getTotalSteps(): int
    {
        return $this->stepController->getTotalSteps();
    }

    /**
     * Get the progress bar object for the multi-step form
     * @return Progressbar
     */
    public function getStepsProgressbar(): Progressbar
    {
        return $this->stepController->getProgressbar();
    }

    /**
     * Show or hide the "Step x of y" text on multi-step form steps
     * @param bool $showStep
     * @return $this
     */
    public function showStepOf(bool $showStep = true): self
    {
        $this->stepController->setShowStepsOf($showStep);
        return $this;
    }

    /**
     * Show or hide the progressbar on multi-step form steps
     * @param bool $showStepProgressbar
     * @return $this
     */
    public function showStepsProgressbar(bool $showStepProgressbar = true): self
    {
        $this->stepController->setShowStepsProgressbar($showStepProgressbar);
        return $this;
    }


    /**
     * Disable/enable the display of a warning alert, if request methos GET is choosen by using a file upload field in the form
     * By default, a warning message will be displayed
     * @param bool $prevent
     * @return $this
     */
    public function setPreventGetFileUploadWarning(bool $prevent): self
    {
        $this->preventGetFileUploadWarning = $prevent;
        return $this;
    }

    /**
     * Get the value of prevetGetFileUploadWarning
     * @return bool
     */
    public function getPreventGetFileUploadWarning(): bool
    {
        return $this->preventGetFileUploadWarning;
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
     * @return void
     */
    public function setAjaxMessage(string $ajaxmsg): void
    {
        $this->frontendforms['input_ajaxMsg'] = trim(
            $ajaxmsg !== '' ? $ajaxmsg : $this->_('Please be patient... the form will be validated!')
        );
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
        if (!is_null($question)) {
            $this->captchaManager->addQuestion($question, $answers ?? [], $options);
        }
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
        $this->captchaManager->config()->position = [
            $ref_field_name => in_array($pos, ['before', 'after'], true) ? $pos : 'after'
        ];
        return $this;
    }

    /**
     * Get the CAPTCHA position if a valid reference field was set
     * @return array|null
     */
    protected function getCaptchaPosition(): array|null
    {
        $position = $this->captchaManager->config()->position;
        if (!empty($position) && is_array($position)) {
            $firstKey = array_key_first($position);
            if (!$this->getFormelementByName($firstKey)) {
                return null;
            }
        }

        return $position ?: null;
    }

    /**
     * Add a success message to the Captcha after successful validation
     * @param string $successmsg
     * @return $this
     */
    public function setCaptchaSuccessMsg(string $successmsg): self
    {
        $this->captchaManager->config()->successMsg = $successmsg;
        return $this;
    }

    /**
     * Add a placeholder to the Captcha input
     * @param string $placeholder
     * @return $this
     */
    public function setCaptchaPlaceholder(string $placeholder): self
    {
        $this->captchaManager->config()->placeholder = $placeholder;
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
        $this->captchaManager->config()->removeLabel = true;
        $this->captchaManager->config()->useLabelAsPlaceholder = $usePlaceholder;
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
        $this->captchaManager->config()->showValueOnSameQuestionAgain = $show;
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
        $this->general_desc_position = match($pos) {
            'beforeLabel', 'afterLabel', 'afterInput' => $pos,
            default => throw new \InvalidArgumentException("Invalid description position: {$pos}"),
        };

        return $this;
    }

    /**
     * Create a new mail instance of a given custom mail module if set
     * Otherwise a new WireMail object will be instantiated
     * This method is only for other modules based on FrontendForms
     * @param string|null $class
     * @return WireMail|WireMailPostmark|WireMailPostmarkApp
     * @throws WireException
     */
    //protected function newMailInstance(string|null $class = null): WireMail|WireMailPostmark|WireMailPostmarkApp|WireMailSmtp|WireMailPHPMailer
    protected function newMailInstance(string|null $class = null): WireMailPostmarkApp|WireMail|WireMailPostmark
    {

        // if $class is null, set WireMail() object by default
        if (is_null($class)) {
            return new WireMail();
        }

        // just to play safe - check if the given module is installed first
        if (!$this->wire('modules')->getModuleID($class)) {
            return new WireMail();
        }

        // create a new instance of the given module
        switch ($class) {
            case ('WireMailPostmark'):
            case ('WireMailPostmarkApp'):
                return $this->wire('mail')->new();
                break;
            case ('WireMailSmtp'):
                return $this->wire('mail')->new();
            case ('WireMailPHPMailer'):
                return $this->wire("modules")->get("WireMailPHPMailer");
            default:
                return new WireMail();
        }
    }

    /**
     * Get all files that were uploaded and stored after successful validation
     * @return array
     *
     */
    public function getUploadedFiles(): array
    {
        return $this->fileUploadHandler->getUploadedFiles();
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
        return (bool) $this->frontendforms['input_html5_validation'];
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
        $useDoubleFormSubmissionCheck = FormHelper::sanitizeValueToInt($useDoubleFormSubmissionCheck); // sanitize to int

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
        $this->useCSRFProtection = FormHelper::sanitizeValueToInt($csrf);
    }

    /**
     * Get whether CSRF protection is currently enabled for this form
     * @return bool
     */
    public function getCSRFProtection(): bool
    {
        return (bool)$this->useCSRFProtection;
    }

    /**
     * Method to disable some methods if form is used inside an iframe on a different domain (crossdomain)
     * @return void
     * @throws WireException
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

    /**
     * @deprecated Use FormHelper::createQueryCode() instead. Kept as a thin
     * forwarding method for backward compatibility with external code
     * still calling Form::createQueryCode().
     * @param int $charLength - the length of the random string - default is 100
     * @return string - returns a slug version of the generated random string that can be used inside an url
     */
    public static function createQueryCode(int $charLength = 100): string
    {
        return FormHelper::createQueryCode($charLength);
    }

    /**
     * @deprecated Use FormHelper::getSeoMaestro() instead. Kept as a thin
     * forwarding method for backward compatibility with external code
     * still calling Form::getSeoMaestro().
     * @return Field|null
     */
    public static function getSeoMaestro(): ?Field
    {
        return FormHelper::getSeoMaestro();
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
        $this->mailTemplateRenderer->includeMailTemplate($mail, $this->frontendforms['input_emailTemplate']);
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
                if ($uploadfield instanceof InputFile) {
                    $fields[] = $uploadfield;
                }
            }
        }
        return $fields;
    }

    /**
     * Render the mail template: replace placeholders and use HTML email template if set
     * @param HookEvent $event
     * @return Module|wire|WireArray|WireData
     * @throws DOMException
     * @throws WireException
     * @throws WirePermissionException
     */

    public function renderTemplate(HookEvent $event): Module|Wire|WireArray|WireData
    {
        return $this->mailTemplateRenderer->renderTemplate($event);
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
        MailTemplateRenderer::setBody($mail, $body, $mailModule);
    }

    /**
     * This method prevents the multiple embedding of the email template if there are multiple forms on one page.
     * @return void
     * @throws WireException
     */
    public function removeTemplateSession(): void
    {
        $this->mailTemplateRenderer->removeTemplateSession();
    }

    /**
     * Load a template file from the given path including php code and output it as a string
     * @param string $templatePath - the path to the template that should be rendered
     * @return string - the HTML template
     */
    protected function loadTemplate(string $templatePath): string
    {
        return $this->mailTemplateRenderer->loadTemplate($templatePath);
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
        // fall back to the plain, default-language value (not just straight
        // to the hardcoded default) if no language-specific override
        // exists - otherwise, on a multi-language-enabled site where only
        // the plain "input_dateformat" was ever configured (no per-language
        // override), the admin's configured format would be skipped
        // entirely in favor of the hardcoded 'Y-m-d' fallback.
        $format = $this->frontendforms[$fieldName]
            ?? $this->frontendforms['input_dateformat']
            ?? $this->defaultDateFormat;
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
        // same reasoning as getDate() above
        $format = $this->frontendforms[$fieldName]
            ?? $this->frontendforms['input_timeformat']
            ?? $this->defaultTimeFormat;
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
        $this->mailPlaceholders->set($placeholderName, $placeholderValue);
        return $this;
    }

    /**
     * Remove a placeholder by its name from the placeholder array if it is present
     * @param string $placeholderName
     * @return void
     */
    public function removePlaceholder(string $placeholderName): void
    {
        $this->mailPlaceholders->remove($placeholderName);
    }

    /**
     * Get all placeholder variables and their values
     * For usage in body template of emails
     * @return array
     */
    public function getMailPlaceholders(): array
    {
        return $this->mailPlaceholders->all();
    }

    /**
     * Get the value of a certain placeholder by its name
     * @param string $placeholderName
     * @return string
     */
    public function getMailPlaceholder(string $placeholderName): string
    {
        return $this->mailPlaceholders->get($placeholderName);
    }





    /**
     * Get all included classes of the form fields
     * For usage in body template of emails
     * @return array
     */
    protected function getFormFieldClasses(): array
    {
        return $this->elementsFinder->getFormFieldClasses();
    }

    /**
     * Check if an input field with a specific name is present the current form (but not if it has a value)
     * @param string $fieldName
     * @return bool
     */
    public function formfieldExists(string $fieldName): bool
    {
        return $this->elementsFinder->formfieldExists($fieldName);
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
        if ($checkPrefix) {
            $name = $this->createElementName($name);
        }
        return $this->elementsFinder->getFormelementByName($name);
    }

    /**
     * Get the position of a certain form element inside the form elements array
     * This returns the number of the key
     * @param $element
     * @return int|string|void
     */
    public function getFormElementsPosition($element)
    {
        return $this->elementsFinder->getFormElementsPosition($element);
    }

    /**
     * Get all elements of the form that are an object of a specific class
     * Returns an array containing all objects of the given class (e.g., all Button elements)
     * @param string $class
     * @return array
     */
    public function getFormElementsByClass(string $class): array
    {
        return $this->elementsFinder->getFormElementsByClass($class);
    }

    /**
     * Count how many elements of a given class are present in the form
     * @param string $className
     * @return int
     */
    public function formContainsElementByClass(string $className): int
    {
        return $this->elementsFinder->formContainsElementByClass($className);
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
        return $this->elementsFinder->removeMultipleEntriesByClass($className);
    }

    /**
     * Get all form element objects of a given class as an array
     * @param string $className
     * @return array
     */
    public function getElementsbyClass(string $className): array
    {
        return $this->elementsFinder->getElementsbyClass($className);
    }

    /**
     * Get the position of a certain element inside the formElements array by its name
     * @param string $nameAttribute
     * @return int|string
     */
    public function getElementPositionByName(string $nameAttribute)
    {
        return $this->elementsFinder->getElementPositionByName($nameAttribute);
    }

    /**
     * Return the names of all input fields inside a form as an array
     * @return array
     */
    public function getNamesOfInputFields(): array
    {
        return $this->elementsFinder->getNamesOfInputFields();
    }



    /**
     * Output the value of multilang fields from the module configuration
     * @param string $fieldName
     * @param array|null $modulConfig
     * @param int|null $lang_id
     * @return string
     */
    protected function getLangValueOfConfigField(
        string     $fieldName,
        array|null $modulConfig = null,
        int|null   $lang_id = null
    ): string {
        $modulConfig = (is_null($modulConfig)) ? $this->frontendforms : $modulConfig;
        $langAppendix = (is_null($lang_id)) ? $this->langAppendix : '__' . $lang_id;
        $fieldNameLang = $fieldName . $langAppendix;
        if (isset($modulConfig[$fieldNameLang])) {
            return $modulConfig[$fieldNameLang] != '' ? $modulConfig[$fieldNameLang] : $modulConfig[$fieldName];
        }
        return $modulConfig[$fieldName];
    }

    /**
     * Set a custom upload path for uploaded files
     * If no path is selected, then the files will be stored inside the dir of this page in site/assets/files
     * @param string $path_to_folder
     * @return Form
     */
    public function setUploadPath(string $path_to_folder): self
    {
        $this->fileUploadHandler->setUploadPath($path_to_folder);
        return $this;
    }

    /**
     * Get the upload path for files
     * @return string
     */
    public function getUploadPath(): string
    {
        return $this->fileUploadHandler->getUploadPath();
    }

    /**
     * Run (or re-run) the IP blacklist check and store its result in
     * $this->ipCheckPassed / $this->showForm.
     *
     * Called once from the constructor, and again from useIPBan() /
     * testIPBan() - both of those methods change an input the check
     * depends on (whether the ban is enabled, or the visitor IP being
     * simulated) after construction, so without re-running the check
     * here, calling either method would silently have no effect on the
     * already-computed result from construction time.
     * @return void
     */
    private function runIpBanCheck(): void
    {
        $ipBlacklistGuard = new IPBlacklistGuard($this->wire('input')->post, $this, $this->alert);
        $this->ipCheckPassed = $ipBlacklistGuard->check(
            (bool) $this->frontendforms['input_useIPBan'],
            $this->frontendforms['input_preventIPs'],
            $this->visitorIP
        ); // show or hide the form depending on the IP ban
        $this->showForm = $this->ipCheckPassed;
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
            // re-run the check with the simulated IP - otherwise this
            // method would silently have no effect, since the check
            // already ran once in the constructor with the real visitor IP
            $this->runIpBanCheck();
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
        $this->frontendforms['input_useIPBan'] = FormHelper::sanitizeValueToInt($enabled);
        // re-run the check with the updated setting - otherwise this
        // method would silently have no effect, since the check already
        // ran once in the constructor with the config-default setting
        $this->runIpBanCheck();
    }

    /**
     * Disable/enable and set type of captcha on per-form base
     * @param string $captchaType
     * @return void
     */
    protected function setCaptchaType(string $captchaType): void
    {
        $this->frontendforms['input_captchaType'] = $captchaType;
        $this->captchaManager->setType($captchaType);
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
     * @deprecated The category is now set internally by CaptchaManager::setType(). Kept only
     * for backward compatibility in case external code calls it directly.
     */
    protected function setCaptchaCategory(string $captchaType): void
    {
        $this->captchaManager->config()->category = AbstractCaptchaFactory::getCaptchaTypeFromClass($captchaType);
    }

    /**
     * Get the captcha category
     * @return string
     */
    public function getCaptchaCategory(): string
    {
        return $this->captchaManager->config()->category;
    }

    /**
     * Get the captcha object for further manipulations
     * @return object|null
     */
    protected function getCaptcha(): object|null
    {
        return $this->captchaManager->getCaptchaObject();
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
        $this->frontendforms['input_logFailedAttempts'] = FormHelper::sanitizeValueToInt($logFailedAttempts);
    }

    /**
     * Whether logging of blocked visitor's IP is enabled for this form
     * @return bool
     */
    public function getLogFailedAttempts(): bool
    {
        return (bool) ($this->frontendforms['input_logFailedAttempts'] ?? false);
    }

    /**
     * Convert post-values to a string
     * @param bool $showButtonValues
     * @return string
     * @throws WireException
     */
    public function getValuesAsString(bool $showButtonValues = false): string
    {
        return $this->valueStore->getValuesAsString($showButtonValues);
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
        $useFormElementsWrapper = FormHelper::sanitizeValueToInt($useFormElementsWrapper); // sanitize to int
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
     * Can be used to overwrite the default success message or to disable the output of a success message
     * @param string|bool $successMsg : entering empty string or false will disable the output of the success message
     * @return void
     */
    public function setSuccessMsg(string|bool $successMsg): void
    {

        if ($successMsg === '' || $successMsg === true) {
            $successMsg = $this->_('Thank you for your message.');
        } elseif ($successMsg === false) {
            $successMsg = '';
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
        $this->captchaManager->config()->errorMsg = $errormsg;
        return $this;
    }

    /**
     * Method to overwrite the default CAPTCHA required error message
     * @param string $errormsg
     * @return $this
     */
    public function setCaptchaRequiredErrorMsg(string $errormsg): self
    {
        $this->captchaManager->config()->requiredErrorMsg = $errormsg;
        return $this;
    }

    /**
     * Method to overwrite the default CAPTCHA notes
     * @param string $notes
     * @return $this
     */
    public function setCaptchaNotes(string $notes): self
    {
        $this->captchaManager->config()->notes = $notes;
        return $this;
    }

    /**
     * Method to add a description text to the CAPTCHA input field
     * @param string $desc
     * @return $this
     */
    public function setCaptchaDescription(string $desc): self
    {
        $this->captchaManager->config()->description = $desc;
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
            $this->captchaManager->config()->descriptionPosition = $pos; // set new position property
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
     * Set the error message if errors occur after form submission
     * Can be used to overwrite the default error message or to disable the output of a success message
     * @param string|bool $errorMsg : Entering an empty string or false will disable the output of the error message
     * @return void
     */
    public function setErrorMsg(string|bool $errorMsg): void
    {
        if ($errorMsg === '' || $errorMsg === true) {

            $errorMsg = $this->_('Sorry, some errors occur. Please check your inputs once more.');
        } elseif ($errorMsg === false) {
            $errorMsg = '';
        }
        $this->frontendforms['input_alertErrorText'] = trim($errorMsg);
    }



    /**
     * Get the value of a specific formfield after form submission by its name
     * Can be used to send fe this value via email to a recipient or store it inside the db
     * You can enter pure name or name attribute including form prefix
     * @param string $name - the name attribute of the input field
     * @return string|array|null
     * @throws WireException
     */
    public function getValue(string $name): string|array|null
    {
        return $this->valueStore->getValue($name);
    }

    /**
     * Add the form id as prefix to the name attribute
     * @param string $name - the name attribute of the element
     * @return string - returns the name attribute including the form id as prefix
     */
    public function createElementName(string $name): string
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
     * @throws WireException
     */
    public function getValues(bool $buttonValue = false): array|null
    {
        return $this->valueStore->getValues($buttonValue);
    }

    /**
     * Same as getValues() but outputs the labels too
     * @param bool $buttonValue
     * @return array
     * @throws WireException
     */
    public function getValuesWithLabels(bool $buttonValue = false): array
    {
        return $this->valueStore->getValuesWithLabels($buttonValue);
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
     * Get the array of stored files (including overwritten filenames), if any.
     * @return array
     */
    public function getStoredFiles(): array
    {
        return $this->storedFiles;
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
        $this->frontendforms['input_useHoneypot'] = FormHelper::sanitizeValueToInt($honeypot);
    }





    /**
     * Method to set the form method (get or post)
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $method = strtolower($method);
        $allowedMethods = ['post', 'get'];
        if (in_array($method, $allowedMethods)) {
            $this->setAttribute('method', $method);
        }
        return $this;
    }

    /**
     * Get all slices which contain the form elements of each step
     * @param Button|ResetButton|null $submitButton
     * @param Button|ResetButton|null $resetButton
     * @return array
     */
    protected function getSlices(Button|ResetButton|null $submitButton = null, Button|ResetButton|null $resetButton = null): array
    {
        return $this->stepController->getSlices($submitButton, $resetButton);
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

        // if it is a multi-step form -> remove all not used form elements from each step
        if ($this->stepController->hasSteps()) {

            // add a special data-set attribute to mulit-step forms
            $this->setAttribute('data-multistep', 'true');

            // get the submit button (and reset button if present) element
            $submitButton = null;
            $resetButton = null;

            // grab the submit and reset button from all buttons
            $buttons = $this->getFormelementsByClass('Button');
            $resetButtons = $this->getFormelementsByClass('ResetButton');
            $allButtons = array_merge($buttons, $resetButtons);

            $buttonsNumber = 0;
            $submitButtonArray = [];
            $resetButtonArray = [];

            if ($allButtons) {
                foreach ($allButtons as $button) {

                    //extract the submit button
                    if ($button->getAttribute('type') === 'submit') {
                        $buttonsNumber = $buttonsNumber + 1;
                        $submitButton = $button;
                        $submitButtonArray[] = $button;
                    }

                    // extract the reset button if present
                    if ($button->getAttribute('type') === 'reset') {
                        $buttonsNumber = $buttonsNumber + 1;
                        $resetButton = $button;
                        $resetButtonArray[] = $button;
                    }
                }
            }

            // only allow 1 submit button and remove all others
            if (count($submitButtonArray) > 1) {
                // remove all submit buttons except the last
                foreach ($submitButtonArray as $key => $button) {
                    if ($key !== array_key_last($submitButtonArray)) {
                        $this->remove($button);
                    }
                }
            }

            // only allow 1 reset button and remove all others
            if (count($resetButtonArray) > 1) {
                // remove all submit buttons except the last
                foreach ($resetButtonArray as $key => $button) {
                    if ($key !== array_key_last($resetButtonArray)) {
                        $this->remove($button);
                    }
                }
            }

            // get all slices for all steps
            $slices = $this->getSlices($submitButton, $resetButton);

            // check if URL contains a query string
            $action = $this->wire('input')->queryStringClean(['validNames' => [$this->getID() . '-step'], 'sanitizeName' => 'string', 'sanitizeValue' => 'string']);
            if ($action) {

                $stepNumber = explode('=', $action)[1];
                $stepNumber = $this->wire('sanitizer')->int($stepNumber, ['min' => 0, 'max' => count($slices) + 1]);
                $this->stepController->setCurrentStepNumber($stepNumber);

                if (count($slices) == $stepNumber) {
                    $this->stepController->setLastStep(true);
                }
                if ($stepNumber == 1) {
                    $this->stepController->setFirstStep(true);
                }

                // set form action with query string
                $this->setAttribute('action', $this->wire('input')->url(['withQueryString' => true]));

            } else {
                $this->stepController->setFirstStep(true);
            }

            // get total number of steps
            $this->stepController->setTotalSteps(count($slices));

            // cut the array after the last step
            $fields = array_slice($this->formElements, 0, ($slices[$this->stepController->getTotalSteps() - 1]['end']) + 1);

            // get the first and last input field and ignore all others (fieldset, markup,..)
            $inputFields = [];

            foreach ($fields as $key => $field) {

                if (is_subclass_of($field, 'FrontendForms\Inputfields')) {
                    if ($this->stepController->isLastStep()) {
                        if (!$field->getRemoveFromLastStep()) {
                            $inputFields[$key] = $field;
                        }
                    } else {
                        $inputFields[$key] = $field;
                    }

                }
            }

            $this->stepController->setFirstElement(reset($inputFields));
            $this->stepController->setLastElement(end($inputFields));

            // slice the array
            $slice = $slices[$this->stepController->getCurrentStepNumber()];
            $offSet = $slice['start'];
            $length = $slice['end'] - $slice['start'];
            $formElements = array_slice($this->getFormElements(), $offSet, $length + 1);

            // add the previous button on all steps except the first one
            if (!$this->stepController->isFirstStep()) {
                $prevButton = new Button('prev');
                $prevButton->setAttribute('value', $this->_('Previous'));
                $prevButton->setAttribute('type', 'button');
                $prevButton->setAttribute('class', 'ff-prev-button');

                $anchor = ($this->preventJumpToForm) ? '' : '#' . $this->getID() . '-allwrapper';
                $location = $this->page->url . '?' . $this->getID() . '-step=' . ($this->stepController->getCurrentStepNumber() - 1) . $anchor;
                $prevButton->setAttribute('data-prev', $location);
                $prevButton->setAttribute('data-formid', $this->getID());
                $formElements[] = $prevButton;
            }

            if (!is_null($submitButton)) {
                $formElements[] = $submitButton;
            }
            if (!is_null($resetButton)) {
                $formElements[] = $resetButton;
            }

            // set the form elements array new
            $this->formElements = $formElements;


            $lastElementKey = $this->getSlices()[$this->stepController->getTotalSteps() - 1]['end'];

            if (!$this->stepController->isLastStep()) { // all steps except the last

                // remove CAPTCHA on all steps (except the last)
                $this->disableCaptcha();

                // set redirect URL to the next step
                $anchor = ($this->preventJumpToForm) ? '' : '#' . $this->getID() . '-allwrapper';
                $redirectUrl = $this->page->url . '?' . $this->getID() . '-step=' . ($this->stepController->getCurrentStepNumber() + 1) . $anchor;
                $this->setRedirectURL($redirectUrl);

                // set the submit button text to "next" (except on the last step)
                if ($submitButton) {
                    $submitButton->setAttribute('value', $this->_('Next'));
                    $submitButton->setAttribute('class', 'ff-next-button');
                    $submitButton->setAttribute('data-next', $this->getRedirectURL());
                    $submitButton->setAttribute('data-formid', $this->getID());
                }


            } else { // last step

                // add a special data attribute, which is required for HTML 5 validation
                $this->setAttribute('data-step', 'last');

                // make all form elements visible with a special markup
                $lastStepElements = [];
                foreach ($this->formElements as $key => $field) {

                    if ($key <= $lastElementKey) {
                        if (is_subclass_of($field, 'FrontendForms\Inputfields')) {
                            if ($field->getRemoveFromLastStep()) {
                                unset($this->formElements[$key]);
                            } else {
                                $field->useFieldWrapper(true);
                                $field->useInputWrapper(true);
                            }

                        }
                    } else {
                        $lastStepElements[] = $field;
                    }
                }

                $this->stepController->setLastStepElements($lastStepElements);
            }

            /*****
             * Values
             */
            $stepValues = []; // default field values

            // get all form values that have been set for this step
            $formValues = $this->wire('session')->get($this->getID() . '-values');

            // loop through all session values from this step and add the values to the appropriate form fields
            if ($formValues) {

                if ($this->stepController->isLastStep()) {

                    if ($_POST) {
                        $values = $_POST;
                    } else {
                        $values = [];
                        foreach ($formValues as $key => $array) {
                            foreach ($array as $name => $value) {
                                $values[$name] = $value;
                            }
                        }
                    }
                    $stepValues = $values;
                } else {
                    if ($_POST) {
                        $stepValues = $_POST;
                    } else {
                        if (array_key_exists($this->stepController->getCurrentStepNumber(), $formValues)) {
                            $stepValues = $formValues[$this->stepController->getCurrentStepNumber()];
                        }
                    }
                }
            }

            if ($stepValues) {

                foreach ($stepValues as $name => $value) {

                    $field = $this->getFormelementByName($name);
                    if ($field) {

                        switch ($field->className()) {
                            case 'InputCheckboxMultiple':
                            case 'InputRadioMultiple':
                            case 'SelectMultiple':

                                foreach ($field->getOptions() as $option) {
                                    if (is_array($value)) {
                                        if (in_array($option->getAttribute('value'), $value, true)) {
                                            if ($field->className() === 'SelectMultiple') {
                                                $option->setAttribute('selected', 'selected');
                                            } else {
                                                $option->setAttribute('checked', 'checked');
                                            }
                                        }
                                    } else {
                                        if ($option->getAttribute('value') === $value) {
                                            if ($field->className() === 'SelectMultiple') {
                                                $option->setAttribute('selected', 'selected');
                                            } else {
                                                $option->setAttribute('checked', 'checked');
                                            }
                                        }
                                    }
                                };
                                break;
                            case 'InputCheckbox':
                            case 'InputRadio':
                            case 'Select':
                                if ($field->className() === 'Select') {
                                    if (array_key_exists($field->getAttribute('name'), $stepValues)) {
                                        foreach ($field->getOptions() as $option) {
                                            if ($option->getAttribute('value') === $stepValues[$field->getAttribute('name')]) {
                                                $option->setAttribute('selected', 'selected');
                                            }
                                        };
                                    }
                                } else {
                                    // compare the actual value, not just whether the name
                                    // exists - matters when multiple InputCheckbox/InputRadio
                                    // fields share the same name (e.g. a manually built radio
                                    // group), where only the one matching the stored value
                                    // should be marked checked.
                                    if (
                                        array_key_exists($field->getAttribute('name'), $stepValues)
                                        && $field->getAttribute('value') === $stepValues[$field->getAttribute('name')]
                                    ) {
                                        $field->setAttribute('checked', 'checked');
                                    }
                                }
                                break;
                            default:
                                if (is_array($value)) {
                                    $value = implode(',', $value);
                                }
                                $field->setAttribute('value', $value);
                        }
                    }
                }
            }
        }

        // Add the multi-question array for the simple question CAPTCHA
        if ($this->captchaManager->hasQuestions()) {
            $this->captchaManager->pickRandomQuestion();
        }

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
                        $input[$name] = FormHelper::simplifyMultiFileArray($_FILES[$name]);
                    } else {
                        $input[$name] = $_FILES[$name];
                    }
                }
            }
        }

        // instantiate the Captcha field if set
        $useCaptcha = $this->captchaManager->isActive();

        if ($useCaptcha) {
            $this->captchaManager->buildField($this->getID(), $input);
        }

        // instantiates the FormSecurity object
        $validation = new FormSecurity($input, $this, $this->alert);
        $honeypotGuard = new HoneypotGuard($input, $this, $this->alert);
        $refererGuard = new RefererGuard($input, $this, $this->alert);

        if (!$this->passesSubmissionGuards($validation, $refererGuard, $formElements)) {
            return false;
        }

        /* START PROCESSING THE FORM */

        //add honeypotfield to the array because it will be rendered afterwards
        if ($this->frontendforms['input_useHoneypot']) {
            $formElements[] = $honeypotGuard->createField(
                $this->createElementName(HoneypotGuard::FIELD_NAME),
                $this->useInputWrapper,
                $this->useFieldWrapper
            );
        }
        //add captcha to the array because it will be rendered afterwards
        if ($useCaptcha) {

            // special treatment for SimpleQuestionCaptcha
            if (wireClassName($this->captchaManager->getCaptchaObject()) === 'SimpleQuestionCaptcha') {
                // add custom answers if present
                if ($this->answers) {
                    $this->getCaptcha()->setCaptchaValidValue($this->answers);
                }

                // set the appropriate validator
                if ($this->getCaptcha()->getCaptchaValidValue()) {

                    $validValue = $this->getCaptcha()->getCaptchaValidValue();

                    // overwrite the default value with the one from the session for random question if present
                    if (array_key_exists($this->getID() . '-random_key', $_POST)) {
                        $questionPool = $this->captchaManager->getQuestionPool();
                        $randomKey = $_POST[$this->getID() . '-random_key'];
                        if (array_key_exists($randomKey, $questionPool)) {
                            $prev_question = $questionPool[$randomKey];
                            $validValue = $prev_question['answers'];
                            if (array_key_exists('errorMsg', $prev_question)) {
                                // set the custom error message
                                $errormsg = ($prev_question['errorMsg'] !== '') ? $prev_question['errorMsg'] : $this->_('The answer is wrong!');
                                // set the valid values from the session
                            } else {
                                $errormsg = $this->captchaManager->config()->errorMsg ?? $this->_('The answer is wrong!');
                            }
                        } else {
                            // an invalid/tampered random_key must never result in an empty
                            // comparison list, since validateTextComparison() treats an empty
                            // list as an automatic pass - fail closed with a value that can
                            // never match a real submitted answer instead
                            $validValue = [uniqid('invalid-random-key-', true)];
                            $errormsg = $this->_('The answer is wrong!');
                        }
                    } else {
                        $errormsg = ($this->captchaManager->config()->errorMsg !== '') ? $this->captchaManager->config()->errorMsg : $this->_('The answer is wrong!');
                    }
                    $this->captchaManager->getField()->setRule('compareTexts', $validValue)->setCustomMessage($errormsg);
                }

            }

            if ($this->getCaptchaType() === 'SliderCaptcha') {

                // add the servers side validation of the slider CAPTCHA
                $this->captchaManager->getField()->setRule('checkSliderCaptcha', $input[$this->getID() . '-xPos'], $input[$this->getID() . '-yPos'], $this->getID())->setCustomMessage($this->_('The Slider-Captcha was not solved correctly.'));
            }

            $formElements[] = $this->captchaManager->getField();
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
                    if (array_key_exists($file_upload_name, $_FILES)) {
                        if ($element->getMultiple()) {
                            $sanitizedValues[$file_upload_name] = $this->fileUploadHandler->reArrayFiles($_FILES[$file_upload_name]);
                        } else {
                            $sanitizedValues[$file_upload_name] = [$_FILES[$file_upload_name]];
                        }
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

            if (is_null($fieldValue) && (!$element instanceof InputCheckbox) && (!$element instanceof InputCheckboxMultiple) && (!$element instanceof InputRadio) && (!$element instanceof InputRadioMultiple)) { // exclude checkboxes and radios because they are allowed to have no value
                $element->removeAllRules();
            }

        }

        // NOTE: file/ZIP content validation (allowed extensions, size limits, ZIP depth,
        // forbidden extensions, etc.) is handled by FileLogic/ZipLogic, which read directly
        // from $_FILES via FileHelper. No temp-dir preparation is needed here anymore.

        $v = new Validator($sanitizedValues);


        foreach ($formElements as $element) {
            // run validation only if there is at least one validation rule set
            if (count($element->getRules()) > 0) {

                $addRequiredFileValidation = false;
                // check if field is file upload field and has required validator added
                if ($element instanceof InputFile) {
                    if (array_key_exists('required', $element->getRules())) {
                        // add fileRequired validator if it is not present
                        if (!array_key_exists('fileRequired', $element->getRules())) {
                            $element->setRule('fileRequired');
                            $addRequiredFileValidation = true;
                        }
                    }
                }
                // add required validation to be the first
                $rules = FormHelper::putRequiredOnTop($element->getRules());
                $cl = [];
                foreach ($rules as $validatorName => $parameters) {

                    $v->rule($validatorName, $element->getAttribute('name'), ...$parameters['options']);

                    // Add custom error message text if present
                    if (isset($parameters['customMsg'])) {
                        $v->message($parameters['customMsg']);
                    } else {
                        // check if field has fileRequired validation
                        if ($addRequiredFileValidation && $validatorName == 'fileRequired') {
                            // check if required validator has a custom error message added
                            if (array_key_exists('customMsg', $rules['required'])) {
                                $v->message($rules['required']['customMsg']);
                            }
                        }
                    }

                    if (isset($parameters['customFieldName'])) {
                        $v->label($parameters['customFieldName']);
                        $cl[] = $parameters['customFieldName'];
                    } else {
                        if ($element->getLabel()->getText()) {
                            // use the label if present, otherwise use the name attribute
                            if (!count($cl)) {
                                $v->label($element->getLabel()->getText());
                            }
                        }
                    }

                }
            }

            // add honeypot validation if honeypot field is included
            if ($this->frontendforms['input_useHoneypot']) {
                if ($element->getAttribute('name') === $this->createElementName(HoneypotGuard::FIELD_NAME)) {
                    $v->rule(
                        'length',
                        $element->getAttribute('name'),
                        0
                    )->message($honeypotGuard->getMessage());
                }
            }

            // add captcha validation if captcha field is included
            if ($useCaptcha) {

                if ($element->getAttribute('name') == $this->createElementName('captcha')) {

                    // exclude this CAPTCHA types from using the checkCaptcha rule
                    $nonCheckCaptchaTypes = ['SimpleQuestionCaptcha'];

                    if (!in_array($this->getCaptchaType(), $nonCheckCaptchaTypes)) {
                        // check if the custom error message has been set
                        if ($this->captchaManager->config()->errorMsg) {
                            $v->rule(
                                'checkCaptcha',
                                $element->getAttribute('name'),
                                $this->wire('session')->get('captcha_' . $this->getID())
                            )->message($this->captchaManager->config()->errorMsg);
                        } else {
                            $v->rule(
                                'checkCaptcha',
                                $element->getAttribute('name'),
                                $this->wire('session')->get('captcha_' . $this->getID())
                            )->label($this->_('The CAPTCHA'));
                        }
                    }
                }
            }
        }

        $this->valueStore->setValues();

        // re-sync the "{fieldname}value" mail placeholders with the now-
        // populated, actually submitted values - add() only sets them once
        // at form-build time, before any submitted value exists on the
        // field, so they need to be refreshed here (right before a
        // success/error email might be sent) to actually reflect what the
        // visitor submitted. Same checkbox/radio exclusion as in add().
        foreach ($formElements as $element) {
            if (!is_subclass_of($element, 'FrontendForms\Inputfields')) {
                continue;
            }
            $elementClassName = $element->className();
            if (
                $elementClassName === 'InputCheckbox'
                || is_subclass_of($element, 'FrontendForms\InputCheckbox')
                || $elementClassName === 'InputRadio'
                || is_subclass_of($element, 'FrontendForms\InputRadio')
                || $element instanceof InputFile
            ) {
                continue;
            }
            $fieldValue = $element->getAttribute('value');
            if (is_array($fieldValue)) {
                $fieldValue = implode(',', $fieldValue);
            }
            $this->setMailPlaceholder(
                $element->getAttribute('name') . 'value',
                (string) $fieldValue
            );
        }

        if ($v->validate()) {

            $this->validated = '1';

            $this->alert->setAttribute('data-ffsuccess', 'true');
            $this->alert->setAttribute('data-formid', $this->getID());
            $this->alert->setAttribute('id', $this->getID() . '-alert');
            $this->alert->setCSSClass('alert_successClass');
            $this->alert->setText($this->getSuccessMsg());
            $this->wire('session')->remove('attempts');
            $this->wire('session')->remove('submitted');
            // remove attempt session
            $this->wire('session')->remove('doubleSubmission-' . $this->getID());
            // remove the session for checking for double form submission
            $this->showForm = false;
            // check if files were uploaded and store them inside the chosen folder
            $this->fileUploadHandler->storeUploadedFiles($formElements);
            // remove session added by matchUser or matchEmail validation rule if present
            $this->wire('session')->remove($this->getAttribute('id') . '-email');
            $this->wire('session')->remove($this->getAttribute('id') . '-username');

            // finally, add the files including the overwritten filenames to the array
            if ($this->storedFiles) {
                $this->fileUploadHandler->setUploadedFiles($this->storedFiles);
            }

            /***********************************
             * check if it is a multi-step form
             * ********************************/
            if ($this->stepController->hasSteps()) {
                if (!$this->stepController->isLastStep()) { // run if it is not the final step

                    // 1) save all values inside a session

                    if ($this->wire('session')->get($this->getID() . '-values')) {
                        // remove the old values from the array
                        unset($formValues[$this->stepController->getCurrentStepNumber()]);
                        $newValues = [$this->stepController->getCurrentStepNumber() => $this->getValues()];
                        // add new values to the array
                        $values = array_replace($formValues, $newValues);
                        $this->wire('session')->set($this->getID() . '-values', $values);
                    } else {
                        // session does not exist -> set session for the first time
                        $this->wire('session')->set($this->getID() . '-values', [$this->stepController->getCurrentStepNumber() => $this->getValues()]);
                    }
                    return false; // set to false to prevent the execution of the code inside the isValid() method
                } else {
                    // last step: remove the session
                    $this->wire('session')->remove($this->getID() . '-values');
                }
            }
            /*** Multi-step form end */

            return true;


        } else {
            // set error alert
            $this->wire('session')->set('errors', '1');
            $this->formErrors = $v->errors();

            // set data-attribute for validation status for later usage with JS
            $this->setAttribute('data-valid', 'false');

            // check if a CAPTCHA is enabled
            if ($this->getCaptchaType() != 'none') {

                $captchaName = $this->getID() . '-captcha';
                // if Captcha value was valid -> add it to the captcha_value property
                if ($this->getCaptchaType() === 'SimpleQuestionCaptcha') {

                    if (!array_key_exists($captchaName, $this->formErrors)) {
                        // captcha was valid

                        // check if it is multi-question
                        if (array_key_exists($this->getID() . '-random_key', $_POST)) {

                            $questionPool = $this->captchaManager->getQuestionPool();
                            $randomKey = $_POST[$this->getID() . '-random_key'];
                            $prev_question = $questionPool[$randomKey] ?? [];
                            if (array_key_exists('successMsg', $prev_question)) {
                                $this->captchaManager->getField()->setSuccessMessage($prev_question['successMsg']);
                            } else {
                                // output the global success message if set
                                $this->captchaManager->getField()->setSuccessMessage($this->captchaManager->config()->successMsg);
                            }

                            // check if the current question is the same as before -> otherwise remove the CAPTCHA value on multi question CAPTCHA
                            if ($this->captchaManager->config()->showValueOnSameQuestionAgain) {
                                // question is not the same as before -> delete the value
                                if ($prev_question['question'] != $this->question) {
                                    $this->captchaManager->getField()->setAttribute('value', '');
                                }
                            } else {// false
                                $this->captchaManager->getField()->setAttribute('value', '');
                            }

                        } else {
                            // single question CAPTCHA
                            // add the value back to this field on success if there is only a single question set (not an array)
                            $this->captchaManager->getField()->setAttribute('value', $_POST[$this->getID() . '-captcha']);
                            $this->captchaManager->getField()->setSuccessMessage($this->captchaManager->config()->successMsg);

                        }

                    } else {
                        // entered CAPTCHA value was wrong
                        $this->captchaManager->getField()->setAttribute('value', '');
                    }
                } else {
                    // all other CAPTCHA types
                    if (!array_key_exists($captchaName, $this->formErrors)) {
                        $this->captchaManager->getField()->setSuccessMessage($this->captchaManager->config()->successMsg);
                    }
                    // CAPTCHA value will be deleted in any way
                    $this->captchaManager->getField()->setAttribute('value', '');
                }
            }

            $this->alert->setAttribute('id', $this->getID() . '-alert');
            $this->alert->setCSSClass('alert_dangerClass');
            $this->alert->setText($this->getErrorMsg());

            // add a max attempts warning message to the error message
            if ($this->getMaxAttempts() && isset($this->wire('session')->attempts)) {

                $attemptDiff = $this->getMaxAttempts() - $this->wire('session')->attempts;

                if ($attemptDiff <= 3) {
                    $plural = $this->_('attempts');
                    $singular = $this->_('attempt');
                    $attempts = $this->_n($singular, $plural, $attemptDiff);
                    $attemptWarningText = '<br>' . sprintf(
                            $this->_('You have %s %s left until you will be blocked due to security reasons.'),
                            $attemptDiff,
                            $attempts
                        );
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
                    $this->alert->setText(sprintf(
                        $this->_('This is failed attempt number %s. This is your last attempt to send the form. After that you will be blocked due to security reasons.'),
                        ($this->wire('session')->attempts)
                    ));
                }
            } else {
                // remove the session for attempts if set to 0
                $this->wire('session')->remove('attempts');
            }
            return false;
        }

        /* END PROCESSING THE FORM */
    }

    /**
     * Check whether the current request passes all guards required before the form
     * submission may be processed.
     *
     * The guards are checked in order: the form was actually submitted (and no other
     * form on the same page), the submission happened within the allowed time window,
     * the maximum number of attempts was not exceeded, no double submission is
     * detected, the CSRF token is valid, and the Referer header (if present) points
     * to this host.
     *
     * CSRF and Referer failures terminate the request via die(), matching the
     * original behaviour, rather than returning false, since these are treated as
     * active attacks rather than ordinary invalid submissions.
     *
     * @param FormSecurity $validation The form security helper running the checks.
     * @param RefererGuard $refererGuard The Referer-header guard (defense in depth on top of CSRF).
     * @param array $formElements The form elements used for the submission time-window check.
     * @return bool True if every guard passed and processing may continue.
     * @throws WireException
     */
    private function passesSubmissionGuards(
        FormSecurity $validation,
        RefererGuard $refererGuard,
        array $formElements
    ): bool {
        // 1) check if this form was submitted and no other form on the same page
        if (!$validation->thisFormSubmitted()) {
            return false;
        }
        // add a validation class to the form after it was submitted
        $this->setCSSClass('formClassValidated'); // add the CSS class

        // 2) check if form was submitted in time range
        if (!$validation->checkTimeDiff($formElements)) {
            return false;
        }

        // 3) check if max attempts were reached
        if (!$validation->checkMaxAttempts($this->wire('session')->attempts)) {
            return false;
        }

        // 4) check for double form submission
        if (!$validation->checkDoubleFormSubmission($this, $this->useDoubleFormSubmissionCheck)) {
            return false;
        }

        // 5) Check for CSRF attack
        if (!$validation->checkCSRFAttack($this->getCSRFProtection(), $this->getAttribute('method'))) {
            // CSRF attack
            die();
            // live a great life and die() gracefully.
        }

        // 6) Defense-in-depth check on top of CSRF: reject only if the
        // Referer header is present but points to a different host
        if (!$refererGuard->check()) {
            die();
        }

        return true;
    }

    /**
     * Add the input wrapper to all fields of this form in general
     *
     * Also applies this setting to fields that were already added via
     * add() before this call - unless a field received its own explicit
     * setting via a direct useInputWrapper() call on that field, in which
     * case the field's own choice is respected and left untouched.
     *
     * @param bool $useInputWrapper
     * @return void
     */
    public function useInputWrapper(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;

        foreach ($this->formElements as $field) {
            if (is_subclass_of($field, 'FrontendForms\Inputfields') && !$field->isInputWrapperExplicitlySet()) {
                $field->setInputWrapperFromForm($useInputWrapper);
            }
        }
    }

    /**
     * Add the field wrapper to all fields of this form in general
     *
     * Also applies this setting to fields that were already added via
     * add() before this call - unless a field received its own explicit
     * setting via a direct useFieldWrapper() call on that field, in which
     * case the field's own choice is respected and left untouched.
     *
     * @param bool $useFieldWrapper
     * @return void
     */
    public function useFieldWrapper(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;

        foreach ($this->formElements as $field) {
            if (is_subclass_of($field, 'FrontendForms\Inputfields') && !$field->isFieldWrapperExplicitlySet()) {
                $field->setFieldWrapperFromForm($useFieldWrapper);
            }
        }
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
        return (int) $this->frontendforms['input_maxAttempts'];
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
     * Change the tag of an given element
     * @param object $element
     * @param string $tagProperty
     * @return void
     */
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
     * Insert the CAPTCHA field into the formElements array at the correct
     * position (right before the submit button, or a custom position if
     * one was set via the API), and set up SimpleQuestionCaptcha-specific
     * warnings (missing question/answers), label-as-placeholder, and
     * label-removal config.
     * @param int $refKey - the array key of the first Button element, used as the default insertion point
     * @param int $firstButtonPos - the current position of the first Button element
     * @return int - the (possibly updated) position of the first Button element, needed by later positioning steps
     */
    private function insertCaptchaField(int $refKey, int $firstButtonPos): int
    {
        if ($this->getCaptchaType() != 'none') {

            // position in the form fields array to insert
            $captchaPosition = $refKey;

            if (wireClassName($this->captchaManager->getCaptchaObject()) === 'SimpleQuestionCaptcha') {

                // add custom question as label if present
                if ($this->question) {
                    $this->captchaManager->getField()->setLabel($this->question);
                }

                // check if a question and accepted answers have been set
                $missing_msg = [];
                // output a warning message if question for question CAPTCHA is missing
                if (!$this->captchaManager->getField()->getLabel()->getText()) {
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
            if (((wireClassName($this->captchaManager->getCaptchaObject()) === 'SimpleQuestionCaptcha') && (!$missing_msg)) || (wireClassName($this->captchaManager->getCaptchaObject()) !== 'SimpleQuestionCaptcha')) {

                // insert the captcha input field after the last input field
                $this->formElements = array_merge(
                    array_slice($this->formElements, 0, $captchaPosition),
                    array($this->captchaManager->getField()),
                    array_slice($this->formElements, $captchaPosition)
                );

                // re-index the formElements array
                $this->formElements = array_values($this->formElements);

            }

            // remove label and set it as placeholder if set
            if ($this->captchaManager->config()->useLabelAsPlaceholder && ($this->captchaManager->getField() instanceof InputText)) {
                $this->captchaManager->getField()->setAttribute('placeholder', $this->captchaManager->getField()->getLabel()->getText());
                $this->captchaManager->getField()->setLabel(''); // remove the label tag
            }
            // remove Label if set
            if ($this->captchaManager->config()->removeLabel) {
                $this->captchaManager->getField()->setLabel('');
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
                    FormHelper::repositionArrayElement($this->formElements, $captchaPosition, $new_pos);
                }
            }

        } else {
            // no Captcha is used
            if ($this->getElementsbyClass('Button')) {
                $firstButtonPos = key($this->getElementsbyClass('Button')[0]);
            }
        }

        return $firstButtonPos;
    }

    /**
     * Create and add all the hidden fields a form needs for its own
     * internal bookkeeping: an optional Ajax-redirect URL, the slider
     * CAPTCHA's x/y position placeholders, the CSRF token, the
     * double-submission token, the form id (to detect which form on the
     * page was submitted), the encrypted load timestamp (for min/max time
     * checks), and the random question-pool key for question CAPTCHAs.
     * @param string $tokenName
     * @param string $tokenValue
     * @return void
     */
    private function createHiddenFormFields(string $tokenName, string $tokenValue): void
    {
        // create hidden Ajax redirect input if set
        // this value can be grabbed afterwards via JavaScript to make a JS redirect
        if ($this->getSubmitWithAjax()) {
            if ($this->ajaxRedirect) {
                $ajaxredirectField = new InputHidden('ajax_redirect');
                $url = $this->ajaxRedirect;
                if ($this->preventJumpToForm) {
                    // remove internal anchor
                    $url = explode("#", $url);
                    $url = $url[0];
                }
                $ajaxredirectField->setAttribute('value', $url);
                $this->add($ajaxredirectField);
            }
        }

        // only for the slider captcha -> add hidden fields for the x and y position
        if ($this->getCaptchaType() === 'SliderCaptcha') {

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
            $hiddenField4->setAttribute('value', FormHelper::encryptDecrypt((string) $this->load_time));
            $this->add($hiddenField4);
        }

        // if a random question array is set, add the random item key to this field
        if (!is_null($this->captchaManager->getCurrentQuestionIndex())) {
            $hiddenField5 = new InputHidden('random_key');
            $hiddenField5->setAttribute('value', $this->captchaManager->getCurrentQuestionIndex());
            $this->add($hiddenField5);
        }
    }

    /**
     * Build and inject the final-step summary table row markup (with an
     * edit link to jump back to the step where the field was filled out)
     * for one form element, when displaying the last step of a multi-step
     * form's review screen.
     * @param object $element
     * @param int $key
     * @param int $firstStep
     * @param int $lastStep
     * @return void
     */
    private function renderFinalStepTableRow(object $element, int $key, int $firstStep, int $lastStep): void
    {
        $name = $element->getAttribute('name');

        $values = [];

        if (!$_POST) {

            // set final values from session
            $finalValues = $this->wire('session')->get($this->getID() . '-values');

            foreach ($finalValues as $fv) {

                foreach ($fv as $name => $v) {
                    if (is_array($v)) {
                        $values[$name] = implode(', ', $fv[$name]);
                    } else {
                        $values[$name] = $v;
                    }
                }
            };

        } else {
            foreach ($_POST as $name => $v) {
                if (is_array($v)) {
                    $values[$name] = implode(', ', $_POST[$name]);
                } else {
                    $values[$name] = $v;
                }
            }
        }

        if ($element->className() !== 'InputHidden' && $element->getAttribute('name') != $this->getID() . '-seca') {

            // show/hide inputfield depending on, if there is an error or not
            if (array_key_exists($element->getAttribute('name'), $this->formErrors)) {
                $hideClass = '';
            } else {
                $hideClass = 'ff-final-list-hidden ';
            }

            /*
             * Create the list
             */
            if ($key <= $lastStep) {

                // table start
                $markup = '';

                if ($key === $firstStep) {

                    $markup .= '<div class="' . $this->getCSSClass('responsiveTableClass') . '">';

                    $tableStyling = [
                        'none.json' => 'ff-table',
                        'pico2.json' => 'ff-table',
                        'uikit3.json' => 'uk-table-small uk-table-divider',
                        'bootstrap5.json' => 'table-sm'
                    ];

                    if (array_key_exists($this->frontendforms['input_framework'], $tableStyling)) {
                        $tableStyleClass = $tableStyling[$this->frontendforms['input_framework']];
                    } else {
                        $tableStyleClass = 'ff-table';
                    }

                    $markup .= '<table id="' . $this->getID() . '-final-step-table" class="' . $this->getCSSClass('tableClass') . ' ' . $tableStyleClass . ' final-list-table">';

                }

                $hideWrapperOpen = '<tr id="' . $element->getAttribute('id') . '-hidden-wrapper" class="ff-hidden-wrapper ' . $hideClass . '"><td colspan="3">';

                $hideWrapperClose = '</td></tr>';
                if ($this->formFieldConditions && array_key_exists($element->getAttribute('name'), $this->formFieldConditions)) {
                    $hideWrapperClose = '</td></tr></tbody>';
                }

                // if field wrapper is disabled -> enable it for making the form element invisible
                if (!$element->getUsageOfFieldWrapper()) {
                    $element->useFieldWrapper(true);
                }

                // create edit link element
                $editLink = '<td class="ff-final-list-edit ' . $this->getCSSClass('finaltableEditClass') . '">';
                $editLink .= '<a id="' . $this->getID() . '-' . $element->getAttribute('id') . '-edit" class="ff-edit-link" href="#" rel="nofollow" data-element="' . $element->getAttribute('id') . '-hidden-wrapper"';
                $editLink .= ' data-close="' . $this->_('close') . '"';
                $editLink .= ' data-edit="' . $this->_('edit') . '"';
                $editLink .= '>' . $this->_('edit') . '</a>';
                $editLink .= '</td>';

                if ($this->formFieldConditions && array_key_exists($element->getAttribute('name'), $this->formFieldConditions)) {
                    $markup .= '<tbody id="' . $element->getAttribute('id') . '-tablebody" class="tbodywrapper">';
                    // replace the default container class with the tbodywrapper class
                    $element->setConditionContainerClass('tbodywrapper');
                }

                $markup .= '<tr id="' . $this->getID() . '-' . $this->getFormElementsPosition($element) . '">';
                if ($element->getCustomListLabel()) {
                    $labelText = $element->getCustomListLabel();
                } else {
                    $label = $element->getLabel();
                    $labelText = $label->getText();
                }
                $markup .= '<td class="ff-final-list-label ' . $this->getCSSClass('finaltableLabelClass') . '">' . htmlspecialchars((string) $labelText, ENT_QUOTES, 'UTF-8') . '</td>';
                if (array_key_exists($element->getAttribute('name'), $values)) {
                    $valText = $values[$element->getAttribute('name')];
                } else {
                    $valText = '';
                }

                // special treatment for password fields - do not display passwords in plain text
                if ($element->className() === 'InputPassword' || $element->getAttribute('type') === 'password') {
                    if (array_key_exists($element->getAttribute('name'), $values)) {
                        $valText = '';
                        for ($i = 1; $i <= strlen($values[$element->getAttribute('name')]); $i++) {
                            $valText .= '*';
                        }
                    } else {
                        $valText = '';
                    }
                }

                $markup .= '<td class="ff-final-list-value">' . htmlspecialchars((string) $valText, ENT_QUOTES, 'UTF-8') . '</td>';
                $markup .= $editLink;

                $markup .= '</tr>' . $hideWrapperOpen;

                if ($key === $lastStep) {
                    $hideWrapperClose .= '</table></div>';
                }

                $element->getFieldWrapper()->prepend($markup)->append($hideWrapperClose);
            }
        }
    }

    /**
     * Render the form markup (including alerts if present) on the frontend
     * @return string
     * @throws WireException
     * @throws Exception
     */
    public function render(): string
    {
        $this->redirectAfterValidationIfNeeded();
        $this->applySliderCaptchaPageFlag();

        $out = $this->initOutputWrapper();
        $out = $this->appendAjaxWarningMarkup($out);
        $out = $this->processFormElementsForRenderSetup($out);
        $this->appendIpBlockedWarningIfNeeded();

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
            $firstButtonPos = $this->insertCaptchaField($refKey, $firstButtonPos);

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

                FormHelper::repositionArrayElement($this->formElements, $privacyElements[0], $newPos);
                if (array_key_exists(1, $privacyElements)) {
                    $newPos = array_key_last($this->formElements) - 1;
                    FormHelper::repositionArrayElement($this->formElements, $privacyElements[1] - 1, $newPos);
                }
            }

            // create the new array of inputfields only to position the honeypot field in between
            $inputfieldKeys = [];

            // only for the slider captcha
            if ($this->getCaptchaType() === 'SliderCaptcha') {

                $xPos = (float)rand() / (float)getrandmax();
                $yPos = (float)rand() / (float)getrandmax();

                $this->wire('session')->set($this->getID() . '-captcha_x', $xPos);
                $this->wire('session')->set($this->getID() . '-captcha_y', $yPos);

                // add x and y positions as data attributes for JavaScript usage later on
                $this->captchaManager->getField()->setAttribute('data-x', $xPos);
                $this->captchaManager->getField()->setAttribute('data-y', $yPos);
            }

            foreach ($this->formElements as $key => $element) {
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {

                    // exclude hidden input fields - add only visible fields
                    if ($element->className() !== 'InputHidden') {
                        $inputfieldKeys[] = $key;
                    }

                }

            }

            if (($this->frontendforms['input_useHoneypot']) && ($inputfieldKeys)) {

                $honeypotGuard = new HoneypotGuard($this->wire('input')->post, $this, $this->alert);

                $honeypot = $honeypotGuard->createField(
                    $this->createElementName(HoneypotGuard::FIELD_NAME),
                    $this->useInputWrapper,
                    $this->useFieldWrapper
                );

                // if it is multistep form - add additional markup to seca field for displaying the list table properly
                if ($this->stepController->hasSteps() && $this->stepController->isLastStep()) {
                    if (!$honeypot->getUsageOfFieldWrapper()) {
                        $honeypot->useFieldWrapper(true);
                    }
                    $honeypot->getFieldWrapper()->prepend('<tr>')->append('</tr>');
                }

                $honeypotGuard->insertIntoElements($this->formElements, $inputfieldKeys, $honeypot, (bool) $this->stopHoneypotRotation);
            }

            $this->createHiddenFormFields($tokenName, $tokenValue);

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

            // Don't show step-related UI (progressbar, "Step X of Y") once
            // the form has been successfully submitted - $this->showForm
            // is set to false right after successful validation (see
            // ___isValid()), the same flag that already hides the actual
            // form fields further below. Without this check here, the
            // progressbar/step text would still render on the final step
            // even after a successful submission, alongside the success
            // message.
            if ($this->stepController->hasSteps() && $this->showForm) {
                if ($this->stepController->showsStepsOf()) {
                    $out .= '<p class="ff-steps-of">' . sprintf($this->_('Step %s of %s'), $this->stepController->getCurrentStepNumber(), (int)$this->stepController->getTotalSteps()) . '</p>';
                }

                if ($this->stepController->getCustomProgressbar() === '') {

                    if ($this->stepController->showsStepsProgressbar()) {

                        $this->stepController->getProgressbar()->setAttribute('max', count($this->getSlices()));
                        $this->stepController->getProgressbar()->setAttribute('value', $this->stepController->getCurrentStepNumber());
                        if ($this->frontendforms['input_framework'] === 'bootstrap5.json') {
                            $percent = round($this->stepController->getCurrentStepNumber() * 100 / count($this->getSlices()));
                            $this->stepController->getProgressbar()->setAttribute('style', 'width:' . $percent . '%');
                        }
                        $out .= $this->stepController->getProgressbar()->render();

                    }
                } else {
                    $out .= $this->stepController->getCustomProgressbar();
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

                if ($this->stepController->hasSteps()) {

                    if (!$this->stepController->isFirstStep()) {

                        // first check if user is allowed to enter this step
                        // A user can only enter the next step if the previous step is valid

                        $formValues = $this->wire('session')->get($this->getID() . '-values') ?? [];

                        $key = $this->wire('input')->url(['withQueryString' => true]);
                        if ($this->stepController->getCurrentStepNumber() == 2) {
                            $key = '/';
                            // special treatment because first step can be reached with or without querystring
                            $keys = [
                                $this->wire('input')->url(['withQueryString' => true]),
                                $this->wire('page')->url,
                                '/?' . $this->getID() . '-step=1'
                            ];

                            foreach ($keys as $k) {
                                if (array_key_exists($k, $formValues)) {
                                    $key = $k;
                                    break;
                                }
                            }
                        }

                        if ($formValues) {

                            // check if the previous step number exists inside the formValues array -> otherwise redirect
                            $prevStep = $this->stepController->getCurrentStepNumber() - 1;
                            if (!array_key_exists($prevStep, $formValues)) {

                                // make redirect to the next step which should be filled out
                                $nextStepToFillOut = array_key_last($formValues) + 1;
                                $redirectURL = ($nextStepToFillOut === 1) ? $this->page->url : $this->page->url . '?' . $this->getID() . '-step=' . $nextStepToFillOut;
                                $this->wire('session')->redirect($redirectURL);
                            }
                        } else {
                            // no form values exist -> so redirect to the first step
                            $this->wire('session')->redirect($this->page->url);
                        }
                    }

                    if ($this->stepController->isLastStep()) {

                        if ($this->stepController->getLastStepListText()) {
                            $out .= $this->stepController->getLastStepListText();
                        }

                        // remove all non inputfields from the form fields array inside the final list

                        // get key number of the last element of the previous step
                        $cleanedFormElements = [];

                        $nonAllowed = [
                            'FieldsetClose',
                            'FieldsetOpen',
                            'Markup'
                        ];


                        $lastElement = $this->stepController->getLastStepElements()[0];
                        $lastKey = (array_search($lastElement, $this->formElements)) - 1;

                        foreach ($this->formElements as $key => $element) {

                            // non allowed objects in final list
                            if ($key <= $lastKey) {
                                if (!in_array($element->className(), $nonAllowed)) {
                                    $cleanedFormElements[] = $element;
                                }
                            } else {
                                $cleanedFormElements[] = $element;
                            }
                        }

                        foreach ($cleanedFormElements as $key => $element) {
                            if ($element->getAttribute('name') === $this->stepController->getFirstElement()->getAttribute('name')) {
                                $firstStep = $key;
                            }
                            if ($element->getAttribute('name') === $this->stepController->getLastElement()->getAttribute('name')) {
                                $lastStep = $key;
                            }
                        }

                        $this->formElements = $cleanedFormElements;

                    }
                }

                // collect all button elements inside the form
                if ($this->stepController->hasSteps()) {
                    $buttons = [];
                    foreach ($this->formElements as $key => $element) {
                        if ($element->className() === 'Button') {
                            $buttons[] = $element->getAttribute('name');
                        }
                    }
                    $firstButton = $buttons[0];
                    $lastButton = $buttons[count($buttons) - 1];
                }

                foreach ($this->formElements as $key => $element) {

                    // check if it multi-step form
                    if ($this->stepController->hasSteps() && $this->stepController->isLastStep()) {
                        $this->renderFinalStepTableRow($element, $key, $firstStep, $lastStep);
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

                        // check if the container has been overwritten
                        if (!is_null($element->getConditionContainerClass())) {
                            $conditions['container'] = $element->getConditionContainerClass();
                        }

                        $conditions = json_encode($conditions);
                        $element->setAttribute('data-conditional-rules', $conditions);

                    }

                    //create input ID as a combination of form id and input name
                    $oldId = $element->getAttribute('id');
                    $element->setAttribute('id', $this->getID() . '-' . $oldId);

                    // change the name attribute of the CSRF field
                    if ($element->getID() === $this->getID() . '-post_token') {
                        $element->setAttribute('name', $tokenName);
                    }

                    // enable/disable usage of Aria attributes
                    if (method_exists($element, 'useAriaAttributes')) {
                        $element->useAriaAttributes($this->useAriaAttributes);
                    }

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
                        // do not add the for attribute to InputRadioMultiple and InputCheckboxMultiple elements
                        if ((wireClassName($element) !== 'InputRadioMultiple') && (wireClassName($element) !== 'InputCheckboxMultiple')) {
                            $element->getLabel()->setAttribute('for', $element->getAttribute('id'));
                        }
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

                        // create progressbar and info text for form submission
                        $submitInfo = $this->ajaxProgressbar->render() . '<div class="ajax-submission-text">' . $this->frontendforms['input_ajaxMsg'] . '</div>';

                        if ($this->stepController->hasSteps() && $this->stepController->isLastStep()) {
                            if ($element->getAttribute('name') == $firstButton) {
                                if ($this->showProgressbar) {
                                    $formElements .= '<div id="' . $this->getID() . '-form-submission" class="progress-submission" style="display:none">' . $submitInfo . '</div>';
                                }
                            }
                        } else {
                            if ($key === $position) { // add it only before the first button inside the form
                                if ($this->showProgressbar) {
                                    $formElements .= '<div id="' . $this->getID() . '-form-submission" class="progress-submission" style="display:none;">' . $submitInfo . '</div>';
                                }
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

                    // add a button wrapper on multi-step form
                    if ($this->stepController->hasSteps() && $element->getAttribute('name') === $firstButton) {
                        $formElements .= '<div id="' . $this->getID() . '-button-wrapper" class="button-wrapper">';
                        // add additional class to submit button on last step
                        if ($element->hasAttribute('type') && $element->getAttribute('type') === 'submit') {
                            $element->setAttribute('class', 'ff-finalstep-submit');
                        }
                    }
                    // remove pattern attribute, if it is not allowed for the given input type
                    if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                        if ($element->hasAttribute('pattern') && !$element->patternAttributeAllowed()) {
                            $element->removeAttribute('pattern');
                        }
                    }

                    $formElements .= $element->render() . PHP_EOL;

                    if ($this->stepController->hasSteps() && $element->getAttribute('name') === $lastButton) {
                        $formElements .= '</div>';
                    }

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

            // closing wrapper over all elements
            $out .= '</div>';

        }

        return $out;
    }

    /**
     * Redirect the browser to the configured redirect URL if the form was
     * just successfully validated (and Ajax submission is not in use, since
     * that handles redirects on the client side instead).
     * @return void
     * @throws WireException
     */
    private function redirectAfterValidationIfNeeded(): void
    {
        if ($this->getRedirectURL() && $this->validated && !$this->getSubmitWithAjax()) {
            $this->wire('session')->redirect($this->getRedirectURL());
        }
    }

    /**
     * Flag the current page as using the Slider CAPTCHA (read by the
     * frontend JS/CSS loader) if that CAPTCHA type is configured.
     * @return void
     */
    private function applySliderCaptchaPageFlag(): void
    {
        if ($this->frontendforms['input_captchaType'] == 'SliderCaptcha') {
            $this->page->sliderCaptcha = true;
        }
    }

    /**
     * Build the opening "allwrapper" div that surrounds the whole rendered
     * form, including the data-preventjumptoform attribute.
     * @return string
     */
    private function initOutputWrapper(): string
    {
        $dataPrevent = ' data-preventjumptoform="false"';
        if ($this->preventJumpToForm) {
            $dataPrevent = ' data-preventjumptoform="true"';
        }
        return '<div id="' . $this->getID() . '-allwrapper"' . $dataPrevent . '>';
    }

    /**
     * If Ajax submission is enabled, append the "JavaScript disabled"
     * warning box and the Ajax wrapper div's opening tag to the given
     * output string, and set the data-submitajax attribute on the form.
     * @param string $out
     * @return string
     */
    private function appendAjaxWarningMarkup(string $out): string
    {
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
        return $out;
    }

    /**
     * Walk all form elements once to: detect field conditions (and flag the
     * page accordingly), detect file upload fields (setting the enctype
     * attribute and appending GET-method / non-last-step warnings to the
     * given output string), and normalize the label/description/notes/
     * message tags of every Inputfields element.
     * @param string $out
     * @return string
     */
    private function processFormElementsForRenderSetup(string $out): string
    {
        foreach ($this->formElements as $obj) {

            // check if the field contains a field condition
            if (($obj instanceof Element) && ($obj->containsConditions())) {
                $this->page->field_conditions = true;
                // add the condition to the formFieldsCondition rray
                $this->formFieldConditions[$obj->getAttribute('name')] = $obj->getConditions();
            }

            if ($obj instanceof InputFile) {
                $this->setAttribute('enctype', 'multipart/form-data');
                // check if request method is set to get
                $method = strtolower($this->getAttribute('method'));

                if ($method === 'get') {
                    if (!$this->getPreventGetFileUploadWarning()) {
                        // create a warning alert to inform the dev
                        $warningAlert = new Alert();
                        $warningAlert->setCSSClass('alert_warningClass');
                        $warningAlert->setText($this->_('Uploading files via GET request is not possible. Please use POST instead or remove the file upload field.'));
                        $out .= $warningAlert->render();
                    }
                } else {
                    if ($this->stepController->hasSteps() && !$this->stepController->isLastStep()) {
                        // create a warning alert to inform the dev
                        $warningAlert = new Alert();
                        $warningAlert->setCSSClass('alert_warningClass');
                        $warningAlert->setText($this->_('A file upload is only possible in the last step. Please move the file upload field(s) to the last step. Otherwise it would not work.'));
                        $out .= $warningAlert->render();
                    }
                }

            }

            if (is_subclass_of($obj, 'FrontendForms\Inputfields')) {
                // Label
                $this->changeElementTag($obj->getLabel(), $this->labeltag);
                $this->changeElementTag($obj->getDescription(), $this->desctag);
                $this->changeElementTag($obj->getNotes(), $this->notestag);
                $this->changeElementTag($obj->getErrorMessage(), $this->msgtag);
                $this->changeElementTag($obj->getSuccessMessage(), $this->msgtag);

                // Fields like InputCheckboxMultiple/InputRadioMultiple create
                // one separate sub-element (with its own, independent label)
                // per option - those labels need the same tag change too,
                // since they are not covered by $obj->getLabel() above.
                if (method_exists($obj, 'getOptions')) {
                    foreach ($obj->getOptions() as $option) {
                        if (is_subclass_of($option, 'FrontendForms\Inputfields')) {
                            $this->changeElementTag($option->getLabel(), $this->labeltag);
                        }
                    }
                }

            }
        }
        return $out;
    }

    /**
     * Set the "IP blocked" warning alert if the one-time IP blacklist check
     * from the constructor failed.
     *
     * NOTE: this must use $this->ipCheckPassed (not $this->showForm),
     * because showForm may have been set to false for an unrelated reason
     * since then (e.g. hiding the form after a successful submission) -
     * re-checking the blacklist here would just repeat the exact same
     * lookup already done in the constructor.
     * @return void
     */
    private function appendIpBlockedWarningIfNeeded(): void
    {
        if (!$this->ipCheckPassed) {
            $this->alert->setCSSClass('alert_warningClass');
            $this->alert->setText(sprintf(
                $this->_('We are sorry, but your IP address %s is on the list of forbidden IP addresses. Therefore the form will not be displayed. If you think your IP address is mistakenly on the list, please contact the administrator of the site.'),
                $this->visitorIP
            ));
            // do not display form to banned visitors
            $this->showForm = false;
        }
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
     * need to use it if you are creating the form by your own
     * @param Markup|Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field -
     *     the current form field which should be appended to the form
     * @param Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose|bool|null $otherfield -
     *     optional: another form field
     * @param bool $add_before - optional: current should be inserted before or after this (another) form field
     * @return void
     * @throws Exception
     */
    public function add(
        Markup|Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose    $field,
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose|null|bool $otherfield = null,
        bool                                                                 $add_before = false
    ): void {

        // add or remove wrapper divs on each form element
        if (is_subclass_of($field, 'FrontendForms\Inputfields')) {

            // apply the form-level wrapper settings to the field, unless
            // the field already received its own explicit setting via a
            // direct useInputWrapper()/useFieldWrapper() call - in that
            // case, the field's own choice is respected and left as-is.
            if (!$field->isInputWrapperExplicitlySet()) {
                $field->setInputWrapperFromForm($this->useInputWrapper);
            }
            if (!$field->isFieldWrapperExplicitlySet()) {
                $field->setFieldWrapperFromForm($this->useFieldWrapper);
            }

            // create a placeholder for the label of this field
            $fieldname = $field->getAttribute('name');
            $this->setMailPlaceholder($fieldname . 'label', $field->getLabel()->getText());

            $className = $field->className();
            $value = '';
            // special treatment for single checkbox and single radio - do not add the value by default to the placeholder
            if ($className !== 'InputCheckbox' && !is_subclass_of($field, 'FrontendForms\InputCheckbox') && $className !== 'InputRadio' && !is_subclass_of($field, 'FrontendForms\InputRadio')) {
                $fieldValue = $field->getAttribute('value');
                if (is_array($fieldValue)) {
                    $fieldValue = implode(',', $fieldValue);
                }
                $value = (string) $fieldValue;
            }
            $this->setMailPlaceholder($fieldname . 'value', $value);

        }


        // if the field is not a text element and not a markup, set the name attribute if not set before
        $elementsWithNoName = [
            'Markup',
            'FieldsetOpen',
            'FieldsetClose',
            'Progressbar'
        ];

        $className = $field->className();


        if ((!is_subclass_of($field, 'FrontendForms\TextElements')) && !in_array($className, $elementsWithNoName)) {

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
                throw new Exception(
                    "The reference field (argument 2) where you want to add this field before or after does not exist. Please check if you have written the name attribute correctly.",
                    1
                );
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
                        $this->formElements = array_merge(
                            array_slice($this->formElements, 0, $ref_position),
                            [$field],
                            array_slice($this->formElements, $ref_position)
                        );
                    }
                }
            }
        } else {
            // no other element is present -> so add it to formElements array as next element
            $this->formElements = array_merge(
                $this->formElements,
                [$field]
            ); // array must be numeric for honeypot field
        }

    }

    /**
     * Insert a form field before another form field
     * Can be used if you have not created the form by your own, but you need to add a new field to a created
     * formElements array at a certain position
     * @param Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field -
     *     the current form field
     * @param Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $before_field -
     *     the form field object before which the current form field object should be inserted
     * @return void
     * @throws Exception
     */
    public function addBefore(
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose      $field,
        Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $before_field
    ): void {
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
     * @param Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose $field -
     *     the current form field
     * @param Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $after_field -
     *     the form field object after which the current form field object should be inserted
     * @return void
     * @throws Exception
     */
    public function addAfter(
        Inputfields|Textelements|Button|FieldsetOpen|FieldsetClose      $field,
        Inputfields|Textelements|FieldsetOpen|FieldsetClose|Button|bool $after_field
    ): void {
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
    public function remove(object $field): void
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
    public function getMinTime(): int
    {
        return (int) $this->frontendforms['input_minTime'];
    }

    /**
     * Set the min time in seconds before the form should be submitted
     * @param int $minTime
     * @return $this
     */
    public function setMinTime(int $minTime): self
    {
        $this->frontendforms['input_minTime'] = $minTime;
        return $this;
    }

    /**
     * Get the max time value
     * @return int
     */
    protected function getMaxTime(): int
    {
        return (int) $this->frontendforms['input_maxTime'];
    }

    /**
     * Set the max time in seconds until the form should be submitted
     * @param int $maxTime
     * @return $this
     */
    public function setMaxTime(int $maxTime): self
    {
        $this->frontendforms['input_maxTime'] = $maxTime;
        return $this;
    }



    /**
     * Create a required hint text element if showTextHint is set to true
     * @param string $position - has to be 'top' or 'bottom'
     * @return string
     */
    private function renderRequiredText(string $position): string
    {
        if ($this->defaultRequiredTextPosition === $position) {
            return $this->requiredHint->render();
        }
        return ''; // return empty string
    }





    /**
     * Make a readable string from a number of seconds
     * @param int $seconds - a number of seconds which should be converted to a readable string
     * @return string|null - a readable string of the time (fe 1 day instead of 86400 seconds)
     * @throws Exception
     */
    protected function readableTimestringFromSeconds(int $seconds = 0): ?string
    {
        $then = new DateTime(date('Y-m-d H:i:s', 0));
        $now = new DateTime(date('Y-m-d H:i:s', $seconds));
        $interval = $then->diff($now);

        if ($interval->y >= 1) {
            $thetime[] = $interval->y . ' ' . _n(
                    $this->_('year'),
                    $this->_('years'),
                    $interval->y
                );
        }
        if ($interval->m >= 1) {
            $thetime[] = $interval->m . ' ' . _n(
                    $this->_('month'),
                    $this->_('months'),
                    $interval->m
                );
        }
        if ($interval->d >= 1) {
            $thetime[] = $interval->d . ' ' . _n(
                    $this->_('day'),
                    $this->_('days'),
                    $interval->d
                );
        }
        if ($interval->h >= 1) {
            $thetime[] = $interval->h . ' ' . _n(
                    $this->_('hour'),
                    $this->_('hours'),
                    $interval->h
                );
        }
        if ($interval->i >= 1) {
            $thetime[] = $interval->i . ' ' . _n(
                    $this->_('minute'),
                    $this->_('minutes'),
                    $interval->i
                );
        }
        if ($interval->s >= 1) {
            $thetime[] = $interval->s . ' ' . _n(
                    $this->_('second'),
                    $this->_('seconds'),
                    $interval->s
                );
        }

        return isset($thetime) ? implode(' ', $thetime) : null;
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

    /**
     * Return placeholders for email pre-header to prevent showing up other text
     * The Litmus hack adds empty spaces after the mail placeholder to prevent the display of other text inside the
     * pre-header
     * @return string
     */
    protected function getLitmusHack(): string
    {
        return $this->mailTemplateRenderer->getLitmusHack();
    }

    /**
     * Method for internal usage only
     * @return string
     */
    protected function getPreheaderStyle(): string
    {
        return $this->mailTemplateRenderer->getPreheaderStyle();
    }

    /**
     * Generate an invisible pre-header text after the subject for an email
     * @param WireMail $mail
     * @return string
     */
    protected function generateEmailPreHeader(WireMail $mail): string
    {
        return $this->mailTemplateRenderer->generateEmailPreHeader($mail);
    }

    /**
     * Add a step marker for multi-step forms to the form on a given position
     * @return $this
     *
     */
    public function addStep(): self
    {
        $this->stepController->addStep(count($this->formElements));
        return $this;
    }

    /**
     * Add a text above the list at the last step
     * @param string $text
     * @return void
     */
    public function setLastStepListText(string $text): self
    {
        $this->stepController->setLastStepListText($text);
        return $this;
    }

}
