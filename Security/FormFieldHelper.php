<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Wire;
use ProcessWire\WireException;
use ProcessWire\WireInputData;

/**
 * Helper for form field processing: filtering real input elements out of
 * a mixed list of form elements, and sanitizing/re-populating submitted
 * POST values back into their corresponding elements.
 *
 * This is deliberately separate from the security guard classes (timing,
 * attempts, double-submission, CSRF) since it performs data processing
 * rather than a pass/fail security check.
 */
class FormFieldHelper extends Wire
{
    /**
     * @param WireInputData $input Submitted form input data.
     */
    public function __construct(
        private readonly WireInputData $input
    ) {
        parent::__construct();
    }

    /**
     * Filter out all non-input elements (buttons, plain text, ...) from a
     * list of form elements.
     *
     * @param array $formElements All elements currently registered on the form.
     *
     * @return array Only the elements that represent actual input fields.
     */
    public function getRealInputFields(array $formElements): array
    {
        return array_values(array_filter(
            $formElements,
            fn ($element) => $element instanceof Inputfields
        ));
    }

    /**
     * Retrieve a submitted POST value for the given form element and write
     * it back into the element's "value" attribute, so it can be
     * re-displayed after a failed validation.
     *
     * Explicit per-value sanitization (via the sanitizer's string()
     * method) is only applied to multi-value fields (e.g. checkboxes,
     * SelectMultiple) - for all other field types, the value is used as
     * returned by WireInputData::get() without further processing here.
     * This is safe because the value is only ever written back as an
     * HTML attribute, which is escaped at render time regardless (see
     * Tag::attributesToString()); it is not used for any other purpose
     * (e.g. storage) by this method.
     *
     * @param mixed $element The form element to retrieve the submitted value for.
     *
     * @return string|array|int|float|null The (possibly sanitized) value, or null if not submitted.
     *
     * @throws WireException
     */
    public function sanitizePostValue($element): string|array|int|null|float
    {
        $fieldname = $element->getAttribute('name');

        if (!array_key_exists($fieldname, $this->input->getArray())) {
            return null;
        }

        $value = $this->input->get($fieldname);

        if (in_array($element->className(), Tag::MULTIVALCLASSES)) {
            // A multi-value field (e.g. checkboxes) with nothing selected
            // may come back as null instead of an empty array.
            if (!is_array($value)) {
                $value = [];
            }

            array_walk($value, function (&$v) {
                $v = $this->wire('sanitizer')->string($v);
            });
        }

        // Write the value back even if it is falsy ("0", empty array),
        // since those are still legitimate submitted values.
        if ($value !== null) {
            $element->setAttribute('value', $value);
        }

        return $value;
    }
}