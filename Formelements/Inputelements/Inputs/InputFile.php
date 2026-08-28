<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Render an input field for file uploads.
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: InputFile.php
 * Created: 12.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use InvalidArgumentException;

class InputFile extends Input
{
    protected Button $button;
    protected ?Wrapper $wrapper = null;
    protected ?Wrapper $labelWrapper = null;
    protected bool $multiple = true;
    protected bool $showClearLink = true;
    protected bool $showTotalFileSize = false;
    protected ?bool $imageReRender = null; // null = no per-field override, follow the global module setting

    /**
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);

        $framework = pathinfo($this->markupType, PATHINFO_FILENAME);

        $this->setAttribute('data-framework', $framework);
        $this->setAttribute('data-filesize', '0');
        $this->setAttribute('type', 'file');
        $this->setAttribute('class', 'fileupload');
        $this->setCSSClass('input_fileClass');
        $this->setMultiple();
        $this->setLabel($this->_('File upload'));
        $this->removeSanitizers('text');
        $this->setSanitizer('arrayVal'); // sanitize that upload value is an array
        $this->setRule('noErrorOnUpload'); // do not remove - check for error during upload
        $this->setRule('noEmptyFiles'); // do not allow the upload of empty files
        $this->setRule('matchingExtMimeType'); // do not remove - security check for spoofed files
        $this->setRule('phpIniUploadMaxFileSize', self::normalizeSizeUnit(ini_get('upload_max_filesize'))); // do not remove-> checks for server limit against single upload size limit
        $this->setRule('phpIniPostMaxFileSize', self::normalizeSizeUnit(ini_get('post_max_size'))); // do not remove -> checks for server limit against total upload size

        // check global settings for allowed MIME types
        if (array_key_exists('input_allowedmimetypes', $this->frontendforms)) {
            if ($this->frontendforms['input_allowedmimetypes']) {
                $allowedMimeTypes = preg_split("/\r\n|\r|\n/", $this->frontendforms['input_allowedmimetypes']);
                $this->setRule('allowedMimeTypes', $allowedMimeTypes);
            }
        }

        match ($framework) {
            'uikit3' => $this->initUikit3(),
            'bulma1' => $this->initBulma1(),
            default => null,
        };
    }

    /**
     * Set special attributes for UIKIT3
     * @return void
     */
    private function initUikit3(): void
    {
        $this->button = new Button();
        $this->button->setAttribute('type', 'button');
        $this->button->setAttribute('value', $this->_('Select files'));
        $this->button->setAttribute('tabindex', '-1');

        $this->wrapper = new Wrapper();
        $this->wrapper->setAttribute('class', 'js-upload');
        $this->wrapper->setAttribute('data-uk-form-custom');
    }

    /**
     * Set special attributes for Bulma 1
     * @return void
     */
    private function initBulma1(): void
    {
        $this->wrapper = new Wrapper();
        $this->wrapper->setAttribute('class', 'file');

        $this->labelWrapper = new Wrapper();
        $this->labelWrapper->setAttribute('class', 'file-label')->setTag('label');
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
     * Explicitly enable or disable image re-rendering for this specific
     * upload field, overriding the global "Measure 10: Re-render
     * uploaded images" module setting for this field only.
     * @param bool $reRender
     * @return $this
     */
    public function setImageReRender(bool $reRender = true): self
    {
        $this->imageReRender = $reRender;
        return $this;
    }

    /**
     * Get the per-field image re-rendering override, if one was set via
     * setImageReRender(). Returns null if no override was set for this
     * field, meaning the global module setting should be used instead.
     * @return bool|null
     */
    public function getImageReRender(): ?bool
    {
        return $this->imageReRender;
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
        $this->setAttribute('id', $this->getAttribute('id') . '-fileupload');

        if ($this->getMultiple()) {
            $this->setAttribute('name', $this->getAttribute('name') . '[]');
        }

        $out = match ($this->frontendforms['input_framework'] ?? '') {
            'uikit3.json' => $this->renderUikit3(),
            'bulma1.json' => $this->renderBulma1(),
            default => $this->renderInput(),
        };

        if ($this->showClearLink) {
            $out .= sprintf(
                '<div class="files-area"><div id="%s-files" class="files-list"></div></div>',
                $this->getID()
            );
        }

        if ($this->getMultiple() && $this->getShowTotalFileSize()) {
            $out .= sprintf(
                '<div class="ff-total-file-size"><span class="ff-totallabel">%s:</span><span id="%s-total">0 kB</span></div>',
                $this->_('Total'),
                $this->getID()
            );
        }

        return $out;
    }

    /**
     * Special render method for UIKIT 3
     * @return string
     */
    private function renderUikit3(): string
    {
        $this->wrapper->setContent($this->renderInput() . $this->button->render());
        return $this->wrapper->render();
    }

    /**
     * Special render method for Bulma 1
     * @return string
     */
    private function renderBulma1(): string
    {
        $label = sprintf(
            '<span class="file-cta"><span class="file-label">%s</span></span>',
            $this->_('Select files')
        );

        $this->labelWrapper->setContent($this->append($label)->renderInput());
        $this->wrapper->setContent($this->labelWrapper->render());
        return $this->wrapper->render();
    }

    /**
     * Normalize a PHP ini-style size value (e.g. "8M", "2G", "512K") into a
     * space-separated number+unit string suitable for convertToBytes()
     * (e.g. "8 MB", "2 GB"). A bare number with no unit is treated as bytes.
     * @param string $value
     * @return string
     * @throws InvalidArgumentException if $value does not match a valid size format
     */
    public static function normalizeSizeUnit(string $value): string
    {
        $value = trim(strtoupper($value));

        // Zahl + optionale Einheit zerlegen
        if (!preg_match('/^(\d+(?:\.\d+)?)\s*([KMGTP]?)(B)?$/', $value, $matches)) {
            throw new InvalidArgumentException('Invalid size for input value: ' . $value);
        }

        $number = $matches[1];
        $unit   = $matches[2];

        // Keine Einheit → Bytes
        if ($unit === '') {
            return $number . 'B';
        }

        return $number .' '. $unit . 'B';
    }

    /**
     * Render the input field including additional notes for Validation used for file uploads
     * @return string
     */
    public function ___render(): string
    {
        $notes = &$this->notes_array;
        $isMultiple = $this->getMultiple();


        if (isset($notes['phpIniFilesize'], $notes['allowedFileSize'])) {
            $allowed = Inputfields::convertToBytes($notes['allowedFileSize']['value']);
            $ini = Inputfields::convertToBytes($notes['phpIniFilesize']['value']);
            unset($notes[$allowed <= $ini ? 'phpIniFilesize' : 'allowedFileSize']);
        }

        if (!$isMultiple) {
            unset($notes['allowedTotalFileSize']);
        }

        $fileSize = $notes['phpIniFilesize']['value'] ?? $notes['allowedFileSize']['value'] ?? null;

        if ($fileSize !== null) {
            $this->setAttribute('data-maxfilesize', Inputfields::convertToBytes($fileSize));
        }

        if ($isMultiple) {
            if (isset($notes['allowedFileNumber'])) {
                $this->setAttribute('data-uploadlimit', $notes['allowedFileNumber']['value']);
            }

            if (isset($notes['allowedTotalFileSize'])) {

                $this->setAttribute('data-maxtotalfilesize', Inputfields::convertToBytes($notes['allowedTotalFileSize']['value']));
            }
        }

        return parent::___render();
    }
}
