<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating a success message under an input element
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Successmessage.php
     * Created: 17.05.2024
     */

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Successmessage extends TextElements
    {

        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct()
        {
            parent::__construct();
            $this->setCSSClass('success_messageClass');
            if ($this->frontendforms['input_framework'] === 'bootstrap5.json')
                $this->setTag('div');
            if ($this->frontendforms['input_framework'] === 'pico2.json')
                $this->setTag('small');
        }

    }