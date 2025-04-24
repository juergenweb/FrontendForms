<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Trait containing additional methods for elements that contain options (select, radio, checkbox, datalist)
     * Can be used on datalist, select, select multiple, radio multiple and checklist multiple elements
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: TraitOptionElements.php
     * Created: 24.04.2025
     */

    trait TraitOptionElements
    {

        /**
         * Get the name of the property containing the options
         * @return string
         */
        protected function getOptionsPropertyName(): string
        {
            switch ($this->className) {
                case 'InputRadioMultiple':
                    $name = 'radios';
                    break;
                case 'InputCheckboxMultiple':
                    $name = 'checkboxes';
                    break;
                default:
                    $name = 'options';
            }

            return $name;
        }

        /**
         * Get a specific option element by its value (for further manipulations)
         * @param string|int $value
         * @return \FrontendForms\InputRadio|\FrontendForms\InputCheckbox|\FrontendForms\Option|null
         */
        public
        function getOptionByValue(string|int $value): null|InputRadio|InputCheckbox|Option
        {
            $option = null;
            $name = $this->getOptionsPropertyName();

            foreach ($this->$name as $optionElement) {
                if ($value == $optionElement->getAttribute('value')) {
                    $option = $optionElement;
                    break;
                }
            }
            return $option;
        }

        /**
         * Remove a specific option element by its value
         * @param string|int $option -> option value can be a string or an integer
         * @return $this
         */
        public
        function removeOptionByValue(string|int $value): void
        {

            $name = $this->getOptionsPropertyName();

            foreach ($this->$name as $key => $optionElement) {
                if ($value == $optionElement->getAttribute('value')) {
                    unset($this->$name[$key]);
                    break;
                }
            }
        }


    }
