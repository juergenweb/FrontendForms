<?php

declare(strict_types=1);

namespace FrontendForms;

use DOMDocument;
use DOMException;
use Exception;
use ProcessWire\HookEvent;
use ProcessWire\Module;
use ProcessWire\Wire;
use ProcessWire\WireArray;
use ProcessWire\WireData;
use ProcessWire\WireMail;

use function ProcessWire\wirePopulateStringTags;

/**
 * MailTemplateRenderer
 *
 * Renders the HTML email template (built-in or custom) for outgoing mails,
 * replaces [[PLACEHOLDER]] tags with values from a MailPlaceholderRegistry,
 * and adds an invisible pre-header (with the Litmus hack to prevent other
 * text from showing up in email client previews).
 *
 * A single "before" hook on WireMail::send() is registered once per request
 * (see registerHooks()), no matter how many Form instances are created. For
 * each outgoing mail, resolveForm() decides which form's placeholders and
 * template settings apply - or whether the mail is left completely untouched
 * because it does not belong to FrontendForms at all (see dispatch()).
 *
 * @package FrontendForms\Mail
 */
final class MailTemplateRenderer extends Wire
{
    /** Marker property on a WireMail object: the Form the mail belongs to (see WireMail::useForm()) */
    public const MAIL_FORM_PROPERTY = '_ffForm';
    /** Marker property on a WireMail object: fingerprint of the body after it was rendered */
    private const MAIL_RENDERED_PROPERTY = '_ffRenderedFingerprint';

    private static bool $hooksRegistered = false;
    private static ?Form $lastCreatedForm = null; // most recently constructed form of this request
    private static ?Form $submittedForm = null; // form that was successfully submitted (isValid() === true) in this request

    private string $templatesDirPath = '';
    private string $customTemplatesDirPath = '';
    private string $template = '';
    // NOTE: templatePath/customTemplatePath are computed in init() for parity with the
    // former Form properties, but - as before - not read anywhere; includeMailTemplate()
    // recomputes the path itself from templatesDirPath/customTemplatesDirPath + template.
    private string $templatePath = '';
    private string $customTemplatePath = '';

    public function __construct(
        private readonly Form $form,
        private readonly MailPlaceholderRegistry $placeholders
    ) {
        parent::__construct();
    }

    /**
     * Initialize the template paths - called once from Form::__construct()
     * @param string $configuredTemplate - the value of the input_emailTemplate module config field
     * @return void
     */
    public function init(string $configuredTemplate): void
    {
        // set the path to the template folder for the email templates
        $this->templatesDirPath = $this->form->wire('config')->paths->siteModules . 'FrontendForms/email-templates/';
        // set the path to the custom template folder for the email templates
        $this->customTemplatesDirPath = $this->form->wire('config')->paths->site . 'frontendforms-custom-templates/';

        // "none" is stored as an empty template name (see resolveTemplateName())
        $this->template = '';
        $this->templatePath = $this->customTemplatePath = '';
        if ($configuredTemplate !== 'none') {
            $this->template = $configuredTemplate; // set filename
            $this->templatePath = $this->templatesDirPath . $this->template; // set file path
            $this->customTemplatePath = $this->customTemplatesDirPath . $this->template; // set file path
        }
    }

    /**
     * Register the WireMail::send() hook - only once per request, regardless of
     * how many Form instances are created.
     *
     * Previously every Form instance registered its own class-wide hook pair, so
     * with two forms on a page every mail was processed twice - and the first
     * form's hook removed the placeholders of the second form (wirePopulateStringTags()
     * removes unknown tags by default) before the second form's hook even ran.
     * @param Wire $wire - any Wire object to register the hook from
     * @return void
     */
    public static function registerHooks(Wire $wire): void
    {
        if (self::$hooksRegistered) {
            return;
        }
        self::$hooksRegistered = true;
        $wire->addHookBefore('WireMail::send', function (HookEvent $event) {
            self::dispatch($event);
        });
    }

    /**
     * Remember the most recently created form - used as a fallback for mails that
     * explicitly request a template via $mail->mailTemplate() but carry no form
     * reference (see resolveForm()).
     * @param Form $form
     * @return void
     */
    public static function registerForm(Form $form): void
    {
        self::$lastCreatedForm = $form;
    }

