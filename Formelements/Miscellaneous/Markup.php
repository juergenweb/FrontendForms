<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating a markup element
     * This class does not have a lot of methods, because it is only for adding a string containing some
     * HTML elements to the form
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Markup.php
     * Created: 29.06.2024
     */

    class Markup
    {

        protected string $markup = '';

        public function __construct()
        {
        }

        public function setMarkup(string $markup): self
        {
            $this->markup = $markup;
            return $this;
        }

        public function getMarkup(): string
        {
            return $this->markup;
        }

        // All those function needs to be defined, but they have no effect
        // If they are not defined, an error will occur during the rendering process of the form
        public function setAttribute(string $name, string $attribute): void
        {

        }

        /**
        * Do not check for conditions
        */
        public function containsConditions(): bool
        {
            return false;
        }

        public function getID(): void
        {

        }
        public function getAttribute(): void
        {

        }

        public function render(): string
        {
            return $this->markup;
        }

    }
