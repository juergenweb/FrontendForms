<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * This is the base class for creating input elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Input.php
 * Created: 03.07.2022
 */

use Exception;

class Input extends Inputfields
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('input');
        $this->setAttribute('type', 'text');// set text as default type if no type was set
        $this->setCSSClass('inputClass');
    }

    /**
     *  Render the input tag
     * @return string
     */
    public function renderInput(): string
    {
        return $this->renderSelfclosingTag($this->getTag()). PHP_EOL;
    }

}