    /**
     * Remember the form that was successfully submitted in this request - called from
     * Form::___isValid() right before it returns true.
     * @param Form $form
     * @return void
     */
    public static function markSubmitted(Form $form): void
    {
        self::$submittedForm = $form;
    }

    /**
     * Reset the static request state - for tests only.
     * @return void
     */
    public static function resetState(): void
    {
        self::$lastCreatedForm = null;
        self::$submittedForm = null;
    }

    /**
     * Decide which form an outgoing mail belongs to:
     * 1) the form set explicitly via $mail->useForm($form) (or $mail->sendAttachments($form))
     * 2) otherwise the form that was successfully submitted in this request
     * 3) otherwise - only if the mail explicitly requested a template via
     *    $mail->mailTemplate() - the most recently created form (previous behavior)
     * 4) otherwise null: the mail does not belong to FrontendForms and stays untouched
     * @param WireMail $mail
     * @return Form|null
     */
    public static function resolveForm(WireMail $mail): ?Form
    {
        $form = $mail->get(self::MAIL_FORM_PROPERTY);
        if ($form instanceof Form) {
            return $form;
        }
        if (self::$submittedForm) {
            return self::$submittedForm;
        }
        if ((string) $mail->get('email_template') !== '') {
            return self::$lastCreatedForm;
        }
        return null;
    }

    /**
     * Hook callback for WireMail::send() (registered once by registerHooks()).
     * Renders the mail with the renderer of the form it belongs to. A mail that was
     * already rendered and not changed since then (e.g. sent a second time) is skipped,
     * so the template can never be embedded twice.
     * @param HookEvent $event
     * @return void
     * @throws DOMException
     */
    public static function dispatch(HookEvent $event): void
    {
        $mail = $event->object;
        if (!$mail instanceof WireMail) {
            return;
        }
        $fingerprint = $mail->get(self::MAIL_RENDERED_PROPERTY);
        if ($fingerprint !== null && $fingerprint === self::bodyFingerprint($mail)) {
            return; // already rendered and unchanged
        }
        $form = self::resolveForm($mail);
        if (!$form) {
            return; // not a FrontendForms mail -> leave it untouched
        }
        $form->renderTemplate($event);
        $mail->set(self::MAIL_RENDERED_PROPERTY, self::bodyFingerprint($mail));
    }

    /**
     * Fingerprint of the current mail body (bodyHTML, or Body for WireMailPHPMailer)
     * @param WireMail $mail
     * @return string
     */
    private static function bodyFingerprint(WireMail $mail): string
    {
        return md5((string) $mail->get('bodyHTML') . "\0" . (string) $mail->get('Body'));
    }

    /**
     * Resolve the effective template name of a mail: an explicitly set template
     * wins, an empty value or "inherit" falls back to the module configuration.
     * An empty result (template set to "none" in the module configuration) is
     * normalized to "none".
     * @param string|null $mailTemplate - the email_template property of the mail
     * @param string $defaultTemplate - the template from the module configuration
     * @return string
     */
    public static function resolveTemplateName(?string $mailTemplate, string $defaultTemplate): string
    {
        $name = (string) $mailTemplate;
        if ($name === '' || $name === 'inherit') {
            $name = $defaultTemplate;
        }
        return ($name === '' || $name === 'inherit') ? 'none' : $name;
    }

