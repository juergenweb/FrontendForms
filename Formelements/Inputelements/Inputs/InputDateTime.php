<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input datetime element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputDateTime.php
 * Created: 03.07.2022
 */

use Exception;

/**
 * Class for creating an input datetime element
 */
class InputDateTime extends InputDate
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'datetime-local');
        $this->setRule('date');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputDateTime(): string
    {
        return $this->renderInput();
    }

}
