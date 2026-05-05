<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an input month element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputMonth.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;

class InputMonth extends Input
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'month');
        $this->setRule('month');
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputMonth(): string
    {
        return $this->renderInput();
    }

}
