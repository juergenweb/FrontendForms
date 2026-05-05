<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input url element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputUrl.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputUrl extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'url');
        $this->setRule('url');
        $this->setRule('urlActive');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputUrl(): string
    {
        return parent::renderInputText();
    }

}
