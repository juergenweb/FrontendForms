<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating a description under an input element
     * Will be instantiated in the setDescription() method of the input fields class
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Description.php
     * Created: 03.07.2022
     */

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Description extends TextElements
    {
        use TraitTags;
        protected string|null $desc_position = 'afterInput'; // position of the field description - before the label, after the label or after the input

        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct($id = null)
        {
            parent::__construct($id);
            $this->setCSSClass('descriptionClass');
        }

        /**
         * Set the position of the description on per field base
         * @param string $pos
         * @return self
         */
        public function setPosition(string $pos): self
        {
            if (in_array($pos, ['beforeLabel', 'afterLabel', 'afterInput'])) {
                $this->desc_position = $pos; // set new position property
                $this->setAttribute('class', $pos . '-desc'); // add new position class
            }
            return $this;
        }

        /**
         * Get the position of the description
         * @return string|null
         */
        public function getPosition(): ?string
        {
            return $this->desc_position;
        }


    }
