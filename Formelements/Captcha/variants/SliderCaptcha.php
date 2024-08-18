<?php
    declare(strict_types=1);

    /*
     * Class for creating a slider captcha
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: SliderCaptcha.php
     * Created: 11.08.2024
     */


    namespace FrontendForms;

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class SliderCaptcha extends AbstractSliderCaptcha
    {

        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct()
        {
            parent::__construct();
            $this->title = $this->_('Slider Captcha');
            $this->desc = $this->_('Move a puzzle piece.');

        }




    }