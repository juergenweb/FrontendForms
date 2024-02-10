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
        $option->setAttribute('value', '');
        $this->options = array_merge($this->options, [$option]);
        return $option;
    }

    /**
     * Add hr tag to the options to help visually break up the options for a better user experience
     * https://developer.chrome.com/blog/hr-in-select
     * Not supported in all browsers - so please check it if it works
     * @return \FrontendForms\Option
     */
    public function addHorizontalRule(): TextElements
    {
        $hr = new TextElements();
        $hr->setTag('hr');
        $this->options = array_merge($this->options, [$hr]);
        return $hr;
    }

    /**
     * This method deletes all options
     * @return void
     */
    public function removeAllOptions(): void
    {
        $this->options = [];
    }

}
