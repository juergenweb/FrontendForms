<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\FormHelper;
use FrontendForms\MailTemplateRenderer;
use PHPUnit\Framework\TestCase;
use ProcessWire\HookEvent;
use ProcessWire\WireMail;

use function ProcessWire\wire;

/**
 * Integration tests for the WireMail::send() hook of FrontendForms
 * (MailTemplateRenderer::registerHooks()/dispatch()/resolveForm()) - see GitHub issue #34.
 *
 * Mails are never actually sent: a "before" hook with a very high priority replaces
 * WireMail::send() after all FrontendForms hooks have run and captures the final body.
 */
final class MailTemplateDispatchTest extends TestCase
{
    private string $captureHookId = '';
    /** @var string[] */
    private array $sentBodies = [];

    protected function setUp(): void
    {
        MailTemplateRenderer::resetState();
        $this->sentBodies = [];
        $this->captureHookId = wire()->addHookBefore('WireMail::send', function (HookEvent $event) {
            $event->replace = true;
            $event->return = 1;
            $this->sentBodies[] = (string) $event->object->bodyHTML;
        }, ['priority' => 10000]);
    }

    protected function tearDown(): void
    {
        wire()->removeHook($this->captureHookId);
        MailTemplateRenderer::resetState();
    }

    /**
     * Set the template the form's renderer falls back to (as if it was configured in the module)
     */
    private function setConfiguredTemplate(Form $form, string $template): void
    {
        $property = new \ReflectionProperty(Form::class, 'mailTemplateRenderer');
        $property->getValue($form)->init($template);
    }

    private function newMail(string $body): WireMail
    {
        $mail = new WireMail();
        $mail->to('test@example.com');
        $mail->subject('Test');
        $mail->bodyHTML($body);
        return $mail;
    }

    private function lastBody(): string
    {
        return (string) end($this->sentBodies);
    }

    // --- resolveTemplateName() / checkForPath() ---

    public function testResolveTemplateNameTreatsEmptyAndInheritLikeConfiguredDefault(): void
    {
        $this->assertSame('none', MailTemplateRenderer::resolveTemplateName(null, ''));
        $this->assertSame('none', MailTemplateRenderer::resolveTemplateName('', ''));
        $this->assertSame('none', MailTemplateRenderer::resolveTemplateName('inherit', ''));
        $this->assertSame('none', MailTemplateRenderer::resolveTemplateName('', 'none'));
        $this->assertSame('template_1.html', MailTemplateRenderer::resolveTemplateName('', 'template_1.html'));
        $this->assertSame('template_1.html', MailTemplateRenderer::resolveTemplateName('inherit', 'template_1.html'));
        $this->assertSame('template_2.html', MailTemplateRenderer::resolveTemplateName('template_2.html', 'template_1.html'));
        $this->assertSame('none', MailTemplateRenderer::resolveTemplateName('none', 'template_1.html'));
    }

    public function testCheckForPathReturnsFalseForEmptyStringWithoutWarning(): void
    {
        $this->assertFalse(FormHelper::checkForPath(''));
        $this->assertFalse(FormHelper::checkForPath('template_1.html'));
        $this->assertTrue(FormHelper::checkForPath('/site/templates/mail/my_template.html'));
    }

    // --- unrelated mails (issue #34, problem 1) ---

    public function testUnrelatedMailIsLeftUntouchedWhenTemplateIsNone(): void
    {
        $form = new Form('dispatch-unrelated-none');
        $this->setConfiguredTemplate($form, 'none');

        $this->newMail('<p>Hello [[UNKNOWN]]</p>')->send();

        $this->assertSame('<p>Hello [[UNKNOWN]]</p>', $this->lastBody());
    }

    public function testUnrelatedMailIsLeftUntouchedWhenTemplateIsConfigured(): void
    {
        $form = new Form('dispatch-unrelated-template');
        $this->setConfiguredTemplate($form, 'template_1.html');

        $this->newMail('<p>Hello</p>')->send();

        $this->assertSame('<p>Hello</p>', $this->lastBody());
    }

    public function testMailOfSubmittedFormWithTemplateNoneDoesNotThrow(): void
    {
        $form = new Form('dispatch-submitted-none');
        $this->setConfiguredTemplate($form, 'none');
        $form->setMailPlaceholder('myfield', 'my value');
        MailTemplateRenderer::markSubmitted($form);

        $this->newMail('<p>[[MYFIELD]]</p>')->send();

        $this->assertSame('<p>my value</p>', $this->lastBody());
    }

