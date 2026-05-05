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
    use TraitPWOptions, TraitCheckboxesAndRadios, TraitCheckboxesAndRadiosMultiple, TraitOptionElements;

    protected array $radios = [];
    protected bool $directionHorizontal = true;
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
    protected function getOptions(): array
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
        $defaultValues = (array)$this->getDefaultValue();
        $postValue = $this->getPostValue();
        $out = '';

        foreach ($this->radios as $key => $radio) {
            $inputId = $name . '-' . $key;

            $radio->setAttribute('id', $inputId);
            $radio->useInputWrapper(false);
            $radio->useFieldWrapper(false);
            $radio->getLabel()->disableAsterisk();

            if ($isRequired) {
                $radio->setAttribute('required');
            }

            if ($appendLabel) {
                $radio->getLabel()->setAttribute('for', $inputId);
            }

            $this->applyRadioMarkupFormatting($radio);

            if (in_array($radio->getAttribute('value'), $defaultValues, strict: true)
                || $postValue === $radio->getAttribute('value')
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
