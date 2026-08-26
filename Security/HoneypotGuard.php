<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Guard that provides the honeypot spam-protection field: a hidden input
 * that is invisible to real users but tends to get filled in automatically
 * by simple bots, revealing them.
 */
class HoneypotGuard extends BaseGuard
{
    /**
     * Name/CSS class used for the honeypot field.
     */
    public const FIELD_NAME = 'seca';

    /**
     * Create the honeypot input field.
     *
     * @param string $elementName     Fully qualified element name (already
     *                                built via Form::createElementName()).
     * @param bool   $useInputWrapper Whether the input wrapper should be used.
     * @param bool   $useFieldWrapper Whether the field wrapper should be used.
     *
     * @return InputText The honeypot field, ready to be inserted into the form.
     */
    public function createField(string $elementName, bool $useInputWrapper, bool $useFieldWrapper): InputText
    {
        $honeypot = new InputText(self::FIELD_NAME);
        $honeypot->setAttribute('name', $elementName);
        $honeypot->setLabel($this->getMessage())->setAttribute('class', self::FIELD_NAME);
        // Remove or add wrappers depending on settings
        $honeypot->useInputWrapper($useInputWrapper);
        $honeypot->useFieldWrapper($useFieldWrapper);
        $honeypot->getFieldWrapper()->setAttribute('class', self::FIELD_NAME);
        $honeypot->getInputWrapper()->setAttribute('class', self::FIELD_NAME);
        $honeypot->setAttributes(['class' => self::FIELD_NAME, 'tabindex' => '-1']);

        return $honeypot;
    }

    /**
     * Insert the honeypot field into the list of form elements, either at
     * a fixed position (rotation disabled) or at a random visible-field
     * position (rotation enabled, the default).
     *
     * @param array     $formElements  Reference to the form's element list.
     * @param array     $inputfieldKeys Keys of the visible input fields in $formElements.
     * @param InputText $honeypot      The honeypot field to insert.
     * @param bool      $stopRotation  If true, always insert at the first position.
     *
     * @return void
     */
    public function insertIntoElements(array &$formElements, array $inputfieldKeys, InputText $honeypot, bool $stopRotation): void
    {
        if ($stopRotation) {
            // add honeypot field on the first position of the form
            array_unshift($formElements, $honeypot);
        } else {
            // add honeypot on the random number field position
            shuffle($inputfieldKeys);
            array_splice($formElements, $inputfieldKeys[0], 0, [$honeypot]);
        }
    }

    /**
     * Get the (translated) message shown as the field label and used as
     * the validation error message.
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->_('Please do not fill out this field');
    }
}