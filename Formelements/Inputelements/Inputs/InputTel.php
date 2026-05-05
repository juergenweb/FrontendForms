<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input tel element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputTel.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputTel extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'tel');
        $this->setRule('noLetters');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputTel(): string
    {
        return parent::renderInputText();
    }

}
