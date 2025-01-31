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
    protected bool $multiple = true; // allow multiple file upload or not
    protected bool $showClearLink = true; // set default to true to show the link under the input field
    protected Link $clearlink; // the link object for the file input clear link

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setAttribute('type', 'file');
        $this->setCSSClass('input_fileClass');
        $this->setMultiple(); // allow multiple file upload by default
        $this->setLabel($this->_('File upload'));
        $this->removeSanitizers('text'); // no need because $_FILES is always an array
        $this->setSanitizer('arrayVal'); // sanitize array
        // set this validation rules as default on file upload field
        $this->setRule('noErrorOnUpload'); // check for upload errors on default
        $this->setRule('phpIniFilesize'); // add the max file size of php.ini as absolute limit of file size by default
        $this->setAttribute('onchange', 'showClearLink(event)');
        if ($this->frontendforms['input_framework'] === 'uikit3.json') {
            // instantiate the button for the uikit3 framework
            $this->button = new Button();
            $this->button->setAttribute('type', 'button');
            $this->button->setAttribute('value', $this->_('Select files'));
            $this->button->setAttribute('tabindex', '-1');
            // instantiate wrapper for the uikit3 framework
            $this->wrapper = new Wrapper();
            $this->wrapper->setAttribute('class', 'js-upload');
            $this->wrapper->setAttribute('data-uk-form-custom');
        }
        // instantiate the clear link object
        $this->clearlink = new Link($this->getID() . '-clear');
        $this->clearlink->setUrl('#');
        $this->clearlink->setAttribute('class', 'clear-link');
        $this->clearlink->setAttribute('onclick', 'event.preventDefault();clearInputfield(this); return false;');
        $this->clearlink->setLinkText($this->_('Clear the input field'));

    }

    /**
     * Show or hide a clear the input field link under the input field
     * @param bool $show
     * @return void
     */
    public function showClearLink(bool $show = false): void
    {
        $this->showClearLink = $show;
    }


    /**
     * Get the clear link object for further manipulations
     * @return Link
     */
    public function getClearLink(): Link
    {
        return $this->clearlink;
    }

    /**
     * Enable/disable multiple file upload
     * @param bool $multiple
     * @return $this
     */
    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;
        if ($multiple) {
            $this->setAttribute('multiple');
        }
        return $this;
    }

    /**
     * Get the boolean value whether multiple file upload is enabled or not
     * @return bool
     */
    public function getMultiple(): bool
    {
        return $this->multiple;
    }

    /**
     * Render the input element
     * @return string
     */
    public function ___renderInputFile(): string
    {

        // add brackets to name attribute if multiple attribute is present
        if ($this->getMultiple())
            $this->setAttribute('name', $this->getAttribute('name') . '[]');
        switch ($this->frontendforms['input_framework']) {
            case('uikit3.json'):

                $out = $this->renderInput();
                $out .= $this->button->render();
                $this->wrapper->setContent($out);
                $out = $this->wrapper->render();
                break;
            default:
                $out = $this->renderInput();
        }
        if ($this->showClearLink) {
            $this->clearlink->setAttribute('id', $this->getID() . '-clear'); // set new id including form id
            $out .= '<div id="' . $this->getID() . '-clearlink-wrapper" class="clear-link-wrapper" style="display:none;">' . $this->clearlink->render() . '</div>';
        }
        return $out;
    }

    /**
     * Render the input field including additional notes for validators used for file uploads
     * @return string
     */
    public function ___render(): string
    {

        // check for simultaneous presence of 'phpIniFilesize' and 'allowedFileSize'
        if ((array_key_exists('phpIniFilesize', $this->notes_array)) && (array_key_exists('allowedFileSize',
                $this->notes_array))) {
            $allowed = Inputfields::convertToBytes($this->notes_array['allowedFileSize']['value']);
            $ini = Inputfields::convertToBytes($this->notes_array['phpIniFilesize']['value']);
            if ($allowed <= $ini) {
                // allowed filesize is larger than the one in ini.php - so take only the value of php.ini
                unset($this->notes_array['phpIniFilesize']); // remove phpIniFilesize from the array
            } else {
                unset($this->notes_array['allowedFileSize']); // remove allowedFileSize from the array
            }
        }

        // create HTML5 max-size attribute depending on validator settings
        if ((array_key_exists('phpIniFilesize', $this->notes_array)) || (array_key_exists('allowedFileSize', $this->notes_array))) {
            $file_size = $this->notes_array['phpIniFilesize']['value'] ?? $this->notes_array['allowedFileSize']['value'];
            $this->setAttribute('max-size', (string)(Inputfields::convertToBytes($file_size)/1000)); // set max-size in kb
        }
        return parent::___render();
    }

}
