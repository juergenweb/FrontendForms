<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating a reset/cancel button element to clear a form
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Button.php
     * Created: 03.07.2022
     */

    class ResetButton extends Button
    {

        public function __construct($name = 'reset')
        {
            parent::__construct($name);
            $this->setAttribute('type', 'reset');
            $this->setAttribute('value', $this->_('Reset'));
        }
    }