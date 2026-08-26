<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a markup element
 * This class does not have a lot of methods, because it is only for adding a string containing some
 * HTML elements to the form
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Markup.php
 * Created: 29.06.2024
 */

use ProcessWire\Wire;

class Markup extends Wire
{
    protected string $markup = '';

    /**
     * Markup elements hold a raw HTML string, not a real element with
     * attributes/config, so nothing from the base Wire constructor is
     * needed here.
     */
    public function __construct()
    {
    }

    /**
     * Set the raw HTML markup string
     * @param string $markup
     * @return $this
     */
    public function setMarkup(string $markup): self
    {
        $this->markup = $markup;
        return $this;
    }

    /**
     * Get the raw HTML markup string
     * @return string
     */
    public function getMarkup(): string
    {
        return $this->markup;
    }

    // All those function needs to be defined, but they have no effect
    // If they are not defined, an error will occur during the rendering process of the form

    /**
     * No-op: a Markup element has no real HTML attributes to set
     * @param string $name
     * @param string $attribute
     * @return void
     */
    public function setAttribute(string $name, string $attribute): void
    {

    }

    /**
     * Do not check for conditions
     */
    public function containsConditions(): bool
    {
        return false;
    }

    /**
     * No-op: a Markup element has no id of its own
     * @return void
     */
    public function getID(): void
    {

    }

    /**
     * No-op: a Markup element has no real HTML attributes to read
     * @return void
     */
    public function getAttribute(): void
    {

    }

    /**
     * Render the raw HTML markup string as-is
     * @return string
     */
    public function ___render(): string
    {
        return $this->markup;
    }

}