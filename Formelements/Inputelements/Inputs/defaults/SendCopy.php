<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a "send copy of my message to me" input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: SendCopy.php
 * Created: 03.07.2022
 */

use Exception;

class SendCopy extends InputCheckbox
{
    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Send a copy of my message to me'));
    }

    /**
     * Render checkbox for sending a copy to the sender
     * @return string
     */
    public function ___renderSendCopy(): string
    {
        return parent::renderInputCheckbox();
    }

}
