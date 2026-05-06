<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating the label for an input element
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Label.php
     * Created: 03.07.2022
     * Optimized via Claude AI 06.05.26
     */

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Label extends TextElements
    {

        use TraitTags;
        protected int|string $enableAsterisk = 1;
        protected bool $required = false;

        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct(?string $id = null)
        {
            parent::__construct($id);
            $this->enableAsterisk = $this->frontendforms['input_showasterisk']; // from global settings
            $this->setCSSClass('labelClass');
            $this->setTag('label');
        }

        /**
         * Disable the markup of Asterisk
         * Needed for Checkbox multiple and Radio multiple to prevent the asterisk being shown on every option if field is required
         */
        public function disableAsterisk(): void
        {
            $this->enableAsterisk = 0;
        }

        /**
         * Render the label element (if the label text exists)
         * Otherwise return an empty string
         * @return string
         */
        public function render(): string
        {
            if (!$this->getText()) {
                return '';
            }

            $content = $this->getText();

            if ($this->required) {
                $this->setCSSClass('label_requiredClass');
                if ($this->enableAsterisk && $this->frontendforms['input_showasterisk']) {
                    $content .= $this->renderAsterisk();
                }
            }

            $this->setContent($content);
            return parent::render();
        }

        /**
         * Get the required status
         * @return boolean
         */
        public function getRequired(): bool
        {
            return $this->required;
        }

        /**
         * Set the required status
         * @return void
         */
        public function setRequired(): void
        {
            $this->required = true;
        }

        /**
         * Render the markup of the asterisk
         * Method is Hook-able
         * @return string
         */
        public function ___renderAsterisk(): string
        {
            return '<span class="asterisk">*</span>';
        }

    }
