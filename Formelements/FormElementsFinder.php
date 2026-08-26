<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Collaborator class that searches, filters, counts and (in one case)
 * de-duplicates entries in a Form's own $formElements array.
 *
 * Holds a live PHP reference to the Form's $formElements property (passed
 * in via the constructor), so removeMultipleEntriesByClass() - the one
 * mutating method here - correctly removes entries from the Form's own
 * array, not a disconnected copy.
 *
 * Created by Claude AI as part of a Form.php size-reduction refactor.
 */

class FormElementsFinder
{
    /** @var array Live reference to the owning Form's $formElements property */
    protected array $formElements;

    /**
     * @param array $formElements - passed by reference from the owning Form
     */
    public function __construct(array &$formElements)
    {
        $this->formElements = &$formElements;
    }

    /**
     * Get all included classes of the form fields
     * For usage in body template of emails
     * @return array
     */
    public function getFormFieldClasses(): array
    {
        $classes = [];
        foreach ($this->formElements as $fieldObject) {
            $classes[] = $fieldObject->className();
        }
        return $classes;
    }

    /**
     * Check if an input field with a specific name is present the current form (but not if it has a value)
     * @param string $fieldName
     * @return bool
     */
    public function formfieldExists(string $fieldName): bool
    {
        $fieldName = strtolower(trim($fieldName));
        foreach ($this->formElements as $element) {
            if (strtolower((string) $element->getAttribute('name')) === $fieldName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get a specific element of the form by entering the name of the element as parameter
     * With this method you can grab and manipulate a specific element
     * @param string $name - the name attribute of the element (fe email)
     * @return object|bool - the form element object or false if not found
     */
    public function getFormelementByName(string $name): object|bool
    {
        return current(array_filter($this->formElements, function ($e) use ($name) {
            return $e->getAttribute('name') == $name;
        }));
    }

    /**
     * Get the position of a certain form element inside the form elements array
     * This returns the number of the key
     * @param $element
     * @return int|string|void
     */
    public function getFormElementsPosition($element)
    {
        $name = $element->getAttribute('name');
        foreach ($this->formElements as $key => $formField) {
            if ($formField->getAttribute('name') == $name) {
                return $key;
            }
        }
    }

    /**
     * Get all elements of the form that are an object of a specific class
     * Returns an array containing all objects of the given class (e.g., all Button elements)
     * @param string $class
     * @return array
     */
    public function getFormElementsByClass(string $class): array
    {
        // remove namespace first if set
        if (str_contains($class, '\\')) {
            $class = substr(strrchr($class, '\\'), 1);
        }

        $items = [];
        foreach ($this->formElements as $element) {
            $className = substr(strrchr(get_class($element), '\\'), 1);
            if ($className == $class) {
                $items[] = $element;
            }
        }
        return $items;
    }

    /**
     * Count how many elements of a given class are present in the form
     * @param string $className
     * @return int
     */
    public function formContainsElementByClass(string $className): int
    {
        $className = '\\FrontendForms\\' . $className;
        return (count(array_filter($this->formElements, function ($entry) use ($className) {
            return ($entry instanceof $className);
        })));
    }

    /**
     * Get all form element objects of a given class as an array
     * @param string $className
     * @return array
     */
    public function getElementsbyClass(string $className): array
    {
        $elements = [];
        if ($this->formContainsElementByClass($className)) {
            $className = '\\FrontendForms\\' . $className;
            $elements[] = array_filter($this->formElements, function ($entry) use ($className) {
                return ($entry instanceof $className);
            });
        }
        return $elements;
    }

    /**
     * If there are multiple instances of a given class, remove all except the last one
     * This is useful if only one instance is allowed, but there are multiple instances
     * Returns the key of the last item, which will not be deleted (unset)
     * @param string $className
     * @return int|null
     */
    public function removeMultipleEntriesByClass(string $className): null|int
    {
        // there are too many PrivacyText elements, only one is allowed per form -> remove all except the last one
        $fullClassName = '\\FrontendForms\\' . $className;
        $matchingKeys = array_keys(array_filter($this->formElements, function ($entry) use ($fullClassName) {
            return ($entry instanceof $fullClassName);
        }));

        if (!$matchingKeys) {
            return null;
        }

        $lastKey = array_pop($matchingKeys);
        foreach ($matchingKeys as $key) {
            unset($this->formElements[$key]);
        }
        return $lastKey;
    }

    /**
     * Get the position of a certain element inside the formElements array by its name
     * @param string $nameAttribute
     * @return int|string
     */
    public function getElementPositionByName(string $nameAttribute)
    {
        // find the object inside the array by its name
        $positon = 0;
        foreach ($this->formElements as $pos => $element) {
            if ($element->getAttribute('name') === $nameAttribute) {
                return $pos;
            }
        }
        return $positon;
    }

    /**
     * Return the names of all input fields inside a form as an array
     * @return array
     */
    public function getNamesOfInputFields(): array
    {
        $elements = [];
        if ($this->formElements) {
            foreach ($this->formElements as $element) {
                if (is_subclass_of($element, 'FrontendForms\Inputfields')) {
                    $elements[] = $element->getAttribute('name');
                }
            }
        }
        return array_filter($elements);
    }
}