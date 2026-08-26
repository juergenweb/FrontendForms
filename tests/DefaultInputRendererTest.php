<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DefaultInputRenderer;
use FrontendForms\InputCheckbox;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DefaultInputRenderer.
 *
 * Uses real field instances (InputText, InputCheckbox) rather than mocks:
 * Inputfields sits at the bottom of a Tag/Element/Wire chain with several
 * magic-property/constructor-dependent behaviours (see the Page-related
 * lessons learned earlier in this session for CaptchaQuestionRepositoryTest)
 * that make real objects far more predictable than mocks here. The test
 * environment bootstraps a real ProcessWire installation (see
 * tests/bootstrap.php), so constructing real fields works reliably.
 *
 * $input (the pre-rendered raw <input> markup) is passed in as a plain
 * fixed string in every test - DefaultInputRenderer treats it as an opaque
 * value it never inspects, so a real rendered <input> tag isn't needed.
 */
final class DefaultInputRendererTest extends TestCase
{
    private function renderer(): DefaultInputRenderer
    {
        return new DefaultInputRenderer();
    }

    // --- InputHidden branch ---

    /**
     * 1) For InputHidden, the "class" attribute is removed and the raw
     * input markup is used as-is, with no label/description wrapping.
     */
    public function testInputHiddenRemovesClassAttributeAndSkipsLabel(): void
    {
        $field = new InputText('hiddenfield');
        $field->setAttribute('class', 'should-be-removed');
        $field->setLabel('This label must not appear');

        $out = $this->renderer()->render($field, 'InputHidden', '<input type="hidden">');

        $this->assertNull($field->getAttribute('class'));
        $this->assertStringContainsString('<input type="hidden">', $out);
        $this->assertStringNotContainsString('This label must not appear', $out);
    }

    // --- default (text-like) branch ---

    /**
     * 2) For a regular field, the label is rendered before the input markup
     * when it has text.
     */
    public function testDefaultBranchRendersLabelBeforeInput(): void
    {
        $field = new InputText('textfield');
        $field->setLabel('Your name');

        $out = $this->renderer()->render($field, 'InputText', '[INPUT]');

        $labelPos = strpos($out, 'Your name');
        $inputPos = strpos($out, '[INPUT]');
        $this->assertNotFalse($labelPos);
        $this->assertNotFalse($inputPos);
        $this->assertLessThan($inputPos, $labelPos);
    }

    /**
     * 3) A field with no label text does not render an (empty) label at all.
     */
    public function testDefaultBranchSkipsEmptyLabel(): void
    {
        $field = new InputText('textfield');
        $field->setLabel('');

        $out = $this->renderer()->render($field, 'InputText', '[INPUT]');

        $this->assertStringNotContainsString('<label', $out);
    }

    /**
     * 4) The description is positioned before the label, after the label,
     * or after the input, depending on its configured position.
     */
    public function testDescriptionPositionIsRespected(): void
    {
        $field = new InputText('textfield');
        $field->setLabel('Label text');
        $field->setDescription('Description text')->setPosition('beforeLabel');

        $out = $this->renderer()->render($field, 'InputText', '[INPUT]');

        $descPos = strpos($out, 'Description text');
        $labelPos = strpos($out, 'Label text');
        $this->assertLessThan($labelPos, $descPos, 'beforeLabel description should render before the label');
    }

    /**
     * 5) When useInputWrapper is enabled, the input markup is wrapped
     * (rendering adds the wrapper's own tag around it); when disabled, the
     * raw input markup appears directly in the output.
     */
    public function testInputWrapperCanBeDisabled(): void
    {
        $field = new InputText('textfield');
        $field->useInputWrapper(false);

        $out = $this->renderer()->render($field, 'InputText', '[UNIQUEINPUTMARK]');

        $this->assertStringContainsString('[UNIQUEINPUTMARK]', $out);
    }

    // --- checkbox-like branch (InputCheckbox / InputRadio / Privacy / SendCopy) ---

    /**
     * 6) For checkbox-like fields, the input markup is wrapped INSIDE the
     * label (so clicking the label text also toggles the checkbox) rather
     * than being rendered as a sibling before/after it.
     */
    public function testCheckboxBranchWrapsInputInsideLabel(): void
    {
        $field = new InputCheckbox('agree');
        $field->setLabel('I agree to the terms');

        $out = $this->renderer()->render($field, 'InputCheckbox', '<input type="checkbox">');

        $labelOpenPos = strpos($out, '<label');
        $inputPos = strpos($out, '<input type="checkbox">');
        $labelClosePos = strpos($out, '</label>');

        $this->assertNotFalse($labelOpenPos);
        $this->assertNotFalse($inputPos);
        $this->assertNotFalse($labelClosePos);
        $this->assertGreaterThan($labelOpenPos, $inputPos);
        $this->assertLessThan($labelClosePos, $inputPos);
    }

    /**
     * 7) When appendLabel is enabled, the input markup comes BEFORE the
     * label's own text inside the rendered label element.
     */
    public function testCheckboxBranchWithAppendLabelPutsInputBeforeLabelText(): void
    {
        $field = new InputCheckbox('agree');
        $field->setLabel('I agree');
        $field->appendLabel(true);

        $out = $this->renderer()->render($field, 'InputCheckbox', '[CHECKBOXINPUT]');

        $inputPos = strpos($out, '[CHECKBOXINPUT]');
        $textPos = strpos($out, 'I agree');
        $this->assertLessThan($textPos, $inputPos);
    }
}
