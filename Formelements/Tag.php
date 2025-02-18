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


use ProcessWire\FrontendForms;
use ProcessWire\Wire as Wire;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;


\ProcessWire\wire('classLoader')->addNamespace('ProcessWire', __DIR__);

abstract class Tag extends Wire
{
    const MULTIVALUEATTR = [
        'class',
        'rel',
        'style',
        'aria-describedby'
    ]; // array of all attributes that can have more than 1 value
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
    protected bool|null $useInputWrapper = true; // whether the input wrapper should be user or not
    protected bool|null $useFieldWrapper = true;// whether the field wrapper should be user or not
    protected bool $appendcheckbox = false; // whether the checkbox should be appended after the label or not
    protected bool $appendradio = false;  // whether the radio should be appended after the label or not
    protected string $uploadPath = '';
    protected array $frontendforms = []; // array that hold all module configuration values of this module

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();

        //  set the property values from the configuration settings from DB and sanitize it
        foreach ($this->wire('modules')->getConfig('FrontendForms') as $key => $value) {
            $this->frontendforms[$key] = $value;
        }

        $this->uploadPath = $this->wire('config')->paths->siteModules . 'FrontendForms/temp_uploads/';
        // Extract values from configuration arrays to single properties of type bool
        $this->useInputWrapper = in_array('inputwrapper', $this->frontendforms['input_wrappers']);
        $this->useFieldWrapper = in_array('fieldwrapper', $this->frontendforms['input_wrappers']);
        $this->appendcheckbox = in_array('appendcheckbox', $this->frontendforms['input_appendLabel']);
        $this->appendradio = in_array('appendradio', $this->frontendforms['input_appendLabel']);

        // set the default path to the custom CSS files directory under site/assets/...
        $customframeworkpath = $this->wire('config')->paths->assets .'files/FrontendForms/frameworks/';

        if(array_key_exists('input_customframeworkpath', $this->frontendforms) && ($this->frontendforms['input_customframeworkpath'] != '')){
            $customframeworkpath = $this->frontendforms['input_customframeworkpath'];
        }
        // load the json file from CSSClass directory
        $this->classes = json_decode(file_get_contents(FrontendForms::getCSSClassFile($this->frontendforms['input_framework'], $customframeworkpath)));

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
     * A method only to output print_r in formatted way for better readability
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
     * Check if form was submitted or not - works only on POST submissions
     * @return bool -> true: the form was submitted, false: the form was not submitted
     * It is the opposition of notSubmitted() method
     */
    protected function isSubmitted(): bool
    {
        return (!empty($_POST));
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
            // convert numbers to string
            if (is_numeric($value)) {
                $value = (string)$value;
            }
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
        $attributes = $this->getAttributes();
        unset($attributes[$name]);
        $this->attributes = $attributes;
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
    public function removeCSSClass(string $className): self
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
            // check if the attribute exists in the attributes array
            if (isset($this->getAttributes()[$key])) {
                // remove values form assoc. arrays like style attribute
                if (is_array($this->getAttributes()[$key]) && ($this->isAssoc($this->getAttributes()[$key]))) {
                    if (array_key_exists($attributeValue, $this->attributes[$key])) {
                        if (in_array($key, self::MULTIVALUEATTR)) {
                            unset($this->attributes[$key][$value]);
                        }
                    }
                } else {
                    // remove values from non assoc. arrays like class, rel, id,...
                    if (array_key_exists($key, $this->getAttributes())) {
                        //if (in_array($value, $this->getAttributes())) {
                        if (in_array($key, self::MULTIVALUEATTR)) {
                            if (count($this->attributes[$key]) > 1) {
                                $this->attributes[$key] = array_diff($this->attributes[$key], [$value]);
                            } else {
                                unset($this->attributes[$key]);
                            }
                        } else {
                            unset($this->attributes[$key]);
                        }
                        //}
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
                    if ($name != 'type') {
                        $attributes[] = $value; // checked OK
                    } else {
                        // allow attribute value hidden on type=hidden
                        $attributes[] = $name . '="' . $value . '"';
                    }
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
     * @param bool $showAttributeValue - by default the value attribute will be displayed as the content -
     * here you can add it as attribute value too
     * @return string
     */
    protected function renderNonSelfclosingTag(
        string $tag,
        bool   $showNoContent = false,
        bool   $showAttributeValue = false
    ): string
    {
        $out = '';
        $show = match ($this->getContent()) {
            null, '' => $showNoContent,
            default => true,
        };
        if ($show) {
            $out .= $this->prepend . '<' . $tag . $this->attributesToString($showAttributeValue) . '>' . $this->getContent() . '</' . $tag . '>' . $this->append;
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
