<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input hidden element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputHidden.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputHidden extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'hidden');
        // disable input and field wrapper by default on this input type
        $this->useInputWrapper(false);
        $this->useFieldWrapper(false);
    }

    /**
     * Render the input element
     * @return string
     */
    public function renderInputHidden(): string
    {
        return $this->renderInput();
    }

}