    /**
     * Include the template in the mail if it was set in the configuration or directly on the WireMail object
     * Takes the given default template (module config) to check whether a template should be used or not
     * @param Module|Wire|WireArray|WireData $mail
     * @param string $defaultTemplate - the value of the input_emailTemplate module config field
     * @return void
     * @throws DOMException
     * @throws Exception
     */
    public function includeMailTemplate(Module|Wire|WireArray|WireData $mail, string $defaultTemplate): void
    {
        // resolve the effective template: an empty value or "inherit" falls back to the
        // module configuration, an empty configuration value means "none"
        $mail->email_template = self::resolveTemplateName($mail->email_template, $defaultTemplate);

        // check if email template is set
        if ($mail->email_template !== 'none') {
            $templatesDirPath = $this->templatesDirPath;
            $customTemplatesDirPath = $this->customTemplatesDirPath;

            // check if template name or template path has been added
            if (FormHelper::checkForPath($mail->email_template)) {
                $templatesDirPath = $customTemplatesDirPath = '';
            }

            if ($this->form->wire('files')->exists($templatesDirPath . $mail->email_template)) {
                $body = $this->loadTemplate($templatesDirPath . $mail->email_template);
            } elseif ($this->form->wire('files')->exists($customTemplatesDirPath . $mail->email_template)) {
                $body = $this->loadTemplate($customTemplatesDirPath . $mail->email_template);
            } else {
                throw new Exception(sprintf(
                    'Mail could not be sent, because the mail template with the name %s does not exist.',
                    $mail->email_template
                ));
            }

            // if bodyHTML is set, set a body placeholder by default out of the content
            switch ($mail->className()) {
                case ('WireMailPHPMailer'):
                    $this->placeholders->set('body', $mail->Body);
                    break;
                default:
                    // default WireMail class used
                    $this->placeholders->set('body', $mail->bodyHTML);
            }

            // render [[BODY]] placeholder if it is present and convert all placeholders inside it
            if ($this->placeholders->get('body')) {
                $bodyPlaceholder = $this->placeholders->get('body');
                $bodyPlaceholder = wirePopulateStringTags($bodyPlaceholder, $this->placeholders->allSanitized(), ['tagOpen' => '[[', 'tagClose' => ']]']);
                $this->placeholders->set('body', $bodyPlaceholder);
            }

            // Replace all [[PLACEHOLDER]] tags in the template BEFORE
            // any DOM manipulation below (pre-header insertion).
            // DOMDocument::loadHTML()/saveHTML() automatically
            // percent-encodes characters like "[" and "]" when they
            // appear inside URI-type attributes (src, href) - since
            // those characters are reserved in URI syntax. If a
            // placeholder like [[DOMAINVALUE]] were still unreplaced
            // at that point (e.g. used inside an <img src="..."> as
            // part of a longer path), the DOM round-trip would turn
            // it into "%5B%5BDOMAINVALUE%5D%5D", which
            // wirePopulateStringTags() below would then no longer
            // recognize as a placeholder at all - leaving the
            // (now permanently mangled) literal tag text in the
            // final HTML instead of the intended value.
            $body = wirePopulateStringTags($body, $this->placeholders->allSanitized(), ['tagOpen' => '[[', 'tagClose' => ']]']);

            // add pre-header text (if present) right after the opening body tag
            if ($mail->title) {
                $doc = new DOMDocument();
                // Suppress libxml warnings/errors for this parse:
                // $body may contain the (deliberately unsanitized,
                // see MailPlaceholderRegistry::allSanitized()) [[BODY]]
                // placeholder content, which can include a literal
                // "&" that isn't part of a properly closed HTML
                // entity (e.g. inside a URL query string, or in
                // plain text like "Tom & Jerry") - DOMDocument's
                // strict HTML parser reports this as
                // "htmlParseEntityRef: expecting ';' in Entity",
                // even though it still parses the document
                // correctly enough for the simple pre-header
                // insertion below. Errors are cleared afterwards so
                // they don't leak into unrelated code elsewhere that
                // might check libxml_get_errors() later.
                $previousUseInternalErrors = libxml_use_internal_errors(true);
                $doc->loadHTML($body);
                libxml_clear_errors();
                libxml_use_internal_errors($previousUseInternalErrors);
                $bodyTags = $doc->getElementsByTagName('body');
                if ($bodyTags->length > 0) {
                    $bodyElement = $bodyTags->item(0);
                    $preheader = $doc->createElement('div', $mail->title . $this->getLitmusHack());
                    $preheader->setAttribute('style', $this->getPreheaderStyle());
                    $bodyElement->insertBefore($preheader, $bodyElement->firstChild);
                    $body = $doc->saveHTML();
                }
            }

            // set the result as the bodyHTML of the email

            // if bodyHTML is set, set a body placeholder by default out of the content
            switch ($mail->className()) {
                case ('WireMailPHPMailer'):
                    $mail->Body = $body;
                    break;
                default:
                    // default WireMail class used
                    $mail->bodyHTML($body);
            }
        } else {
            // add invisible div with email pre-header to the top of the email body
            switch ($mail->className()) {
                case ('WireMailPHPMailer'):
                    $mail->Body = $this->generateEmailPreHeader($mail) . $mail->Body;
                    break;
                default:
                    // default WireMail class used
                    $mail->bodyHTML($this->generateEmailPreHeader($mail) . $mail->bodyHTML);
            }
        }
    }

