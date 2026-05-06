<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class with pre-defined values for creating a phone input field
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Phone.php
     * Created: 17.04.2024
     * Optimized via Claude AI 06.05.26
     */

    use Exception;

    class Phone extends InputTel
    {

        /**
         * @param string $id
         * @throws Exception
         */
        public function __construct(string $id)
        {
            parent::__construct($id);
            $this->setLabel($this->_('Phone'));
        }

        /**
         * Render the phone input field
         * @return string
         */
        public function ___renderPhone(): string
        {
            return parent::renderInputTel();
        }

    }
