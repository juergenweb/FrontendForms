<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a textarea element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Textarea.php
 * Created: 03.07.2022
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Textarea extends Inputfields
{

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('textarea');
        $this->setAttribute('rows', '5'); // default is 5
        $this->setCSSClass('textareaClass');
        $this->removeSanitizers();// remove all sanitizers by default
        $this->setSanitizer('textarea'); // add sanitizer textarea by default for security reasons
    }

    /**
     * Render the textarea input
     * @return string
     */
    public function ___renderTextarea(): string
    {
        $this->setContent($this->getAttribute('value'));
        return $this->renderNonSelfclosingTag($this->getTag(), true);
    }

}