    // --- multiple forms (issue #34, problem 2) ---

    public function testHookIsRegisteredOnlyOnceForMultipleForms(): void
    {
        new Form('dispatch-once-1');
        $before = count(wire()->hooks->getHooks(new WireMail(), 'send'));
        new Form('dispatch-once-2');
        new Form('dispatch-once-3');
        $after = count(wire()->hooks->getHooks(new WireMail(), 'send'));

        $this->assertSame($before, $after);
    }

    public function testSubmittedSecondFormKeepsItsPlaceholderValues(): void
    {
        $formA = new Form('dispatch-two-a');
        $formB = new Form('dispatch-two-b');
        $formA->setMailPlaceholder('afield', 'VALUE-A');
        $formB->setMailPlaceholder('bfield', 'VALUE-B');
        $this->setConfiguredTemplate($formB, 'none');
        MailTemplateRenderer::markSubmitted($formB);

        $this->newMail('<p>B: [[BFIELD]]</p>')->send();

        $this->assertSame('<p>B: VALUE-B</p>', $this->lastBody());
    }

    public function testUseFormTakesPrecedenceOverSubmittedForm(): void
    {
        $formA = new Form('dispatch-useform-a');
        $formB = new Form('dispatch-useform-b');
        $formA->setMailPlaceholder('field', 'FROM-A');
        $formB->setMailPlaceholder('field', 'FROM-B');
        $this->setConfiguredTemplate($formB, 'none');
        MailTemplateRenderer::markSubmitted($formA);

        $mail = $this->newMail('<p>[[FIELD]]</p>');
        $this->assertSame($mail, $mail->useForm($formB));
        $mail->send();

        $this->assertSame('<p>FROM-B</p>', $this->lastBody());
    }

    public function testSendAttachmentsAssignsTheMailToTheGivenForm(): void
    {
        $formA = new Form('dispatch-attach-a');
        $formB = new Form('dispatch-attach-b');
        $formA->setMailPlaceholder('field', 'FROM-A');
        $formB->setMailPlaceholder('field', 'FROM-B');
        $this->setConfiguredTemplate($formB, 'none');
        MailTemplateRenderer::markSubmitted($formA);

        $mail = $this->newMail('<p>[[FIELD]]</p>');
        $mail->sendAttachments($formB);
        $mail->send();

        $this->assertSame('<p>FROM-B</p>', $this->lastBody());
    }

    // --- explicitly requested template / double inclusion ---

    public function testExplicitMailTemplateWithoutSubmissionUsesLastCreatedForm(): void
    {
        $form = new Form('dispatch-explicit');
        $this->setConfiguredTemplate($form, 'none');
        $form->setMailPlaceholder('field', 'EXPLICIT');

        $this->newMail('<p>[[FIELD]]</p>')->mailTemplate('template_1.html')->send();

        $body = $this->lastBody();
        $this->assertStringContainsString('EXPLICIT', $body);
        $this->assertSame(1, substr_count(strtolower($body), '<!doctype'));
    }

    public function testSendingTheSameMailTwiceEmbedsTheTemplateOnlyOnce(): void
    {
        $form = new Form('dispatch-twice');
        $this->setConfiguredTemplate($form, 'template_1.html');
        MailTemplateRenderer::markSubmitted($form);

        $mail = $this->newMail('<p>Hello</p>');
        $mail->send();
        $mail->send();

        $this->assertCount(2, $this->sentBodies);
        $this->assertSame(1, substr_count(strtolower($this->sentBodies[0]), '<!doctype'));
        $this->assertSame($this->sentBodies[0], $this->sentBodies[1]);
    }

    public function testChangedBodyIsRenderedAgainOnSecondSend(): void
    {
        $form = new Form('dispatch-changed');
        $this->setConfiguredTemplate($form, 'none');
        $form->setMailPlaceholder('field', 'VALUE');
        MailTemplateRenderer::markSubmitted($form);

        $mail = $this->newMail('<p>first [[FIELD]]</p>');
        $mail->send();
        $mail->bodyHTML('<p>second [[FIELD]]</p>');
        $mail->send();

        $this->assertSame('<p>first VALUE</p>', $this->sentBodies[0]);
        $this->assertSame('<p>second VALUE</p>', $this->sentBodies[1]);
    }
}
