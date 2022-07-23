<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding option elements
 * Will be used on select and datalist elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TraitOptions.php
 * Created: 03.07.2022
 */

trait TraitOption
{

    protected array $options = [];
    protected string|bool $emptyOption = false;

    /**
     * Set an option object with value and label
     * @param string $label - the label text
     * @param string $value - the value of the option element
     * @return Option
     */
    public function addOption(string $label, string $value): Option
    {
        $option = new Option();
        $option->setContent($label);
        $option->setAttribute('value', $value);
        $this->options = array_merge($this->options, [$option]);
        return $option;
    }

    /**
     * Create an empty option object
     * @param string $optionLabel
     * @return Option
     */
    public function addEmptyOption(string $optionLabel = '-'): Option
    {
        $option = new Option();
        $option->setContent($optionLabel);
        $this->options = array_merge($this->options, [$option]);
        return $option;
    }

}
