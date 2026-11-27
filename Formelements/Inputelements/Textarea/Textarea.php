<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a textarea element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Textarea.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Textarea extends Inputfields
{
    use TraitInputfields;

    protected bool $useCharacterCounter = false; // show or hide char counter below the textarea if max length is set
    protected TextElements $charCounter; // the character counter text object

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('textarea');
        $this->setAttribute('rows', '5'); // default is 5
        $this->setCSSClass('textareaClass');
        $this->removeSanitizers();// remove all sanitizers by default
        $this->setSanitizer('textarea'); // add sanitizer textarea by default for security reasons

        $this->charCounter = new TextElements('char_count');
        $this->charCounter->setAttribute('class', 'fc-counter');
        $this->charCounter->setTag('span');
    }

    /**
     * Enable/disable the usage/showing of a char counter below the textarea
     * @param bool $use
     * @return $this
     */
    public function useCharacterCounter(bool $use = true): self
    {
        $this->useCharacterCounter = $use;
        $this->setAttribute('data-charactercounter', $use ? '1' : '0');
        return $this;
    }

    /**
     * Get the char counter object - can be used to change some value and to influence the markup
     * @return \FrontendForms\TextElements
     */
    public function getCharacterCounter(): TextElements
    {
        return $this->charCounter;
    }

    /**
     * Render the textarea input
     * @return string
     */
    public function ___renderTextarea(): string
    {
        // Escape the value before it becomes the content of the textarea: unlike attribute
        // values (escaped in Tag::attributesToString()), Tag content is output as it is,
        // so a default or submitted value containing "</textarea>" would otherwise close
        // the textarea and everything after it would be rendered as HTML (XSS).
        // A textarea decodes character references, so the text is displayed unchanged.
        // ENT_SUBSTITUTE: without it htmlspecialchars() returns an empty string for
        // invalid UTF-8, and the whole submitted text would disappear.
        $this->setContent(htmlspecialchars((string) $this->getAttribute('value'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

        $counter = '';

        if ($this->useCharacterCounter && array_key_exists('lengthMax', $this->getRules())) {
            $maxChars = (string) ($this->getRules()['lengthMax']['options'][0] ?? '');

            $this->charCounter->setAttribute('id', $this->getID() . '-char_count');
            $this->charCounter->setAttribute('data-maxreached', $this->_('You have reached the maximum number of characters.'));
            $this->charCounter->setContent(sprintf($this->_('You have %s characters left.'), '<span>' . $maxChars . '</span>'));
            $this->charCounter->prepend('<div>');
            $this->charCounter->append('</div>');

            $counter = $this->charCounter->render();
        }

        return $this->renderNonSelfclosingTag($this->getTag(), true) . $counter;
    }

}
