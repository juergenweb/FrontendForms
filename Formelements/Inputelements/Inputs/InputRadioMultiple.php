<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input radio multiple element.
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRadioMultiple.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputRadioMultiple extends Input
{
    use TraitPWOptions;
    use TraitCheckboxesAndRadios;
    use TraitCheckboxesAndRadiosMultiple;
    use TraitOptionElements;

    protected array $radios = [];
    protected bool $directionHorizontal = true;
    protected bool $retainSubmittedValue = true;
    public TextElements $topLabel;

    /**
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'radio');
        $this->removeAttribute('class');
        $this->setCSSClass('radioClass');
        $this->multipleWrapper = new Wrapper();
        $this->topLabel = new TextElements();
        $this->topLabel->setTag('div');
    }

    /**
     * Returns an array of all option objects
     * @return array
     */
    public function getOptions(): array
    {
        return $this->radios;
    }

    /**
     * Add this method to the InputRadioMultiple object to display the radio buttons vertically
     */
    public function alignVertical(): void
    {
        $this->directionHorizontal = false;
    }

    /**
     * Control whether a previously submitted (or default) value stays
     * selected when the radio group is re-rendered.
     *
     * Defaults to true, matching normal radio group behavior (e.g. the
     * user's previous choice stays selected after a validation failure on
     * some other field). Set to false for cases where retaining the value
     * would be undesirable - most notably the image CAPTCHA's radio
     * group, where the previously submitted answer must NOT stay
     * pre-selected on re-render, otherwise the visitor could resubmit the
     * same (potentially already-known-correct, or simply stale) answer
     * without actually looking at the newly generated CAPTCHA image again.
     * @param bool $retain
     * @return void
     */
    public function retainSubmittedValue(bool $retain): void
    {
        $this->retainSubmittedValue = $retain;
    }

    /**
     * Add a radio input as an option to a radio multiple input element
     * @param string $label - the text label for the radio button
     * @param string $value -> the value of the radio button
     * @return InputRadio
     * @throws Exception
     */
    public function addOption(string $label, string $value): InputRadio
    {
        $radio = new InputRadio($this->getAttribute('name'));
        $radio->setLabel($label)->removeAttribute('class');
        $radio->setAttribute('value', $value);
        $this->radios[] = $radio;
        return $radio;
    }

    /**
     * Use a PW field of the type SelectOptions to create the radios;
     * @param string $fieldName
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     */
    public function setOptionsFromField(string $fieldName): void
    {
        $this->setOptionsFromFieldType($fieldName, 'addOption');
    }

    /**
     * Render multiple radio buttons in a group
     * Only one can be selected
     * @return string
     */
    public function ___renderInputRadioMultiple(): string
    {
        if (empty($this->radios)) {
            return '';
        }

        // pico2: set appendLabel once before the loop
        if ($this->markupType === 'pico2.json') {
            $this->appendLabel($this->directionHorizontal);
        }

        $checkedFound = false;
        $appendLabel = $this->getAppendLabel();
        $name = $this->getAttribute('name');
        $isRequired = $this->hasAttribute('required');
        // Only fall back to the default values before the form has ever
        // been submitted - once submitted, whether a default should still
        // apply depends solely on the actual submitted value, not on an
        // unconditional default check (see InputCheckboxMultiple for the
        // same reasoning; applies less often in practice for radios, but
        // the underlying assumption is the same).
        //
        // When retainSubmittedValue is false (e.g. the image CAPTCHA's
        // radio group), neither the default nor the submitted value
        // should ever pre-select an option, regardless of submission
        // state.
        $defaultValues = (!$this->retainSubmittedValue || $this->isSubmitted()) ? [] : (array)$this->getDefaultValue();
        $postValue = $this->retainSubmittedValue ? $this->getPostValue() : null;
        $out = '';

        foreach ($this->radios as $key => $radio) {
            $inputId = $name . '-' . $key;

            $radio->setAttribute('id', $inputId);
            $radio->useInputWrapper(false);
            $radio->useFieldWrapper(false);
            $radio->getLabel()->disableAsterisk();
            // propagate this group's setting to the individual radio -
            // otherwise renderInputRadio() would independently re-check
            // the post value on its own, bypassing the group-level
            // decision made just above
            $radio->retainSubmittedValue($this->retainSubmittedValue);

            if ($isRequired) {
                $radio->setAttribute('required');
            }

            if ($appendLabel) {
                $radio->getLabel()->setAttribute('for', $inputId);
            }

            $this->applyRadioMarkupFormatting($radio);

            if (in_array($radio->getAttribute('value'), $defaultValues, strict: true)
                || ($postValue !== null && $postValue === $radio->getAttribute('value'))
            ) {
                $radio->setAttribute('checked');
            }

            // Only one radio may be checked — remove duplicates
            if ($radio->hasAttribute('checked')) {
                if ($checkedFound) {
                    $radio->removeAttribute('checked');
                } else {
                    $checkedFound = true;
                }
            }

            $out .= $appendLabel
                ? $radio->renderInputRadio() . $radio->getLabel()->render()
                : $radio->render();
        }

        return $this->setCheckBoxRadioAlignmentClass($this->markupType, $this, $out);
    }

    /**
     * Create the markup for the various frameworks
     * @param InputRadio $radio
     * @return void
     */
    private function applyRadioMarkupFormatting(InputRadio $radio): void
    {
        if (!$this->directionHorizontal) {
            switch ($this->markupType) {
                case 'bootstrap5.json':
                    $radio->prepend('<div class="' . $this->getCSSClass('checkinputClass') . '">');
                    $radio->getLabel()->append('</div>');
                    break;
                case 'uikit3.json':
                    $radio->getLabel()
                        ->setAttribute('class', 'uk-form-label uk-display-inline-block')
                        ->append('<br>');
                    break;
                case 'pico2.json':
                    break;
                default:
                    $radio->getLabel()->append('<br>');
            }
        } else {
            switch ($this->markupType) {
                case 'bootstrap5.json':
                    $radio->prepend('<div class="' . $this->getCSSClass('checkbox_horizontalClass') . '">');
                    $radio->getLabel()->append('</div>');
                    break;
                case 'uikit3.json':
                    $radio->getLabel()
                        ->setAttribute('class', 'uk-form-label uk-display-inline-block')
                        ->setAttribute('class', 'uk-margin-small-right');
                    break;
                case 'pico2.json':
                    break;
            }
        }
    }
}
