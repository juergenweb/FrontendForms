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
 * renderTemplate() and removeTemplateSession() are registered as hook
 * targets for WireMail::send() by Form - see Form::__construct().
 *
 * @package FrontendForms\Mail
 */
final class MailTemplateRenderer extends Wire
{
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

        if ($configuredTemplate !== 'none') {
            $this->template = $configuredTemplate; // set filename
            $this->templatePath = $this->templatesDirPath . $this->template; // set file path
            $this->customTemplatePath = $this->customTemplatesDirPath . $this->template; // set file path
        }
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
        // TEMPORARY DIAGNOSTIC - remove after debugging
        file_put_contents(
            $this->form->wire('config')->paths->root . 'template-debug.txt',
            date('Y-m-d H:i:s')
            . " | BEFORE - mail->email_template=" . var_export($mail->email_template, true)
            . " | defaultTemplate (arg)=" . var_export($defaultTemplate, true)
            . " | this->template (MailTemplateRenderer property)=" . var_export($this->template, true)
            . "\n",
            FILE_APPEND
        );

        // set email_template property if it was not set before
        if (!$mail->email_template) {
            $mail->email_template = $defaultTemplate;
        }

        // check if email template is set
        if ($mail->email_template != 'none') {
            // set body as placeholder
            if ($mail->email_template == 'inherit') {
                // use the value from the FrontendForms module configuration
                $mail->email_template = $defaultTemplate;
            }

            // TEMPORARY DIAGNOSTIC - remove after debugging
            file_put_contents(
                $this->form->wire('config')->paths->root . 'template-debug.txt',
                date('Y-m-d H:i:s')
                . " | AFTER resolution - mail->email_template=" . var_export($mail->email_template, true)
                . "\n",
                FILE_APPEND
            );

            if ($mail->email_template != 'none') {

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
     * Hook callback for WireMail::send (registered as a "before" hook by Form).
     * @param HookEvent $event
     * @return Module|Wire|WireArray|WireData
     * @throws DOMException
     */
    public function renderTemplate(HookEvent $event): Module|Wire|WireArray|WireData
    {
        $mail = $event->object;

        // do not add a template if the template is set to "none"
        if ($mail->email_template !== 'none') {

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
            if ($this->form->wire('session')->get('templateloaded') != '1') {
                $this->includeMailTemplate($mail, $this->template); // include/use mail template if set
                $this->form->wire('session')->set('templateloaded', '1');
            }
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
     * This method prevents the multiple embedding of the email template if there are multiple forms on one page.
     * Hook callback for WireMail::send (registered as an "after" hook by Form).
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
