<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input text element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputText.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputText extends Input
{
    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputText(): string
    {
        return $this->renderInput();
    }

}
