<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a select element.
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Select.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Select extends Inputfields
{
    use TraitOption, TraitPWOptions, TraitOptionElements, TraitInputfields;

    protected ?Wrapper $selectWrapper = null;

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('select');
        $this->setCSSClass('selectClass');

        if ($this->frontendforms['input_framework'] === 'bulma1.json') {
            $this->selectWrapper = new Wrapper();
            $this->selectWrapper->setTag('div');
            $this->selectWrapper->setAttribute('class', 'select');

            if (is_a($this, SelectMultiple::class)) {
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
        if (empty($this->options)) {
            return '';
        }

        $selectedValues = array_merge(
            (array) $this->getDefaultValue(),
            (array) $this->getPostValue()
        );

        $options = '';
        foreach ($this->options as $option) {
            if ($option instanceof TextElements) {
                if ($option->getTag() === 'hr') {
                    $options .= $option->renderSelfclosingTag($option->getTag());
                }
                continue;
            }

            if (in_array($option->getAttribute('value'), $selectedValues, strict: true)) {
                $option->setAttribute('selected');
                if ($this->useAriaAttr) {
                    $option->setAttribute('aria-selected', 'true');
                }
            }

            $options .= $option->render();
        }

        $this->setContent($options);
        $out = $this->renderNonSelfclosingTag($this->getTag());

        if ($this->frontendforms['input_framework'] === 'bulma1.json') {
            $this->selectWrapper->setContent($out);
            $out = $this->selectWrapper->renderNonSelfclosingTag($this->selectWrapper->getTag());
        }

        return $out;
    }
}
