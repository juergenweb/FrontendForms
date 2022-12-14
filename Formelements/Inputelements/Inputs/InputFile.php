<?php
declare(strict_types=1);

/*
 * Render an input field for file uploads
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: InputFile.php
 * Created: 12.07.2022 
 */


namespace FrontendForms;

use Exception;

class InputFile extends Input
{

    protected Button $button; // the button object for uikit3
    protected ?Wrapper $wrapper; // the wrapper object for uikit3
    protected bool $allowMultiple = false; // by default only 1 file is allowed for upload

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'file');
        $this->setCSSClass('input_fileClass');
        $this->allowMultiple($this->allowMultiple);
        if ($this->input_framework === 'uikit3') {
            // instantiate the button for the uikit3 framework
            $this->button = new Button();
            $this->button->setAttribute('type', 'button');
            $this->button->setAttribute('value', $this->_('Select files'));
            $this->button->setAttribute('tabindex', '-1');
            // instantiate wrapper for uikit3 framework
            $this->wrapper = new Wrapper();
            $this->wrapper->setAttribute('class', 'js-upload');
            $this->wrapper->setAttribute('data-uk-form-custom');
        }
    }

    /**
     * Allow or disallow upload of multiple files
     * true: uploading multiple files is allowed
     * false: only 1 file is allowed for upload
     * @param bool $allowMultiple
     * @return void
     */
    public function allowMultiple(bool $allowMultiple): void
    {
        $this->allowMultiple = $allowMultiple;
        if ($this->allowMultiple) {
            $this->setAttribute('multiple');
        }
    }

    /**
     * Returns true or false, depending on the settings
     * @return bool
     */
    public function getAllowMultiple(): bool
    {
        return $this->allowMultiple;
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputFile(): string
    {
        // add brackets to name attribute if multiple attribute is present
        if($this->hasAttribute('multiple'))
            $this->setAttribute('name', $this->getAttribute('name').'[]');
        switch ($this->input_framework) {
            case('uikit3'):
                $out = $this->renderInput();
                $out .= $this->button->___render();
                $this->wrapper->setContent($out);
                return $this->wrapper->___render();
            default:
                return $this->renderInput();
        }
    }

}