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
     */

    use Exception;
    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Textarea extends Inputfields
    {

        protected string|int|bool $useCharacterCounter = false; // show or hide char counter below the textarea if max length is set
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
            $counter = ($use) ? '1' : '0';
            $this->setAttribute('data-charactercounter', $counter);
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
            $this->setContent($this->getAttribute('value'));
            // check if counter should be visible
            $counter = '';

            if ($this->useCharacterCounter) {
                // check if max length limitation is set, otherwise a counter does not make sense
                if (array_key_exists('lengthMax', $this->getRules())) {
                    // set max characters
                    $maxChars = (string)$this->getRules()['lengthMax']['options'][0];
                    $this->charCounter->setAttribute('id', $this->getID() . '-char_count');
                    $this->charCounter->setAttribute('data-maxreached', $this->_('You have reached the maximum number of characters.'));
                    $this->charCounter->setContent(sprintf($this->_('You have %s characters left.'), '<span>' . $maxChars . '</span>'));
                    $this->charCounter->prepend('<div>');
                    $this->charCounter->append('</div>');
                    $counter = $this->charCounter->render();
                }
            }
            $textarea = $this->renderNonSelfclosingTag($this->getTag(), true);
            return $textarea . $counter;
        }

    }
