<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input checkbox multiple element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputCheckboxMultiple.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputCheckboxMultiple extends Input
{

    use TraitPWOptions, TraitCheckboxesAndRadios, TraitCheckboxesAndRadiosMultiple, TraitOptionElements;

    protected array $checkboxes = [];// array to hold all InputCheckbox objects
    protected bool $directionHorizontal = true;// default checkbox orientation
    protected TextElements $topLabel; // Top label Textelement object

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);

        $this->setAttribute('type', 'checkbox');
        $this->removeAttribute('class');
        $this->setCSSClass('checkboxClass');
        $this->removeSanitizers('text');
        $this->setSanitizer('arrayVal');
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
        return $this->checkboxes;
    }

    /**
     * Add this method to the InputCheckboxMultiple object to display the checkboxes vertically
     */
    public function alignVertical(): void
    {
        $this->directionHorizontal = false;
    }

    /**
     * Add a checkbox input as an option to a checkbox multiple input element
     * @param string $label
     * @param string $value
     * @return InputCheckbox
     * @throws Exception
     */
    public function addOption(string $label, string $value): InputCheckbox
    {
        $checkbox = new InputCheckbox($this->getAttribute('name'));
        $checkbox->setAttribute('name', $this->getAttribute('name') . '[]');// add brackets to the name attribute
        $checkbox->setLabel($label)->removeAttribute('class');
        $checkbox->setAttribute('value', $value);
        $checkbox->useInputWrapper(false); //remove all wrappers
        $checkbox->useFieldWrapper(false);
        $checkbox->getLabel()->disableAsterisk(); // disable the required signs if present
        $this->checkboxes = array_merge($this->checkboxes, [$checkbox]);

        return $checkbox;
    }

    /**
     * Use a PW field of the type SelectOptions to create the checkboxes;
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
     * Render the multiple checkbox element
     * @return string
     */
    public function ___renderInputCheckboxMultiple(): string
    {
        if (empty($this->checkboxes)) {
            return '';
        }

        $out = $this->topLabel->render();
        $this->setAttribute('value', $this->getPostValue());

        if ($this->markupType === 'pico2.json') {
            $this->appendLabel($this->directionHorizontal);
        }

        $checkedValues = array_merge(
            (array) $this->getDefaultValue(),
            (array) $this->getPostValue()
        );
        $isRequired    = $this->hasAttribute('required');
        $appendLabel   = $this->getAppendLabel();
        $name          = $this->getAttribute('name');

        foreach ($this->checkboxes as $key => $checkbox) {
            $inputId = $name . '-' . $key;

            $checkbox->setAttribute('id', $inputId);
            $checkbox->getLabel()->setAttribute('class', 'uk-form-label uk-display-inline-block');

            if ($appendLabel) {
                $checkbox->getLabel()
                    ->setAttribute('for', $inputId)
                    ->setAttribute('class', 'uk-margin-small-right');
            }

            if ($isRequired) {
                $checkbox->setAttribute('required');
                $checkbox->setAttribute('data-multicheckbox', $name);
            }

            $this->applyMarkupFormatting($checkbox);

            if (in_array($checkbox->getAttribute('value'), $checkedValues, strict: true)) {
                $checkbox->setAttribute('checked');
            }

            $out .= $appendLabel
                ? $checkbox->renderInputCheckbox() . $checkbox->getLabel()->render()
                : $checkbox->render();
        }

        return $this->setCheckBoxRadioAlignmentClass($this->markupType, $this, $out);
    }

    /**
     * Render checkboxes depending on framework set
     * @param object $checkbox
     * @return void
     */
    private function applyMarkupFormatting(object $checkbox): void
    {
        switch ($this->markupType) {
            case 'bootstrap5.json':
                $class = $this->directionHorizontal
                    ? $this->getCSSClass('checkbox_horizontalClass')
                    : $this->getCSSClass('checkinputClass');
                $checkbox->prepend('<div class="' . $class . '">');
                $checkbox->getLabel()->append('</div>');
                break;

            case 'uikit3.json':
            default:
                if (!$this->directionHorizontal) {
                    $checkbox->getLabel()->append('<br>');
                }
                break;

            case 'pico2.json':
                break;
        }
    }

}
