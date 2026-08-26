<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * FormValueStore
 *
 * Collects the sanitized values of a submitted form (see setValues(), called once
 * during Form::___isValid()) and exposes them for read access afterwards - as a
 * plain array, as a labeled array, or as a flattened string. Handles the special
 * case of file upload fields, which are not read from the regular values array
 * but from $_FILES/$_GET/the (currently unused) legacy storedFiles fallback.
 *
 * @package FrontendForms\Values
 */
final class FormValueStore
{
    private array $values = []; // array of all form values (key = name of the inputfield)

    public function __construct(private readonly Form $form)
    {
    }

    /**
     * Get the value of a single field by its name attribute
     * @param string $name
     * @return string|array|null
     */
    public function getValue(string $name): string|array|null
    {
        $name = $this->form->createElementName(trim($name));
        if ($this->getValues()) {
            // first check if the name exists
            if (isset($this->getValues()[$name])) {
                return $this->getValues()[$name];
            } else {
                if (isset($this->getValues()[$this->form->getID() . '-' . $name])) {
                    // check if name including form id prefix exists
                    return $this->getValues()[$this->form->getID() . '-' . $name];
                }
            }
            return null;
        }

        return null;
    }

    /**
     * Get all sanitized form values after form submission as an array
     * If there are sanitizers set for the form values, they will be applied
     * @param bool $buttonValue : If there are buttons set the value of the buttons will be applied too
     * @return array|null
     */
    public function getValues(bool $buttonValue = false): array|null
    {
        // add button elements to inputfields if set
        $elements = $this->form->getNamesOfInputFields();

        foreach ($this->form->getFormElementsByClass('Button') as $button) {
            if ($button->hasAttribute('value')) {
                if ($button->hasAttribute('name')) {
                    if ($buttonValue) {
                        $elements[] = $button->getAttribute('name');
                    }
                }
            }
        }

        $values = [];

        $method = strtolower($this->form->getAttribute('method'));

        foreach ($elements as $key) {

            // remove [] from name attribute if present
            $key = str_replace('[]', '', $key);

            // check if inputfield is a file upload field
            $formElement = $this->form->getFormelementByName($key);
            if ($formElement instanceof InputFile) {

                $multiplefiles = [];
                if ($this->form->getStoredFiles()) {
                    $pathFileArray = $this->form->getStoredFiles();
                    $filesArray = [];
                    foreach ($pathFileArray as $path) {
                        // output only the basename without the whole path
                        $value = $this->form->wire('sanitizer')->filename(pathinfo($path, PATHINFO_BASENAME), true);
                        $filesArray[] = strtolower($value);
                    }

                    $values[$key] = $filesArray;
                } else {
                    if ($method == 'post') {
                        $files = $_FILES[$key]['name'];
                    } else {
                        $files = $_GET[$key];
                    }
                    if (is_array($files)) {
                        // multiple upload field
                        foreach ($files as $filename) {
                            $multiplefiles[] = strtolower($this->form->wire('sanitizer')->filename($filename, true));
                        }
                        $values[$key] = $multiplefiles;
                    } else {
                        // single upload field
                        $value = $this->form->wire('sanitizer')->filename($files, true);
                        $values[$key] = strtolower($value);
                    }

                }

            } else {
                if (array_key_exists($key, $this->values)) {
                    $values[$key] = $this->values[$key];
                }
            }
        }

        return $values; // array
    }

    /**
     * Same as getValues() but outputs the labels too
     * @param bool $buttonValue
     * @return array
     */
    public function getValuesWithLabels(bool $buttonValue = false): array
    {
        $values = [];
        $elements = $this->getValues($buttonValue);
        foreach ($elements as $name => $value) {
            $formElement = $this->form->getFormelementByName($name);
            $label = $formElement->getLabel()->getContent();
            $values[$name] = ['label' => $label, 'value' => $value];
        }
        return $values;
    }

    /**
     * Convert post-values to a string
     * @param bool $showButtonValues
     * @return string
     */
    public function getValuesAsString(bool $showButtonValues = false): string
    {
        $postData = $this->form->flattenMixedArray($this->getValues($showButtonValues));
        $dataAttributes = array_map(function ($value, $key) {
            return $key . '=' . $value;
        }, array_values($postData), array_keys($postData));
        return implode(', ', $dataAttributes);
    }

    /**
     * Collect all form values into the internal store. All sanitizer methods
     * configured on an input element are applied before the value is stored.
     * Also populates a "{fieldname}value" mail placeholder for each value.
     * @return void
     */
    public function setValues(): void
    {
        $method = strtolower($this->form->getAttribute('method'));

        $post_values = $this->form->wire('input')->$method;
        // get buttons
        foreach ($this->form->getFormElementsByClass('Button') as $button) {
            if ($button->hasAttribute('value')) {
                if ($button->hasAttribute('name')) {
                    $post_values[$button->getAttribute('name')] = $button->getAttribute('value');
                }
            }
        }

        $values = [];
        foreach ($post_values as $name => $value) {

            // grab formelement by its name attribute
            $element = $this->form->getFormelementByName($name);

            if ($element) {

                // Run all sanitizer methods over the value, chaining them so
                // each sanitizer operates on the previous one's result
                if (method_exists($element, 'getSanitizers')) {
                    $sanitizers = $element->getSanitizers();
                    $value = $post_values->$name;
                    foreach ($sanitizers as $sanitizer) {
                        $value = $element->wire('sanitizer')->$sanitizer($value);
                    }
                } else {
                    $value = $post_values->$name;
                }
            } else {
                // no element exists -> get the value directly from the POST array
                $value = $post_values->$name;
            }
            $values[$name] = $value;

            // set all form values to a placeholder
            $fieldName = str_replace($this->form->getID() . '-', '', $name) . 'value';

            $this->form->setMailPlaceholder($fieldName, $value);
        }

        $this->values = $values;
    }
}