    /**
     * Load a template file from the given path including php code and output it as a string
     * @param string $templatePath - the path to the template that should be rendered
     * @return string - the HTML template
     */
    public function loadTemplate(string $templatePath): string
    {
        ob_start();
        include($templatePath);
        $var = ob_get_contents();
        ob_end_clean();
        return $var;
    }

    /**
     * Render the mail template: replace placeholders and use HTML email template if set
     * Called by dispatch() for every mail that belongs to this renderer's form.
     * @param HookEvent $event
     * @return Module|Wire|WireArray|WireData
     * @throws DOMException
     */
    public function renderTemplate(HookEvent $event): Module|Wire|WireArray|WireData
    {
        $mail = $event->object;

        // do not add a template if the effective template is "none" - this includes an
        // empty template name (module configuration set to "none", see init())
        if (self::resolveTemplateName($mail->email_template, $this->template) !== 'none') {

            // set the placeholder for the title if present
            $this->placeholders->set('title', $mail->title);

            // set the placeholder for the body
            if (($mail->bodyHTML) || ($mail->bodyHtml) || ($mail->body)) {

                // set HTML as preferred value
                $sourceWasHtml = true;
                if ($mail->bodyHTML) {
                    $content = $mail->bodyHTML;
                } elseif ($mail->bodyHtml) {
                    $content = $mail->bodyHtml;
                } else {
                    $content = $mail->body;
                    $sourceWasHtml = false;
                }

                $body = wirePopulateStringTags(
                    $content,
                    $sourceWasHtml ? $this->placeholders->allSanitized() : $this->placeholders->all(),
                    ['tagOpen' => '[[', 'tagClose' => ']]']
                );

                $this->placeholders->set('body', $body);
                $mail->bodyHTML($body);
                if ($sourceWasHtml) {
                    // derive a plain-text alternative from HTML, never mirror HTML into body -
                    // but only overwrite body if it wasn't already explicitly set by the caller
                    if (!$mail->body) {
                        $mail->body($mail->htmlToText($body));
                    }
                } else {
                    $mail->body($body);
                }

            }
            // include/use mail template - double inclusion is prevented by dispatch()
            $this->includeMailTemplate($mail, $this->template);
        } else {
            // populate Placeholders even if no template is used to send emails
            switch ($mail->className()) {
                case ('WireMailPHPMailer'):
                    $mail->Body = wirePopulateStringTags($mail->bodyHTML, $this->placeholders->allSanitized(), ['tagOpen' => '[[', 'tagClose' => ']]']);
                    break;
                default:
                    // default WireMail class used
                    $mail->bodyHTML(wirePopulateStringTags($mail->bodyHTML, $this->placeholders->allSanitized(), ['tagOpen' => '[[', 'tagClose' => ']]']));
            }
        }

        return $mail;
    }

    /**
     * Remove the former "templateloaded" session flag.
     * No longer needed (double inclusion is prevented per mail in dispatch()) and no longer
     * registered as a hook - kept for backwards compatibility only, it also clears a flag
     * that may still be stored in existing sessions from older versions.
     * @deprecated since 3.0.4
     * @return void
     */
    public function removeTemplateSession(): void
    {
        $this->form->wire('session')->remove('templateloaded');
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
        if (is_null($body)) {
            $body = '';
        }
        // add support for WireMailPHPMailer - has a different name for the bodyHTML property
        if ($mailModule === 'WireMailPHPMailer') {
            $mail->Body = $body;
        } else {
            $mail->bodyHTML($body);
        }
    }

    /**
     * Return placeholders for email pre-header to prevent showing up other text
     * The Litmus hack adds empty spaces after the mail placeholder to prevent the display of other text inside the
     * pre-header
     * @return string
     */
    public function getLitmusHack(): string
    {
        return '&#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279; &#847; &zwnj; &nbsp; &#8199; &#65279;';
    }

    /**
     * Method for internal usage only
     * @return string
     */
    public function getPreheaderStyle(): string
    {
        return 'display:none;font-size:1px; color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;';
    }

    /**
     * Generate an invisible pre-header text after the subject for an email
     * @param WireMail $mail
     * @return string
     */
    public function generateEmailPreHeader(WireMail $mail): string
    {
        if ($mail->title) { // check if title property was set
            // generate an invisible div container
            return '<div id="preheader-text" style="' . $this->getPreheaderStyle() . '">' . $mail->title . $this->getLitmusHack() . '</div>';
        }
        return '';
    }
}
