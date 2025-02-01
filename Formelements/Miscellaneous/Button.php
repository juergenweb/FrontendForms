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
    protected bool $showNoContent = false;
    protected bool $showAttributeValue = false;
    protected string|null $alternativeValue = null;
    protected string|int|bool $useAriaAttr = true; // whether to render area attributes or not

    public function __construct($name = 'submit')
    {
        parent::__construct($name);
        $this->setTag('button');
        $this->setAttribute('name', $name);
        $this->setAttribute('type', 'submit'); // default is submit
        $this->setCSSClass('buttonClass');
        $this->setAttribute('value', $this->_('Send'));
        $this->showNoContent(false);
        $this->showAttributeValue(true);
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
        return $this->render();
    }

    /**
     * Show the button if no value is present - true or false (default ist false)
     * @param bool $showNoContent
     * @return $this
     */
    public function showNoContent(bool $showNoContent): self
    {
        $this->showNoContent = $showNoContent;
        return $this;
    }

    /**
     * Add the button text as value attribute - true or false (default is true)
     * @param bool $showAttributeValue
     * @return $this
     */
    public function showAttributeValue(bool $showAttributeValue): self
    {
        $this->showAttributeValue = $showAttributeValue;
        return $this;
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
     * Method to enable/disable the usage of area attributes for better accessibility
     * @param bool $ariaAttr
     * @return $this
     */
    public function useAriaAttributes(bool $ariaAttr): self
    {
        $this->useAriaAttr = $ariaAttr;
        return $this;
    }

    /**
     * Render the button
     * Use the value attribute as button text
     * @return string
     */
    public function ___render(): string
    {
        $this->setContent($this->getAttribute('value'));
        $this->setAttribute('value',$this->getAttribute('value'));
        $button = $this->renderNonSelfclosingTag($this->getTag(), $this->showNoContent, $this->showAttributeValue);

        if ($this->getWrapper()) {
            $this->getWrapper()->setContent($button);
            return $this->getWrapper()->render();
        }
        return $button;
    }

}
