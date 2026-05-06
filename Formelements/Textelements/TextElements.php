<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating text elements inside the form (fe. labels, description, notes,..)
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TextElements.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class TextElements extends Element
{


    /**
     * @param string|null $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
    }

    /**
     * Set the text between the opening and closing tag
     * @param string $text
     * @return void
     */
    public function setText(string $text): void
    {
        $this->setContent($text);
    }

    /**
     * Get the text for the text element
     * @return string
     */
    public function getText(): string
    {
        return $this->getContent();
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Render the text element
     * @return string
     */
    public function ___render(): string
    {
        $rendered = $this->renderNonSelfclosingTag($this->getTag());

        if ($this->wrapper) {
            $this->wrapper->setContent($rendered);
            return $this->wrapper->render();
        }

        return $rendered;
    }

}
