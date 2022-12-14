<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * General abstract class for each HTML element that can be created via the Tag class.
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Element.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

abstract class Element extends Tag
{
    protected ?Wrapper $wrapper = null; // wrapper object

    /**
     * @param string|null $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct();
        if (is_string($id)) {
            $this->setAttribute('id', $id);// set id if it was set inside the constructor
        }
    }

    /**
     * Add a wrapper around an element (tag)
     * By default it is a div container, but you can change it to whatever you want
     * @return Wrapper - returns a wrapper object
     */
    public function wrap(): Wrapper
    {
        $this->wrapper = new Wrapper();
        return $this->wrapper;
    }

    /**
     * Remove a wrapper if it is present
     * @return void
     */
    public function removeWrap(): void
    {
        unset($this->wrapper);
    }

    /**
     * Returns the wrapper object if present
     * @return Wrapper|null
     */
    public function getWrap(): ?Wrapper
    {
        return $this->wrapper;
    }
}
