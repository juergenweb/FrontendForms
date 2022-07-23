<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a button element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Button.php
 * Created: 03.07.2022
 */

class Button extends Element
{

    public function __construct($name = 'submit')
    {
        parent::__construct($name);
        $this->setTag('button');
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'submit'); // default is submit
        $this->setCSSClass('buttonClass');
        $this->setAttribute('value', $this->_('Send')); // default is "Send"
    }

    /**
     * Get the wrapper object for further manipulation on per field base
     * Use this method if you want to add custom attributes or remove attributes to and from the wrapper
     * @return Wrapper|null
     */
    public function getWrapper(): ?Wrapper
    {
        return $this->getWrap();
    }

    public function __toString()
    {
        return $this->___render();
    }

    /**
     * Creates a new wrapper object
     * @return Wrapper
     */
    protected function addWrapper(): Wrapper
    {
        return $this->wrap();
    }

    /**
     * Remove the button wrapper
     */
    protected function removeWrapper()
    {
        $this->removeWrap();
    }

    /**
     * Render the button
     * Use the value attribute as button text
     * @return string
     */
    public function ___render(): string
    {
        $this->setContent($this->getAttribute('value'));
        $button = $this->renderNonSelfclosingTag($this->getTag());
        if ($this->getWrapper()) {
            $this->getWrapper()->setContent($button);
            return $this->getWrapper()->___render();
        }
        return $button;
    }

}


