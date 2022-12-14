<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating HTML elements (tags)
 * Extends from Wire to be able to make some methods hook-able
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Tag.php
 * Created: 03.07.2022
 */

// include Functions.php

use ProcessWire\Wire as Wire;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;


abstract class Tag extends Wire
{
    const MULTIVALUEATTR = ['class', 'rel', 'style']; // array of all attributes that can have more than 1 value
    const BOOLEANATTR = [   // array of all boolean attributes
        'allowfullscreen',
        'allowpaymentrequest',
        'async',
        'autofocus',
        'autoplay',
        'checked',
        'controls',
        'default',
        'disabled',
        'formnovalidate',
        'hidden',
        'ismap',
        'itemscope',
        'loop',
        'multiple',
        'muted',
        'nomodule',
        'novalidate',
        'open',
        'playsinline',
        'readonly',
        'required',
        'reversed',
        'selected',
        'truespeed'
    ];

    /**
     * Array of class names of classes, where the value attribute can have multiple values and is not inside the MULTIVALUEATTR array
     */
    const MULTIVALCLASSES = [
        'SelectMultiple',
        'InputCheckboxMultiple'
    ];

    protected array $attributes = []; // array that holds all attributes of an element as a multilevel array
    protected string $tag = 'div'; // default type of tag
    protected string $content = ''; // the content between open and closing tag if element is not self-closing
    protected object $classes; // all pre-defined css-classes as stdClass object
    protected string $prepend = ''; // markup before the tag
    protected string $append = ''; // markup after the tag

    /* Properties of module configuration - make them all protected */
    /* Input type is the same as they are stored in the db */
    /* These properties are reachable in all descending classes */
    protected int|string $input_showasterisk = 0;
    protected string $input_requiredHintPosition = '';
    protected string $input_requiredText = '';
    protected string $input_alertSuccessText = '';
    protected string $input_alertErrorText = '';
    protected array|null $input_wrappers = [];
    protected int|string $input_wrapperFormElements = 0;
    protected string $input_wrapperFormElementsCSSClass = '';
    protected int|string $input_removeJS = 0;
    protected int|string $input_removeCSS = 0;
    protected int|string $input_addHTML5req = 0;
    protected string $input_framework = 'none.json';
    protected array $input_appendLabel = [];
    protected string $input_emailTemplate = '';
    protected string $input_privacy = '';
    protected int $input_maxAttempts = 0;
    protected int $input_minTime = 0;
    protected int $input_maxTime = 0;
    protected int|string $input_logFailedAttempts = 0;
    protected int|string $input_honeypot = 0;
    protected string $input_preventIPs = '';
    protected string $input_uploadPath = '';

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        //  set the property values from the configuration settings from DB and sanitize it
        foreach ($this->wire('modules')->getConfig('FrontendForms') as $key => $value) {
            if($value != null)
                $this->$key = $value;
        }
        $this->input_uploadPath = $this->wire('config')->paths->siteModules . 'FrontendForms/temp_uploads/';
        // Extract values from configuration arrays to single properties of type bool
        $this->useInputWrapper = in_array('inputwrapper', $this->input_wrappers);
        $this->useFieldWrapper = in_array('fieldwrapper', $this->input_wrappers);
        $this->appendcheckbox = in_array('appendcheckbox', $this->input_appendLabel);
        $this->appendradio = in_array('appendradio', $this->input_appendLabel);

