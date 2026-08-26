<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Bootstrap5InputRenderer;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Bootstrap5InputRenderer.
 *
 * Focuses on what's DIFFERENT from DefaultInputRenderer (already covered by
 * DefaultInputRendererTest) - the Bootstrap validation state CSS classes
 * added to the label depending on error/$_POST state.
 *
 * IMPORTANT: the actual CSS class STRING added by setCSSClass('input_errorClass')
 * depends on whichever framework config is active in the live test
 * environment (e.g. "is-invalid" for Bootstrap5) - calling
 * Bootstrap5InputRenderer directly does not, by itself, guarantee Bootstrap5's
 * classes.json is the one being read. To stay independent of that, these
 * tests compare label class state *relatively* (does it change between the
 * error/success/neutral scenarios?) rather than asserting an exact string.
 *
 * See DefaultInputRendererTest's class docblock for why real field instances
 * are used instead of mocks.
 */
final class Bootstrap5InputRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // isolate from whatever the real request happened to contain
        $_POST = [];
    }

    private function renderer(): Bootstrap5InputRenderer
    {
        return new Bootstrap5InputRenderer();
    }

    /**
     * 1) A field with an error message ends up with a DIFFERENT label class
     * than the same field configuration without an error message - i.e.
     * the error branch actually changes something, whichever exact class
     * name the active framework config maps "input_errorClass" to.
     */
    public function testErrorMessagePresentChangesLabelClassComparedToNoError(): void
    {

        $withError = new InputText('textfield');
        $withError->setLabel('Your name');
        $withError->getErrorMessage()->setText('This field is required.');
        $this->renderer()->render($withError, 'InputText', '[INPUT]');

        $withoutError = new InputText('textfield');
        $withoutError->setLabel('Your name');

        $this->renderer()->render($withoutError, 'InputText', '[INPUT]');

        $this->assertNotSame(
            $withoutError->getLabel()->getAttribute('class'),
            $withError->getLabel()->getAttribute('class')
        );
    }

    /**
     * 2) A field posted with data but no error ends up with a DIFFERENT
     * label class than a fresh, unsubmitted field (the "success" branch
     * changes something).
     */
    public function testPostedWithoutErrorChangesLabelClassComparedToFreshForm(): void
    {
        $fresh = new InputText('textfield');
        $fresh->setLabel('Your name');
        $_POST = [];
        $this->renderer()->render($fresh, 'InputText', '[INPUT]');

        $posted = new InputText('textfield');
        $posted->setLabel('Your name');
        $_POST = ['some' => 'value'];
        $this->renderer()->render($posted, 'InputText', '[INPUT]');

        $this->assertNotSame(
            $fresh->getLabel()->getAttribute('class'),
            $posted->getLabel()->getAttribute('class')
        );
    }

    /**
     * 3) The error state takes priority: even if $_POST has data, a field
     * with an error message does NOT get the same class as a
     * posted-without-error field.
     */
    public function testErrorTakesPriorityOverPostedSuccessState(): void
    {
        $_POST = ['some' => 'value'];

        $postedWithError = new InputText('textfield');
        $postedWithError->setLabel('Your name');
        $postedWithError->getErrorMessage()->setText('Required.');
        $this->renderer()->render($postedWithError, 'InputText', '[INPUT]');

        $postedWithoutError = new InputText('textfield');
        $postedWithoutError->setLabel('Your name');
        $this->renderer()->render($postedWithoutError, 'InputText', '[INPUT]');

        $this->assertNotSame(
            $postedWithoutError->getLabel()->getAttribute('class'),
            $postedWithError->getLabel()->getAttribute('class')
        );
    }

    /**
     * 4) Just like DefaultInputRenderer, InputHidden still removes the
     * "class" attribute and skips label rendering.
     */
    public function testInputHiddenStillRemovesClassAttribute(): void
    {
        $field = new InputText('hiddenfield');
        $field->setAttribute('class', 'should-be-removed');

        $this->renderer()->render($field, 'InputHidden', '<input type="hidden">');

        $this->assertNull($field->getAttribute('class'));
    }
}
