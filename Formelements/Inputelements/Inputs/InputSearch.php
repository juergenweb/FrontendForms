<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input search element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputSearch.php
 * Created: 03.07.2022
 */

use Exception;

/**
 * Class for creating an input search element
 */
class InputSearch extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'search');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputSearch(): string
    {
        return parent::renderInputText();
    }

}
