<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a fieldset close tag
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FieldsetClose.php
 * Created: 03.07.2022
 */

class FieldsetClose extends Element
{
    public function __construct()
    {
        parent::__construct();
        $this->setTag('fieldset');
    }

    public function __toString()
    {
        return $this->render();
    }

    /**
     * Render the fieldset close tag
     * @return string
     */
    public function render(): string
    {
        return '</' . $this->getTag() . '>';
    }

}
