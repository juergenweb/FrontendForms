<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating the label for an input element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Label.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Label extends TextElements
{
    protected int $enableAsterisk = 1;
    protected bool $required = false;

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->enableAsterisk = $this->input_showasterisk; // from global settings
        $this->setTag('label');
        $this->setCSSClass('labelClass');
    }

    /**
     * Disable the markup of Asterisk
     * Needed for Checkbox multiple and Radio multiple to prevent the asterisk being shown on every option if field is required
     */
    public function disableAsterisk()
    {
        $this->enableAsterisk = 0;
    }

    /**
     * Render the label element
     * @return string
     */
    public function render(): string
    {
        $content = $this->getText();
        if ($this->getRequired()) {
            $this->setCSSClass('label_requiredClass');
            if ($this->enableAsterisk) {
                $content .= ($this->input_showasterisk) ? $this->___renderAsterisk() : '';
            }

        }
        $this->setContent($content);
        return parent::___render();
    }

    /**
     * Get the required status
     * @return boolean
     */
    public function getRequired(): bool
    {
        return $this->required;
    }

    /**
     * Set the required status
     * @return void
     */
    public function setRequired(): void
    {
        $this->required = true;
    }

    /**
     * Render the markup of the asterisk
     * Method is Hook-able
     * @return string
     */
    protected function ___renderAsterisk(): string
    {
        return '<span class="asterisk">*</span>';
    }


}
