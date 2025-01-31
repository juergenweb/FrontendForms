<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a message textarea element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Message.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Message extends Textarea
{

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Message'));
        $this->setRule('required')->setCustomFieldName($this->_('Message'));
    }

    /**
     * Render the input field for message
     * @return string
     */
    public function renderMessage(): string
    {
        return parent::renderTextarea();
    }

}
