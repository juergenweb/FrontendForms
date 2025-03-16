<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input radio multiple element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputRadioMultiple.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class InputRadioMultiple extends Input
{
    use TraitPWOptions, TraitCheckboxesAndRadios, TraitCheckboxesAndRadiosMultiple;

    protected array $radios = []; // array to hold all InputRadio objects
    protected bool $directionHorizontal = true; // default radio button orientation
    public TextElements $topLabel;


    /**
     * @param string $id
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
        $this->radios = array_merge($this->radios, [$radio]);
        return $radio;
    }

    /**
     * Remove a specific option element by its value
     * @param string|int $option -> option value can be a string or an integer
     * @return $this
     */
    public function removeOptionByValue(string|int $option): self
    {
        // get the key of the option element by value
        $key = null;
        foreach($this->radios as $key => $optionElement ) {
            if ($option == $optionElement->getAttribute('value')) {
                $key = $key;
                break;
            }
        }
        if($key !== null) {
            unset($this->radios[$key]);
        }
        return $this;
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
        $out = '';
        if ($this->radios) {

            $checked = [];
            //array to hold checked radios
            foreach ($this->radios as $key => $radio) {
                //Set unique ID for each radio button
                $radio->setAttribute('id', $this->getAttribute('name') . '-' . $key);
                $radio->useInputWrapper(false);
                $radio->useFieldWrapper(false);
                $radio->getLabel()->disableAsterisk();
                if (!$this->directionHorizontal) {
                    switch ($this->markupType) {
                        case ('bootstrap5.json'):
                            $radio->prepend('<div class="' . $this->getCSSClass('checkinputClass') . '">');
                            $radio->getLabel()->append('</div>');
                            break;
                        case ('pico2.json'):
                            $this->appendLabel(false);
                            break;
                        default:
                            $radio->getLabel()->append('<br>');
                    }
                } else {
                    switch ($this->markupType) {
                        case ('bootstrap5.json'):
                            $radio->prepend('<div class="' . $this->getCSSClass('checkbox_horizontalClass') . '">');
                            $radio->getLabel()->append('</div>');
                            break;
                        case ('pico2.json'):
                            $this->appendLabel(true);
                            break;
                        default:
                    }
                }

                if (in_array($radio->getAttribute('value'), $this->getDefaultValue())) {
                    $radio->setAttribute('checked');
                }

                if ($this->getPostValue() === $radio->getAttribute('value')) {
                    $radio->setAttribute('checked');
                }
                // if you use the setChecked() method a checked attribute will be added each time, so there can be more checked attributes than allowed
                // remove multiple checked attributes if present - only 1 checked attribute is allowed
                // the first checked attribute will be accepted - all others will be removed
                if (empty($checked)) {
                    if ($radio->hasAttribute('checked')) {
                        $checked[] = 1;
                    }
                } else {
                    $radio->removeAttribute('checked');
                }
                // Render label after input tag or wrap input tag with label tag
                if ($this->getAppendLabel()) {

                    $out .= $radio->renderInputRadio() . $radio->getLabel()->render();
                } else {

                    $out .= $radio->render();
                }
            }

            $out = $this->setCheckBoxRadioAlignmentClass($this->markupType, $this, $out);

        }
        return $out;
    }
}
