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
     * Optimized via Claude AI 06.05.26
     */

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Successmessage extends TextElements
    {
        use TraitTags;

        /**
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct(?string $id = null)
        {
            parent::__construct($id);
            $this->setCSSClass('success_messageClass');
        }

    }
