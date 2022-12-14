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
 */

use Exception;

class InputUrl extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'url');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputUrl(): string
    {
        return $this->renderInput();
    }

}
