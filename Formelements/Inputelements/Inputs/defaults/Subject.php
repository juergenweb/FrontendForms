<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class with pre-defined values for creating a subject input field
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Subject.php
 * Created: 03.07.2022
 */

use Exception;

class Subject extends InputText
{

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setLabel($this->_('Subject'));
        $this->setRule('required');
    }

    /**
     * Render the subject input field
     * @return string
     */
    public function ___renderSubject(): string
    {
        return parent::renderInputText();
    }

}