        // load the json file from CSSClass directory
        $this->classes = json_decode(file_get_contents($this->wire('config')->paths->FrontendForms . 'CSSClasses' . DIRECTORY_SEPARATOR . $this->input_framework));
    }


    /**
     * Flatten a mixed array (strings and arrays)
     * @param array $data
     * @return array
     */
    public function flattenMixedArray(array $data): array
    {
        $return = array();
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $return[$key] = implode(',', $value);
            } else {
                $return[$key] = $value;
            }
        }
        return $return;
    }

    /**
     * An method only to output print_r in formatted way for better readability
     * Only for dev purposes
     * @param string|array|object $str
     */
    public static function pre(mixed $str)
    {
        echo "<pre>";
        print_r($str);
        echo "</pre>";
    }

    /**
     * Check if form was submitted or not
     * @return bool -> true: the form was submitted, false: the form was not submitted
     * It is the opposition of notSubmitted() method
     */
    protected function isSubmitted(): bool
    {
        if (!empty($this->getServerMethod())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if form was submitted or not
     * @return bool -> true: the form was not submitted, false: the form was submitted
     */
    protected function notSubmitted(): bool
    {
        return (!$this->isSubmitted());
    }

    /**
     * Get the id of the element - this is only an alias method
     * @return string|null
     */
    public function getID(): ?string
    {
        return $this->getAttribute('id');
    }

    /**
     * Get the value of an attribute by its name
     * @param string $attributeName
     * @return string|array|null
     */
    public function getAttribute(string $attributeName): mixed
    {
        $key = $this->sanitizeAttributeName($attributeName);
        if (array_key_exists($key, $this->getAttributes())) {
            return $this->getAttributes()[$key];
        }
        return null;
    }

    /**
     * Sanitize the attribute name
     * Must be all lowercase
     * @param string $name - the name of the attribute (fe class, href,...)
     * @return string - return the sanitized name
     */
    protected function sanitizeAttributeName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * Get all attributes of a tag object
     * @return array
     */
    protected function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Set multiple attributes at once as an assoc. array
     * @param array $attributes - fe (['class' => 'myClass', 'id' => 'myId'])
     * @return void
     */
    public function setAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($key) {
                $this->setAttribute($key, $value);
            } else {
                $this->setAttribute($value);
            }
        }
    }

    /**
     * Set an attribute
     * @param string $key - the attribute name (fe. href, name, id,..)
     * @param string|array|null $value - the value as single value (fe. href) or if multiple values are allowed as a string separated by whitespace (fe class values)
     * value can also be null if attribute can have no value (fe checked, selected, multiple,..) so you can write setAttribute('multiple') or setAttribute('multiple', 'multiple')
     * @return $this
     */
    public function setAttribute(string $key, mixed $value = null): self
    {
        $key = $this->sanitizeAttributeName($key);
        // value must be string, array or null
        if (!is_null($value)) {
            if ((!is_string($value)) && (!is_array($value))) {
                return $this;
            }
            if (in_array($this->className(), self::MULTIVALCLASSES)) {
                if (in_array($key, self::MULTIVALUEATTR)) {
                    if (is_string($value)) {
                        // check if string contains whitespace between the words (fe class1 class2)
                        if ($value == trim($value) && str_contains($value, ' ')) {
                            $value = explode(' ', $value); // create array of string separated by whitespace
                        } // check if string contains semicolon between the words (fe color:yellow;font-weight:bold)
                        elseif (strpos($value, ';')) {
                            $assocArray = array_filter(explode(';',
                                $value)); // create array of string separated by semicolon
                            //create an assoc array
                            $value = [];
                            foreach ($assocArray as $v) {
                                $attr = explode(':', $v);
                                $value[$attr[0]] = $attr[1];
                            }
                            $value = array_filter($value);
                        }
                    } else {
                        $value = array_map('trim', $value);// trim all array values
                    }
                } else {
                    if (is_string($value)) {
                        $value = trim($value);
                    } else {
                        $value = array_map('trim', $value);// trim all array values
                    }
                }
            }
            if (in_array($key, self::MULTIVALUEATTR)) {
                //get all values from this attributes
                $oldValues = $this->getAttributes()[$key] ?? [];
                if (is_string($value)) {
                    $value = [$value];
                }
                $multiValues = array_unique(array_merge($oldValues, $value));
                $this->attributes = array_merge($this->getAttributes(), [$key => $multiValues]);
            } else {
                $this->attributes = array_merge($this->getAttributes(), [$key => $value]);
            }
        } else {
            // boolean attributes
            if ((str_starts_with($key, 'data-uk-')) || (str_starts_with($key, 'uk-')) || (in_array($key,
                    self::BOOLEANATTR))) {
                $this->attributes = array_merge($this->getAttributes(), [$key => $key]);
            }
        }
        return $this;
    }

    /**
     * Remove attribute with a specific name
     * @param string $attributeName (fe href, id,...)
     * @return $this
     */
    public function removeAttribute(string $attributeName): self
    {
        $name = $this->sanitizeAttributeName($attributeName);
        unset($this->getAttributes()[$name]);
        return $this;
    }

    /**
     * Add a markup before this tag (fe div tag for special grid etc.)
     * @param string $markup - fe <div class="grid">
     * @return $this
     */
    public function prepend(string $markup): self
    {
        $this->prepend = $markup;
        return $this;
    }

    /**
     * Remove markup from prepend position
     * @return $this;
     */
    public function removePrepend(): self
    {
        $this->prepend = '';
        return $this;
    }

    /**
     * Add a markup after the tag
     * @param string $markup - fe </div>
     * @return $this
     */
    public function append(string $markup): self
    {
        $this->append = $markup;
        return $this;
    }

    /**
     * Remove markup from append position
     * @return $this;
     */
    public function removeAppend(): self
    {
        $this->append = '';
        return $this;
    }

    /*******
     * ALIAS *
     *********/

    /**
     * Add the pre-defined css class to an element (object) if present
     * @param string $className
     * @return $this;
     */
    protected function setCSSClass(string $className): self
    {
        $class = $this->getCSSClass($className);
        if ((!is_null($class)) && ($class != '')) {
            $this->setAttribute('class', $class);
        }
        return $this;
    }

    /**
     * Get the value of a CSS class as defined in the json file inside the CSS class directory (if present)
     * @param string $className
     * @return string|null
     */
    protected function getCSSClass(string $className): ?string
    {
        if (isset($this->classes->$className)) {
            $inputName = 'input_' . $className;
            // if a default class was overwritten - use it instead
            if (isset($this->$inputName) && (!empty($this->$inputName))) {
                return $this->$inputName;
            }
            return $this->classes->$className;
        }
        return null;
    }

    /**
     * Remove the pre-defined css class from an element (object) if present
     * @param string $className
     * @return $this;
     */
    protected function removeCSSClass(string $className): self
    {
        $class = $this->getCSSClass($className);
        if ((!is_null($class)) && ($class != '')) {
            $this->removeAttributeValue('class', $class);
        }
        return $this;
    }

    /**
     * Remove a specific value of a specific attribute
     * If it is an attribute where only 1 value is allowed (fe. id), then the complete attribute will be removed
     * @param string $attributeName -> the attribute name (fe class)
     * @param string|null $attributeValue (fe my class)
     * @return $this
     * TODO: check for simplification
     */
    public function removeAttributeValue(string $attributeName, ?string $attributeValue = null): self
    {
        $key = $this->sanitizeAttributeName($attributeName);
        if ($attributeValue) {
            $value = trim($attributeValue);
            // remove values form assoc. arrays like style attribute
            if ($this->isAssoc($this->getAttributes()[$key])) {
                if (array_key_exists($attributeValue, $this->attributes[$key])) {
                    if (in_array($key, self::MULTIVALUEATTR)) {
                        unset($this->attributes[$key][$value]);
                    }
                }
            } else {
                // remove values from non assoc. arrays like class, rel, id,...
                if (array_key_exists($key, $this->getAttributes())) {
                    if (in_array($value, $this->attributes[$key])) {
                        if (in_array($key, self::MULTIVALUEATTR)) {
                            if (count($this->attributes[$key]) > 1) {
                                $this->attributes[$key] = array_diff($this->attributes[$key], [$value]);
                            } else {
                                unset($this->attributes[$key]);
                            }
                        } else {
                            unset($this->attributes[$key]);
                        }
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Check if array is assoc.
     * @param array $array - the array to check
     * @return boolean - true if it is assoc. array
     */
    protected function isAssoc(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;
    }

    /**
     * Check if an element has a specific attribute (fe href, class,...)
     * @param string $attributeName
     * @return bool
     */
    public function hasAttribute(string $attributeName): bool
    {
        return (array_key_exists($this->sanitizeAttributeName($attributeName), $this->getAttributes()));
    }

    /**
     * Base render method for self-closing HTML tags
     * @param string $tag - the self-closing tag itself (fe input, hr,...)
     * @return string
     */
    protected function renderSelfclosingTag(string $tag): string
    {
        return $this->prepend . '<' . $tag . $this->attributesToString() . '>' . $this->append;
    }

    /**
     * Renders all attributes as a string
     * @param boolean $selfClosing - if true the value attribute will be not rendered as an attribute.
     * @return string
     */
    protected function attributesToString(bool $selfClosing = true): string
    {
        $allAttributes = $this->getAttributes();
        //remove value attribute from attributes array if self-closing tag
        if ((!$selfClosing) && ($this->getTag() != 'option')) {
            unset($allAttributes['value']);
        }
        $out = '';
        $attributes = [];
        if (count($allAttributes)) {
            foreach ($allAttributes as $name => $value) {
                if (is_array($value)) {
                    // if value is assoc array than chain the values without whitespace as separator
                    if ($this->isAssoc($value)) {
                        $newArray = [];
                        foreach ($value as $key => $val) {
                            $newArray[] = $key . ':' . $val;
                        }
                        $value = implode(';', $newArray);
                    } else {
                        // if numeric add a whitespace as separator between the attribute values ( fe class, rel,..)
                        $value = implode(' ', $value);
                    }
                }
                if (in_array($value, self::BOOLEANATTR)) {
                    $attributes[] = $value;
                } else {
                    $attributes[] = $name . '="' . $value . '"';
                }
            }

            $out = ' ' . implode(' ', $attributes);
        }
        return $out;
    }

    /**
     * Get the tag of an HTML element
     * @return string (fe. a, input, ...)
     */
    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * Set the tag of an HTML element
     * @param string $tag (fe. a, input, ...)
     * @return $this
     */
    public function setTag(string $tag): self
    {
        $this->tag = $this->sanitizeTagName($tag);
        return $this;
    }

    /**
     * Sanitize the tag name
     * Must be all lowercase
     * @param string $name - the name of the tag (fe div, span,...)
     * @return string - return the sanitized tag name
     */
    protected function sanitizeTagName(string $name): string
    {
        return strtolower(trim($name));
    }

    /**
     * Base render method for none-self-closing HTML tags
     * @param string $tag - the non-self-closing tag itself (fe div, form,...)
     * @param boolean $showNoContent - tag should be displayed if there is no content (true) or not (false)
     * @return string
     */
    protected function renderNonSelfclosingTag(string $tag, bool $showNoContent = false): string
    {
        $out = '';
        $show = match ($this->getContent()) {
            null, '' => $showNoContent,
            default => true,
        };
        if ($show) {
            $out .= $this->prepend . '<' . $tag . $this->attributesToString(false) . '>' . $this->getContent() . '</' . $tag . '>' . $this->append;
        }
        return $out;
    }

    /**
     * Get the value of a content if present
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * Set the value of the content between to open and closing tag
     * @param string|null $content
     */
    public function setContent(?string $content)
    {
        if (!is_null($content)) {
            $this->content = $content;
        }
    }

    /**
     * Get the server method after form was submitted
     * @return array $_GET or $_Post
     */
    protected function getServerMethod(): array
    {
        return ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;
    }

}
