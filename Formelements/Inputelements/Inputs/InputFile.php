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
    protected ?Wrapper $labelWrapper; // the label wrapper object for Bulma1
    protected bool $multiple = true; // allow multiple file upload or not
    protected bool $showClearLink = true; // set default to true to show the link under the input field
    protected bool $showTotalFileSize = false;

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);

        $pathInfo = pathinfo($this->markupType);
        $framework = $pathInfo['filename'];
        $this->setAttribute('data-framework', $framework);
        $this->setAttribute('data-filesize', '0');
        $this->setAttribute('type', 'file');
        $this->setCSSClass('input_fileClass');
        $this->setAttribute('class', 'fileupload');
        $this->setMultiple(); // allow multiple file upload by default
        $this->setLabel($this->_('File upload'));
        $this->removeSanitizers('text'); // no need because $_FILES is always an array
        $this->setSanitizer('arrayVal'); // sanitize array
        // set this validation rules as default on file upload field
        $this->setRule('noErrorOnUpload'); // check for upload errors on default
        $this->setRule('phpIniFilesize'); // add the max file size of php.ini as absolute limit of file size by default

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

        if ($this->frontendforms['input_framework'] === 'bulma1.json') {
            $this->wrapper = new Wrapper();
            $this->wrapper->setAttribute('class', 'file');

            $this->labelWrapper = new Wrapper();
            $this->labelWrapper->setAttribute('class', 'file-label')->setTag('label');

        }

    }

    /**
     * Enable/disable the appearance of the total file size under file input fields multiple
     * Has no effect if upload field allows only 1 file
     * @param bool $show
     * @return $this
     */
    public function showTotalFileSize(bool $show = true): self
    {
        $this->showTotalFileSize = $show;
        return $this;
    }

    /**
     * Get the setting if total file size of selected files should be displayed under the input field
     * @return bool
     */
    public function getShowTotalFileSize(): bool
    {
        return $this->showTotalFileSize;
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
     * Enable/disable multiple file upload
     * @param bool $multiple
     * @return $this
     */
    public function setMultiple(bool $multiple = true): self
    {
        $this->multiple = $multiple;

        $multiple
            ? $this->setAttribute('multiple')
            : $this->removeAttribute('multiple');

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
        // ID
        $this->setAttribute('id', $this->getAttribute('id') . '-fileupload');

        // Name
        if ($this->getMultiple()) {
            $this->setAttribute('name', $this->getAttribute('name') . '[]');
        }

        $framework = $this->frontendforms['input_framework'] ?? '';
        $out = '';

        switch ($framework) {
            case 'uikit3.json':
                $content = $this->renderInput() . $this->button->render();
                $this->wrapper->setContent($content);
                $out = $this->wrapper->render();
                break;

            case 'bulma1.json':
                $label = sprintf(
                    '<span class="file-cta"><span class="file-label">%s</span></span>',
                    $this->_('Select files')
                );

                $this->labelWrapper->setContent(
                    $this->append($label)->renderInput()
                );

                $this->wrapper->setContent($this->labelWrapper->render());
                $out = $this->wrapper->render();
                break;

            default:
                $out = $this->renderInput();
        }

        // Files Area
        if ($this->showClearLink) {
            $out .= sprintf(
                '<div class="files-area"><div id="%s-files" class="files-list"></div></div>',
                $this->getID()
            );
        }

        // Total File Size
        if ($this->getMultiple() && $this->getShowTotalFileSize()) {
            $out .= sprintf(
                '<div class="ff-total-file-size">
                <span class="ff-totallabel">%s:</span>
                <span id="%s-total">0 kB</span>
            </div>',
                $this->_('Total'),
                $this->getID()
            );
        }

        return $out;
    }

    /**
     * Render the input field including additional notes for validators used for file uploads
     * @return string
     */
    public function ___render(): string
    {
        $notes = &$this->notes_array;

        // phpIniFilesize vs allowedFileSize → smaller one wins
        if (isset($notes['phpIniFilesize'], $notes['allowedFileSize'])) {
            $allowed = Inputfields::convertToBytes($notes['allowedFileSize']['value']);
            $ini = Inputfields::convertToBytes($notes['phpIniFilesize']['value']);

            unset($notes[$allowed <= $ini ? 'phpIniFilesize' : 'allowedFileSize']);
        }

        $isMultiple = $this->getMultiple();

        // Single Upload → no total size hint
        if (!$isMultiple) {
            unset($notes['allowedTotalFileSize']);
        }

        // set max filesize
        $fileSize = $notes['phpIniFilesize']['value']
            ?? $notes['allowedFileSize']['value']
            ?? null;

        if ($fileSize !== null) {
            $this->setAttribute(
                'data-maxfilesize',
                Inputfields::convertToBytes($fileSize)
            );
        }

        // only on multi-upload fields
        if ($isMultiple) {

            if (isset($notes['allowedFileNumber'])) {
                $this->setAttribute(
                    'data-uploadlimit',
                    $notes['allowedFileNumber']['value']
                );
            }

            if (isset($notes['allowedTotalFileSize'])) {
                $this->setAttribute(
                    'data-maxtotalfilesize',
                    Inputfields::convertToBytes($notes['allowedTotalFileSize']['value'])
                );
            }
        }

        return parent::___render();
    }

}
