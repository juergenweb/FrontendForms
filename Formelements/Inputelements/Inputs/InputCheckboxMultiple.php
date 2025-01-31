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
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputCheckboxMultiple extends Input
{

    use TraitPWOptions, TraitCheckboxesAndRadios, TraitCheckboxesAndRadiosMultiple;

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
        $out = '';

        if ($this->checkboxes) {
            $out .= $this->topLabel->render();

            // set post value as value if present
            $this->setAttribute('value', $this->getPostValue());

            foreach ($this->checkboxes as $key => $checkbox) {
                //Set unique ID for each radio button
                $checkbox->setAttribute('id', $this->getAttribute('name') . '-' . $key);

                switch ($this->markupType) {
                    case ('bootstrap5.json'):
                        $class = $this->getCSSClass('checkinputClass');
                        if($this->directionHorizontal) $class = $this->getCSSClass('checkbox_horizontalClass');
                        $checkbox->prepend('<div class="' . $class . '">');
                        $checkbox->getLabel()->append('</div>');
                        break;
                    case ('pico2.json'):
                        if($this->directionHorizontal){
                            // horizontal
                            $this->appendLabel(true);
                            //$this->setAppendLabel(true);
                        } else {
                            //$this->setAppendLabel(false);
                            $this->appendLabel(false);
                        }
                        break;
                    default:
                        if (!$this->directionHorizontal) {
                            $checkbox->getLabel()->append('<br>');
                        }

                }

                if (in_array($checkbox->getAttribute('value'), $this->getDefaultValue())) {
                    $checkbox->setAttribute('checked');
                }

                if (in_array($checkbox->getAttribute('value'), $this->getPostValue())) {
                    $checkbox->setAttribute('checked');
                }

                // Render label after input tag or wrap input tag with label tag
                if ($this->getAppendLabel()) {
                    $out .= $checkbox->renderInputCheckbox() . $checkbox->getLabel()->render();
                } else {
                    $out .= $checkbox->render();
                }

            }

            // add additional wrapper over multiple checkboxes
            $out = $this->setCheckBoxRadioAlignmentClass($this->markupType, $this, $out);

        }

        return $out;
    }
}
