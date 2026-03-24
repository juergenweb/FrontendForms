<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a select element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Select.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Select extends Inputfields
{

    use TraitOption, TraitPWOptions, TraitOptionElements, TraitInputfields;

    protected ?Wrapper $selectWrapper = null; // special wrapper for Bulma framework

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('select');
        $this->setCSSClass('selectClass');

        if($this->frontendforms['input_framework'] === 'bulma1.json') {
            $this->selectWrapper = new Wrapper();
            $this->selectWrapper->setTag('div');
            $this->selectWrapper->setAttribute('class', 'select');

            // get class
            if(get_class($this) == 'FrontendForms\SelectMultiple') {
                $this->selectWrapper->setAttribute('class', 'is-multiple');
            }
        }
    }

    /**
     * Use a PW field of the type SelectOptions to create the options;
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
     * Returns an array of all option objects
     * @return array
     */
    protected function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get the select wrapper element if Bulma 1 is used
     * @return Wrapper|null
     */
    public function getSelectWrapper(): ?Wrapper
    {
        return $this->selectWrapper;
    }

    /**
     * Render the select input
     * @return string
     */
    public function ___renderSelect(): string
    {
        $out = '';
        if ($this->options) {
            $options = '';
            foreach ($this->options as $option) {
                // check if option element is hr element
                if($option instanceof TextElements) {
                    // add hr tag only to Select, but not to SelectMultiple
                    if($option->getTag() === 'hr'){
                        $options .= $option->renderSelfclosingTag($option->getTag());
                    }
                } else {
                    if (in_array($option->getAttribute('value'), $this->getDefaultValue())) {
                        $option->setAttribute('selected');
                        if($this->useAriaAttr) $option->setAttribute('aria-selected', 'true');
                    }
                    if (in_array($option->getAttribute('value'), (array)$this->getPostValue())) {
                        $option->setAttribute('selected');
                        if($this->useAriaAttr) $option->setAttribute('aria-selected', 'true');
                    }
                    $options .= $option->render();
                }
            }
            $this->setContent($options);

            $out = $this->renderNonSelfclosingTag($this->getTag());

            // special treatment for Bulma framework
            if($this->frontendforms['input_framework'] === 'bulma1.json') {
                $this->selectWrapper->setContent($out);
                $out = $this->selectWrapper->renderNonSelfclosingTag($this->selectWrapper->getTag());
            }
        }
        return $out;
    }

}
