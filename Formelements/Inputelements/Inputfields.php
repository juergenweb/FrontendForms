<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating HTML input elements for collecting user inputs.
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Inputfields.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Valitron\Validator;

use function ProcessWire\wireBytesStr;

abstract class Inputfields extends Element
{
    protected Label        $label;
    protected Notes        $notes;
    protected Description  $description;
    protected Errormessage $errormessage;
    protected Successmessage $successmessage;
    protected Wrapper      $customWrapper;
    protected FieldWrapper $fieldWrapper;
    protected InputWrapper $inputWrapper;
    protected Validator    $validator;
    protected ValitronAPI  $api;
    protected bool         $useCustomWrapper = false;
    protected bool|null    $useInputWrapper  = null;
    protected bool|null    $useFieldWrapper  = null;
    protected bool         $useAriaAttr      = true;
    protected array        $sanitizer        = [];
    protected array        $validatonRules   = [];
    protected array        $defaultValue     = [];
    protected array        $notes_array      = [];
    protected string       $markupType       = '';
    protected string       $form_id_submitted = '';

    public const patternInputs = ['text', 'password', 'email', 'search', 'url'];

    /**
     * @throws WireException
     * @throws WirePermissionException
     * @throws Exception
     */
    public function __construct(string $name)
    {

        parent::__construct($name);
        $this->setAttribute('name', $name);
        $this->customWrapper   = new Wrapper();
        $this->fieldWrapper    = new FieldWrapper();
        $this->inputWrapper    = new InputWrapper();
        $this->label           = new Label();
        $this->errormessage    = new Errormessage();
        $this->successmessage  = new Successmessage();
        $this->notes           = new Notes();
        $this->description     = new Description();
        $this->markupType      = $this->frontendforms['input_framework'];

        if (!in_array($this->className(), Tag::MULTIVALCLASSES)) {
            $this->setSanitizer('text');
        }

        $this->form_id_submitted = $this->getFormIDFromRequest($_REQUEST);

    }

    /**
     * Whether the HTML5 "pattern" attribute is valid for this field's
     * current input type (only a fixed subset of input types support it).
     * @return bool
     */
    public function patternAttributeAllowed(): bool
    {
        return in_array($this->getAttribute('type'), ['email', 'password', 'search', 'tel', 'text', 'url']);
    }

    /**
     * Enable (or disable) wrapping this field in an additional custom
     * wrapper element, and return that wrapper for further configuration.
     * @param bool $use
     * @return Wrapper
     */
    public function useCustomWrapper(bool $use = true): Wrapper
    {
        $this->useCustomWrapper = $use;
        return $this->customWrapper;
    }

    /**
     * Get the custom wrapper object for this field.
     * @return Wrapper
     */
    public function getCustomWrapper(): Wrapper
    {
        return $this->customWrapper;
    }

    /**
     * Find the submitted form id inside a request array by looking for a
     * key ending in "-form_id".
     * @param array $arr Typically $_REQUEST
     * @return string The submitted form id, or an empty string if none found
     */
    private function getFormIDFromRequest(array $arr): string
    {
        foreach ($arr as $key => $val) {
            if (str_ends_with($key, '-form_id')) {
                return $val;
            }
        }
        return '';
    }

    /**
     * True once useInputWrapper() has been called directly on this field
     * (as opposed to only ever having the backend config default from
     * Tag::__construct(), or having been set via the internal
     * setInputWrapperFromForm() propagation path used by Form::add() and
     * Form::useInputWrapper()). Used by Form to decide whether a field's
     * own setting should be respected instead of the form-level default.
     */
    protected bool $inputWrapperExplicitlySet = false;

    /**
     * Same tracking as $inputWrapperExplicitlySet, but for the field
     * wrapper.
     */
    protected bool $fieldWrapperExplicitlySet = false;

    /**
     * Explicitly enable or disable the input wrapper for this specific
     * field, overriding the form-level default.
     * @param bool $useInputWrapper
     * @return void
     */
    public function useInputWrapper(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;
        $this->inputWrapperExplicitlySet = true;
    }

    /**
     * Set the input-wrapper usage from the Form's propagation logic
     * (add() / Form::useInputWrapper()), without marking it as an
     * explicit per-field setting. Not intended to be called directly -
     * use useInputWrapper() instead.
     * @internal
     */
    public function setInputWrapperFromForm(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;
    }

    /**
     * Whether useInputWrapper() has been called directly on this field.
     * @return bool
     */
    public function isInputWrapperExplicitlySet(): bool
    {
        return $this->inputWrapperExplicitlySet;
    }

    /**
     * Get the current input-wrapper usage setting for this field.
     * @return bool|null True/false if set, null if never set at all
     */
    public function getUsageOfInputWrapper(): bool|null
    {
        return $this->useInputWrapper;
    }

    /**
     * Whether the "show asterisk on required fields" option is enabled in the
     * module configuration.
     * @return bool
     */
    public function getShowAsteriskConfig(): bool
    {
        return (bool)$this->frontendforms['input_showasterisk'];
    }

    /**
     * Explicitly enable or disable the field wrapper for this specific
     * field, overriding the form-level default.
     * @param bool $useFieldWrapper
     * @return void
     */
    public function useFieldWrapper(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;
        $this->fieldWrapperExplicitlySet = true;
    }

    /**
     * Set the field-wrapper usage from the Form's propagation logic
     * (add() / Form::useFieldWrapper()), without marking it as an
     * explicit per-field setting. Not intended to be called directly -
     * use useFieldWrapper() instead.
     * @internal
     */
    public function setFieldWrapperFromForm(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;
    }

    /**
     * Whether useFieldWrapper() has been called directly on this field.
     * @return bool
     */
    public function isFieldWrapperExplicitlySet(): bool
    {
        return $this->fieldWrapperExplicitlySet;
    }

    /**
     * Get the current field-wrapper usage setting for this field.
     * @return bool|null True/false if set, null if never set at all
     */
    public function getUsageOfFieldWrapper(): bool|null
    {
        return $this->useFieldWrapper;
    }

    /**
     * Get the input wrapper object for this field.
     * @return InputWrapper
     */
    public function getInputWrapper(): InputWrapper
    {
        return $this->inputWrapper;
    }

    /**
     * Get the field wrapper object for this field.
     * @return FieldWrapper
     */
    public function getFieldWrapper(): FieldWrapper
    {
        return $this->fieldWrapper;
    }

    /**
     * Remove one or more previously added sanitizers by name, or all of
     * them if no name is given.
     * @param array|string|null $sanitizer Sanitizer name(s) to remove, or null to remove all
     * @return void
     */
    public function removeSanitizers(array|string|null $sanitizer = null): void
    {
        if ($sanitizer === null) {
            $this->sanitizer = [];
            return;
        }

        foreach ((array) $sanitizer as $item) {
            $key = array_search($item, $this->sanitizer);
            if ($key !== false) {
                unset($this->sanitizer[$key]);
            }
        }
    }

    /**
     * Convert a human-readable size string (e.g. "10MB", or "2M" for a
     * php.ini-style shorthand value) into a plain byte count.
     * @param string|int $from
     * @param bool $ini Whether $from uses php.ini shorthand units (K/M/G) instead of KB/MB/GB
     * @return int|null The size in bytes, or null if the unit could not be recognized
     */
    public static function convertToBytes(string|int $from, bool $ini = false): ?int
    {
        $units = $ini ? ['B', 'K', 'M', 'G', 'T', 'P'] : ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        if (is_int($from)) {
            $from = (string) $from;
        }

        $pos    = $ini ? -1 : -2;
        $number = substr($from, 0, $pos);
        $suffix = strtoupper(substr($from, $pos));

        if (is_numeric(substr($suffix, 0, 1))) {
            return (int) preg_replace('/[^\d]/', '', $from);
        }

        $exponent = array_flip($units)[$suffix] ?? null;
        return $exponent !== null ? (int) $number * (1024 ** $exponent) : null;
    }

    /**
     * Recursively remove every occurrence of a given key from an array,
     * including inside any nested sub-arrays.
     * @param array $array Array to modify in place
     * @param string $keyToRemove
     * @return void
     */
    private function removeKeyRecursive(array &$array, string $keyToRemove): void
    {
        foreach ($array as $key => &$value) {
            if ($key === $keyToRemove) {
                unset($array[$key]);
                continue;
            }
            if (is_array($value)) {
                self::removeKeyRecursive($value, $keyToRemove);
            }
        }
        unset($value);
    }

    /**
     * Recursively apply array_filter() to an array, also filtering out
     * empty/falsy values inside any nested sub-arrays.
     * @param array $input
     * @return array
     */
    private function array_filter_recursive(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::array_filter_recursive($value);
            }
        }
        return array_filter($input);
    }

    /**
     * Add a validation rule to this field, along with any additional
     * arguments the rule needs (e.g. min/max values, a comparison field).
     * Also applies the matching HTML5 client-side validation attribute
     * automatically, if a matching addHTML5{validator}() method exists.
     * @param string $validator Name of the validation rule
     * @return $this
     */
    public function setRule(string $validator): self
    {

        $args      = func_get_args();
        $variables = array_slice($args, 1);

        $priorCount = count($variables);
        $this->removeKeyRecursive($variables, 'defaultnotes');
        $variables = $this->array_filter_recursive($variables);

        if (count($variables) !== $priorCount) {
            return $this;
        }

        // Prefix field name for equals/different Validation
        if (in_array($validator, ['equals', 'different'], strict: true) && isset($variables[0])) {
            if (!str_starts_with($variables[0], $this->form_id_submitted . '-')) {
                $variables[0] = $this->form_id_submitted . '-' . $variables[0];
            }
        }

        if ($validator === 'allowedFileSize' && isset($variables[0]) && is_int($variables[0])) {
            $variables[0] = wireBytesStr($variables[0]);
        }

        $this->api = new ValitronAPI();
        $this->api->setValidator($validator);
        $result = $this->api->setRule($validator, $variables);
        $this->validatonRules[$result['name']] = ['options' => $variables];

        $this->applyValidatorNote($validator, $variables);

        $method = 'addHTML5' . $validator;
        if (method_exists($this, $method)) {
            $this->$method($variables);
        }

        return $this;
    }

    /**
     * Build and store the translatable hint/note text shown to the user
     * for validation rules that need one (e.g. file size or count
     * limits), based on the rule's own arguments.
     * @param string $validator
     * @param array $variables
     * @return void
     */
    private function applyValidatorNote(string $validator, array $variables): void
    {
        $notes = &$this->notes_array;

        switch ($validator) {
            case 'minFilesInZIPFolder':
                BaseHelper::getPositiveInt($variables, 'minFilesInZIPFolder');
                $notes[$validator] = ['text' => sprintf($this->_('ZIP folder(s) must contain at least %s files'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxFilesInZIPFolder':
                BaseHelper::getPositiveInt($variables, 'maxFilesInZIPFolder');
                $notes[$validator] = ['text' => sprintf($this->_('ZIP folders may not contain more than %s files'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxTotalFileSizeZipUncompressed':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files must not exceed a total size of %s when extracted'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'requiredFileNamesInZip':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files must contain the following files: %s'), implode(', ', $variables[0])), 'value' => $variables[0]];
                break;
            case 'maxNumberOfZipFolders':
                // do not allow other values than a positive integer for the param
                BaseHelper::getPositiveInt($variables, 'maxNumberOfZipFolders');
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload more than %s ZIP file(s)'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxDepthOfZipFolders':
                // do not allow other values than a positive integer for the param
                BaseHelper::getPositiveInt($variables, 'maxDepthOfZipFolders');
                $notes[$validator] = ['text' => sprintf($this->_('The maximum allowed folder/directory depth in a ZIP file is %s'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'allowedFileTypesInZipFolder':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files may only contain the following file types: %s'), implode(', ', $variables[0])), 'value' => $variables[0]];
                break;
            case 'maxAllowedFileSizeOfFileInZipFolder':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files may only contain files which are not larger than %s'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'notAllowedFileTypesInZipFolder':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files may not contain files of the following file types: %s'), implode(', ', $variables[0])), 'value' => $variables[0]];
                break;
            case 'allowedFileSize':
            case 'maxSingleFileSize':
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload files larger than %s'), wireBytesStr($variables[0])), 'value' => $variables[0]];
                break;
            case 'allowedTotalFileSize':
            case 'maxTotalFileSize':
                if (array_key_exists(0, $variables) && !is_null($variables[0])) {
                    $notes[$validator] = ['text' => sprintf($this->_('The total size of all uploaded files must not exceed %s.'), wireBytesStr($variables[0])), 'value' => $variables[0]];
                } else {
                    throw new \InvalidArgumentException(sprintf('Param for total file size of validation rule allowedTotalFileSize at inputfield %s is missing.', $this->getAttribute('name')));
                }
                break;
            case 'allowedFileNumber':
            case 'maxFileNumber':
                BaseHelper::getPositiveInt($variables, 'allowedFileNumber');
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload more than %s files.'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'minFileNumber':
                BaseHelper::getPositiveInt($variables, 'minFileNumber');
                $notes[$validator] = ['text' => sprintf($this->_('Please upload at least %s files.'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'allowedFileExt':
                if (isset($variables[0])) {
                    $notes[$validator] = ['text' => sprintf($this->_('Allowed file types: %s'), implode(', ', $variables[0])), 'value' => implode(', ', $variables[0])];
                }
                break;
            case 'compressedContentAllowedFileExt':
                if (isset($variables[0])) {
                    $notes[$validator] = ['text' => sprintf($this->_('Allowed file types inside compressed folder(s): %s'), implode(', ', $variables[0])), 'value' => implode(', ', $variables[0])];
                }
                break;
            case 'phpIniFilesize':
                $maxFileSize = self::convertToBytes(ini_get('upload_max_filesize'), true);
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload files larger than %s'), wireBytesStr($maxFileSize)), 'value' => $maxFileSize];
                break;
            case 'forbiddenFileExt':
                if (isset($variables[0])) {
                    $mimeTypes = implode(', ', $variables[0]);
                    $notes[$validator] = ['text' => sprintf($this->_('Files with the following extensions are forbidden: %s'), $mimeTypes), 'value' => $variables[0]];
                }
                break;
            case 'minImageDimensions':
                if (isset($variables[0])) {
                    $dimensions = is_array($variables[0]) ? implode(', ', $variables[0]) : $variables[0];
                    $notes[$validator] = ['text' => sprintf($this->_('Please upload only images with dimensions equal to or larger than %s px.'), $dimensions), 'value' => $variables[0]];
                }
                break;
            case 'aspectRatio':
                if (isset($variables[0])) {
                    $ratios = is_array($variables[0]) ? implode(', ', $variables[0]) : $variables[0];
                    $notes[$validator] = ['text' => sprintf($this->_('Uploaded images must be in the format %s.'), $ratios), 'value' => $variables[0]];
                }
                break;

        }
    }

    /**
     * Remove a previously added validation rule from this field, along
     * with its matching HTML5 attribute, if any.
     * @param string $rule Name of the validation rule to remove
     * @return $this
     */
    public function removeRule(string $rule): self
    {
        unset($this->validatonRules[$rule], $this->notes_array[$rule]);

        $method = 'removeHTML5' . $rule;
        if (method_exists($this, $method)) {
            $this->$method();
        }

        return $this;
    }

    /**
     * Set a custom error message for the validation rule that was added
     * most recently via setRule().
     * @param string $msg
     * @return $this
     */
    public function setCustomMessage(string $msg): self
    {
        $this->validatonRules[$this->api->getValidator()] = array_merge(
            $this->validatonRules[$this->api->getValidator()],
            ['customMsg' => $msg]
        );
        return $this;
    }

    /**
     * Set a custom field name to use inside the error message of the
     * validation rule that was added most recently via setRule().
     * @param string $fieldname
     * @return $this
     */
    public function setCustomFieldname(string $fieldname): self
    {
        $this->validatonRules[$this->api->getValidator()] = array_merge(
            $this->validatonRules[$this->api->getValidator()],
            ['customFieldName' => $fieldname]
        );
        return $this;
    }

    /**
     * Render this field and return it as a string.
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Render the complete field markup: applies aria attributes,
     * required-state on the label, combines notes text, renders the
     * input element itself via the active markup framework, and wraps
     * the result in the field/custom wrapper(s) if enabled.
     * @return string
     */
    public function ___render(): string
    {
        if (!in_array($this->getAttribute('type'), self::patternInputs)) {
            $this->removeAttribute('pattern');
        }

        $this->addAriaAttributes();

        if ($this->hasRule('required')) {
            $this->label->setRequired();
        }

        if ($this->notes->getContent() && $this->notes_array) {
            $this->notes_array = ['notes' => ['text' => $this->notes->getContent()]] + $this->notes_array;
        }

        if ($this->notes_array) {
            $wrappedTexts = [];
            foreach ($this->notes_array as $key => $array) {
                $wrappedTexts[$key] = '<span id="' . $this->getID() . '-' . $key . '">' . $array['text'] . '</span>';
            }
            $this->setNotes(implode('<br>', $wrappedTexts));
        }

        $className  = $this->className();
        $input      = $this->{'render' . $className}();

        if ($this->getErrormessage()->getText()) {
            $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getErrorClass());
        }

        if ($this->getPostValue() && $this->getSuccessmessage()->getText() && !$this->getErrormessage()->getText()) {
            $this->fieldWrapper->setAttribute('class', $this->fieldWrapper->getSuccessClass());
        } else {
            $this->setSuccessMessage('');
        }

        $methodName = 'render' . ucfirst(pathinfo($this->markupType, PATHINFO_FILENAME));
        $content    = method_exists($this, '___' . $methodName)
            ? $this->$methodName($className, $input)
            : $this->renderDefault($className, $input);

        $out = '';

        if (!$this->useFieldWrapper) {
            $out .= $content;
        } else {
            $this->fieldWrapper->setContent($content);
            $out .= $this->fieldWrapper->render() . PHP_EOL;
        }

        if ($this->useCustomWrapper) {
            $this->customWrapper->setContent($out);
            $out = $this->customWrapper->render() . PHP_EOL;
        }

        return $out;
    }

    /**
     * Render this field using the default (framework-less) markup.
     * @param string $className
     * @param string $input The already-rendered <input>/<select>/... tag
     * @return string
     */
    public function ___renderDefault(string $className, string $input): string
    {
        return (new DefaultInputRenderer())->render($this, $className, $input);
    }

    /**
     * Render this field using Uikit3 markup - currently identical to the
     * default renderer.
     * @param string $className
     * @param string $input The already-rendered <input>/<select>/... tag
     * @return string
     */
    public function ___renderUikit3(string $className, string $input): string
    {
        return $this->renderDefault($className, $input);
    }

    /**
     * Render this field using Bootstrap5 markup.
     * @param string $className
     * @param string $input The already-rendered <input>/<select>/... tag
     * @return string
     */
    public function ___renderBootstrap5(string $className, string $input): string
    {
        return (new Bootstrap5InputRenderer())->render($this, $className, $input);
    }

    /**
     * Render this field using Pico2 markup.
     * @param string $className
     * @param string $input The already-rendered <input>/<select>/... tag
     * @return string
     */
    public function ___renderPico2(string $className, string $input): string
    {
        return (new Pico2InputRenderer())->render($this, $className, $input);
    }

    /**
     * Whether a validation rule with the given name has been added to
     * this field.
     * @param string $ruleName
     * @return bool
     */
    public function hasRule(string $ruleName): bool
    {
        return array_key_exists(trim($ruleName), $this->getRules());
    }

    /**
     * Get all validation rules currently added to this field.
     * @return array
     */
    public function getRules(): array
    {
        return $this->validatonRules;
    }

    /**
     * Remove every validation rule from this field.
     * @return void
     */
    public function removeAllRules(): void
    {
        $this->validatonRules = [];
    }

    /**
     * Get the label object for this field.
     * @return Label
     */
    public function getLabel(): Label
    {
        return $this->label;
    }

    /**
     * Set the label text for this field.
     * @param string $label
     * @return Label
     */
    public function setLabel(string $label): Label
    {
        $this->label->setText($label);
        return $this->label;
    }

    /**
     * Get the error message object for this field.
     * @return Errormessage
     */
    public function getErrorMessage(): Errormessage
    {
        return $this->errormessage;
    }

    /**
     * Set the error message text for this field.
     * @param string $errorMessage
     * @return Errormessage
     */
    protected function setErrorMessage(string $errorMessage): Errormessage
    {
        $this->errormessage->setText($errorMessage);
        return $this->errormessage;
    }

    /**
     * Get the success message object for this field.
     * @return Successmessage
     */
    public function getSuccessMessage(): Successmessage
    {
        return $this->successmessage;
    }

    /**
     * Set the success message text for this field.
     * @param string $successMessage
     * @return Successmessage
     */
    protected function setSuccessMessage(string $successMessage): Successmessage
    {
        $this->successmessage->setText($successMessage);
        return $this->successmessage;
    }

    /**
     * Get the description object for this field.
     * @return Description
     */
    public function getDescription(): Description
    {
        return $this->description;
    }

    /**
     * Set the description text for this field.
     * @param string $description
     * @return Description
     */
    public function setDescription(string $description): Description
    {
        $this->description->setText($description);
        return $this->description;
    }

    /**
     * Get the notes object for this field.
     * @return Notes
     */
    public function getNotes(): Notes
    {
        return $this->notes;
    }

    /**
     * Set the notes text for this field.
     * @param string $notes
     * @return Notes
     */
    public function setNotes(string $notes): Notes
    {
        $this->notes->setText($notes);
        return $this->notes;
    }

    /**
     * Get the raw, per-validator notes entries collected for this field
     * (e.g. file size/count hints added automatically by
     * applyValidatorNote()).
     * @return array
     */
    public function getNotesArray(): array
    {
        return $this->notes_array;
    }

    /**
     * Remove a single entry from the notes array by its key.
     * @param int|string $key
     * @return Notes
     */
    public function removeNotesByKey(int|string $key): Notes
    {
        unset($this->notes_array[$key]);
        return $this->notes;
    }

    /**
     * Get the default value(s) previously set via setDefaultValue().
     * @return string|array|null
     */
    protected function getDefaultValue(): string|array|null
    {
        return $this->defaultValue;
    }

    /**
     * Set the default value for this field, unless the form has already
     * been submitted (in which case the submitted value takes
     * precedence) or no value is given.
     * @param int|string|array|null $default
     * @return $this
     */
    public function setDefaultValue(int|string|array|null $default = null): self
    {
        if ($this->isSubmitted() || $default === null) {
            return $this;
        }

        if (is_int($default)) {
            $default = (string) $default;
        }

        if (is_string($default)) {
            $default = func_get_args();
            array_walk($default, fn (&$item) => $item = trim($item));
        }

        $isMulti = in_array($this->className(), ['InputCheckboxMultiple', 'InputSelectMultiple']);
        $value   = $isMulti ? $default : $default[0];

        $this->setAttribute('value', $value);
        $this->defaultValue = $default;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function setSanitizer(string $sanitizer): void
    {
        $sanitizer = trim(strtolower($sanitizer));
        if (method_exists($this->wire('sanitizer'), $sanitizer)) {
            $this->sanitizer[] = $sanitizer;
        } else {
            throw new Exception('This sanitizer method does not exist in ProcessWire');
        }
    }

    /**
     * Get all sanitizer names currently added to this field.
     * @return array
     */
    public function getSanitizers(): array
    {
        return $this->sanitizer;
    }

    /**
     * Whether a sanitizer with the given name has been added to this
     * field.
     * @param string $sanitizer
     * @return bool
     */
    public function hasSanitizer(string $sanitizer): bool
    {
        return in_array(trim(strtolower($sanitizer)), $this->sanitizer);
    }

    /**
     * Get this field's submitted value from the request, if present.
     * @return mixed
     */
    protected function getPostValue(): mixed
    {
        if ($this->hasPostValue()) {
            $name = str_replace('[]', '', $this->getAttribute('name'));
            return $this->getServerMethod()[$name];
        }
        return [];
    }

    /**
     * Whether this field has a submitted value in the request.
     * @return bool
     */
    protected function hasPostValue(): bool
    {
        $name = str_replace('[]', '', $this->getAttribute('name'));
        return isset($this->getServerMethod()[$name]);
    }

    /**
     * Add aria-describedby (pointing to the description and/or notes text,
     * if present) and other accessibility-related attributes to the
     * input element, unless disabled via useAriaAttributes(false).
     *
     * If both a description and notes text are present, both their ids
     * are combined into a single, space-separated aria-describedby value
     * (standard ARIA convention for referencing multiple elements) -
     * setting the attribute twice would make the second call silently
     * overwrite the first, leaving only one of the two referenced.
     *
     * Skipped entirely for hidden inputs (type="hidden"), since
     * aria-describedby has no meaning on an element that is never
     * exposed to assistive technology in the first place.
     * @return void
     */
    protected function addAriaAttributes(): void
    {
        if (!$this->useAriaAttr || $this->className() === 'InputHidden') {
            return;
        }

        $describedBy = [];
        if ($this->getDescription()->getText()) {
            $describedBy[] = $this->getID() . '-desc';
        }
        if ($this->getNotes()->getText()) {
            $describedBy[] = $this->getID() . '-notes';
        }
        if ($describedBy) {
            $this->setAttribute('aria-describedby', implode(' ', $describedBy));
        }
    }

    /**
     * Enable or disable automatic ARIA attributes (e.g. aria-describedby)
     * for this field.
     * @param bool $ariaAttr
     * @return $this
     */
    public function useAriaAttributes(bool $ariaAttr): self
    {
        $this->useAriaAttr = $ariaAttr;
        return $this;
    }

    // HTML5 attribute methods — add/remove pattern, min, max, etc.
    //
    // Each addHTML5{validator}() / removeHTML5{validator}() pair below is
    // invoked automatically, by naming convention, from setRule()/
    // removeRule() whenever a validation rule of the matching name is
    // added to or removed from this field - see the method_exists() +
    // dynamic method call in both of those methods. They apply/remove
    // the client-side HTML5 attribute (pattern, min, max, ...) that
    // mirrors the equivalent server-side validation rule, so invalid
    // input is caught by the browser before the form is even submitted.
    // Individual docblocks below only note which attribute is affected
    // and, where not obvious, why.

    /**
     * Add the HTML5 "required" attribute.
     * @return void
     */
    protected function addHTML5required(): void
    {
        $this->setAttribute('required');
    }
    /**
     * Remove the HTML5 "required" attribute.
     * @return void
     */
    protected function removeHTML5required(): void
    {
        $this->removeAttribute('required');
    }
    /**
     * Add the HTML5 "min" attribute.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5min(array $v): void
    {
        $this->setAttribute('min', $v[0]);
    }
    /**
     * Remove the HTML5 "min" attribute.
     * @return void
     */
    protected function removeHTML5min(): void
    {
        $this->removeAttribute('min');
    }
    /**
     * Add the HTML5 "max" attribute.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5max(array $v): void
    {
        $this->setAttribute('max', $v[0]);
    }
    /**
     * Remove the HTML5 "max" attribute.
     * @return void
     */
    protected function removeHTML5max(): void
    {
        $this->removeAttribute('max');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5alpha().
     * @return void
     */
    protected function removeHTML5alpha(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5checkBic().
     * @return void
     */
    protected function removeHTML5checkBic(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5NoNumbers().
     * @return void
     */
    protected function removeHTML5NoNumbers(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5checkIban().
     * @return void
     */
    protected function removeHTML5checkIban(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5alphaNum().
     * @return void
     */
    protected function removeHTML5alphaNum(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5slug().
     * @return void
     */
    protected function removeHTML5slug(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5ascii().
     * @return void
     */
    protected function removeHTML5ascii(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5regex().
     * @return void
     */
    protected function removeHTML5regex(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5exactValue().
     * @return void
     */
    protected function removeHTML5exactValue(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5differentValue().
     * @return void
     */
    protected function removeHTML5differentValue(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5integer().
     * @return void
     */
    protected function removeHTML5integer(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5numeric().
     * @return void
     */
    protected function removeHTML5numeric(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5noLetters().
     * @return void
     */
    protected function removeHTML5noLetters(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5firstAndLastname().
     * @return void
     */
    protected function removeHTML5firstAndLastname(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5contains().
     * @return void
     */
    protected function removeHTML5contains(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5time().
     * @return void
     */
    protected function removeHTML5time(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by the alphaNum2 validator.
     * @return void
     */
    protected function removeHTML5alphaNum2(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5usernameSyntax().
     * @return void
     */
    protected function removeHTML5usernameSyntax(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5ip().
     * @return void
     */
    protected function removeHTML5ip(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5ipv4().
     * @return void
     */
    protected function removeHTML5ipv4(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5ipv6().
     * @return void
     */
    protected function removeHTML5ipv6(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "accept" attribute added by addHTML5allowedFileExt().
     * @return void
     */
    protected function removeHTML5allowedFileExt(): void
    {
        $this->removeAttribute('accept');
    }
    /**
     * Remove the HTML5 "max" attribute added by addHTML5dateBefore().
     * @return void
     */
    protected function removeHTML5dateBefore(): void
    {
        $this->removeAttribute('max');
    }
    /**
     * Remove the HTML5 "min" attribute added by addHTML5dateAfter().
     * @return void
     */
    protected function removeHTML5dateAfter(): void
    {
        $this->removeAttribute('min');
    }
    /**
     * Remove the HTML5 "max" attribute added by addHTML5dateBeforeField().
     * @return void
     */
    protected function removeHTML5dateBeforeField(): void
    {
        $this->removeAttribute('max');
    }
    /**
     * Remove the HTML5 "min" attribute added by addHTML5dateAfterField().
     * @return void
     */
    protected function removeHTML5dateAfterField(): void
    {
        $this->removeAttribute('min');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5meetsPasswordConditions().
     * @return void
     */
    protected function removeHTML5meetsPasswordConditions(): void
    {
        $this->removeAttribute('pattern');
    }
    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5dateFormat().
     * @return void
     */
    protected function removeHTML5dateFormat(): void
    {
        $this->removeAttribute('pattern');
    }

    /**
     * Add the HTML5 "minlength" attribute.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5lengthMin(array $v): void
    {
        $this->setAttribute('minlength', (string) $v[0]);
    }

    /**
     * Remove the HTML5 "minlength" attribute.
     * @return void
     */
    protected function removeHTML5lengthMin(): void
    {
        $this->removeAttribute('minlength');
    }

    /**
     * Add the HTML5 "maxlength" attribute.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5lengthMax(array $v): void
    {
        $this->setAttribute('maxlength', $v[0]);
    }

    /**
     * Remove the HTML5 "maxlength" attribute.
     * @return void
     */
    protected function removeHTML5lengthMax(): void
    {
        $this->removeAttribute('maxlength');
    }

    /**
     * Add both the HTML5 "minlength" and "maxlength" attributes.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5lengthBetween(array $v): void
    {
        $this->setAttribute('minlength', $v[0]);
        $this->setAttribute('maxlength', $v[1]);
    }

    /**
     * Remove the HTML5 "minlength" and "maxlength" attributes added by addHTML5lengthBetween().
     * @return void
     */
    protected function removeHTML5lengthBetween(): void
    {
        $this->removeAttribute('minlength');
        $this->removeAttribute('maxlength');
    }

    /**
     * Add a HTML5 "pattern" attribute allowing letters only.
     * @return void
     */
    protected function addHTML5alpha(): void
    {
        $this->setAttribute('pattern', '[a-zA-Z]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid BIC/SWIFT code.
     * @return void
     */
    protected function addHTML5checkBic(): void
    {
        $this->setAttribute('pattern', '[A-Z0-9]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?');
    }

    /**
     * Add a HTML5 "pattern" attribute disallowing any digit characters.
     * @return void
     */
    protected function addHTML5NoNumbers(): void
    {
        $this->setAttribute('pattern', '[^0-9]+');
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid IBAN, plus a data-checkiban flag used by the frontend JS to run the full checksum validation.
     * @return void
     */
    protected function addHTML5checkIban(): void
    {
        $this->setAttribute('pattern', '[A-Z]{2}\d{13,32}|(?=.{18,42}$)[A-Z]{2}\d{2}( )(\d{4}\1){2,7}\d{1,4}');
        $this->setAttribute('data-checkiban', 'true');
    }

    /**
     * Add a HTML5 "pattern" attribute allowing letters and numbers only.
     * @return void
     */
    protected function addHTML5alphaNum(): void
    {
        $this->setAttribute('pattern', '[a-zA-Z0-9]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters and numbers'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing the characters typically found in first/last names (including accented letters), hyphens, apostrophes and spaces.
     * @return void
     */
    protected function addHTML5firstAndLastname(): void
    {
        $this->setAttribute('pattern', '[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð,-.\'  ]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain allowed characters for names.'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing ASCII characters only.
     * @return void
     */
    protected function addHTML5ascii(): void
    {
        $this->setAttribute('pattern', '[\x00-\x7F]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain ascii characters'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing lowercase letters, numbers, underscores and hyphens only.
     * @return void
     */
    protected function addHTML5slug(): void
    {
        $this->setAttribute('pattern', '[a-z0-9_\-]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters, numbers, underscores or hyphens'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute matching a URL starting with http:// or https://. Skipped for the dedicated InputUrl field type, which already uses the browser's own url input type.
     * @return void
     */
    protected function addHTML5url(): void
    {
        if ($this->className() !== 'InputUrl' || is_subclass_of($this, 'InputUrl')) {
            $this->setAttribute('pattern', 'https?://.{1,63}\.[A-z]{2,13}');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid URL starting with http:// or https://'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5url().
     * @return void
     */
    protected function removeHTML5url(): void
    {
        if ($this->className() !== 'InputUrl' || is_subclass_of($this, 'InputUrl')) {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid email address. Skipped for the dedicated InputEmail field type, which already uses the browser's own email input type.
     * @return void
     */
    protected function addHTML5email(): void
    {
        if ($this->className() !== 'InputEmail' || is_subclass_of($this, 'InputEmail')) {
            $this->setAttribute('pattern', '^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid email address'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5email().
     * @return void
     */
    protected function removeHTML5email(): void
    {
        if ($this->className() !== 'InputEmail' || is_subclass_of($this, 'InputEmail')) {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a HH:MM:SS time, unless the field is already a native type="time" input (which validates this natively).
     * @return void
     */
    protected function addHTML5time(): void
    {
        if ($this->getAttribute('type') !== 'time') {
            $this->setAttribute('pattern', '([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]');
        }
        $this->setAttribute('title', sprintf($this->_('%s is not a valid time. You have to enter the time in this format: HH:MM:SS (fe. 19:00:00)'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing integers or decimal numbers (with a dot, not a comma).
     * @return void
     */
    protected function addHTML5numeric(): void
    {
        $this->setAttribute('pattern', '(([0-9]*)|(([0-9]*)\.([0-9]*)))');
        $this->setAttribute('title', sprintf($this->_('%s should only contain numbers (integers or floats with a dot, not a comma)'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing digits only.
     * @return void
     */
    protected function addHTML5integer(): void
    {
        $this->setAttribute('pattern', '[0-9]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain integers'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid IPv4-style address.
     * @return void
     */
    protected function addHTML5ip(): void
    {
        $this->setAttribute('pattern', '(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*){3}');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IP address in the format x.x.x.x'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid IPv4 address.
     * @return void
     */
    protected function addHTML5ipv4(): void
    {
        $this->setAttribute('pattern', '((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IPv4 address in the format x.x.x.x'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute matching a valid IPv6 address.
     * @return void
     */
    protected function addHTML5ipv6(): void
    {
        $this->setAttribute('pattern', '((^|:)([0-9a-fA-F]{0,4})){1,8}$');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IPv6 address in the format x:x:x:x:x:x:x:x'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute allowing lowercase letters, numbers, underscores and hyphens, up to 128 characters.
     * @return void
     */
    protected function addHTML5usernameSyntax(): void
    {
        $this->setAttribute('pattern', '[a-z0-9_\-]{1,128}');
        $this->setAttribute('title', sprintf($this->_('%s contains not allowed characters or is longer than 128 characters. Allowed characters are: letters, numbers, underscores and dashes (no whitespaces)'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute disallowing any letter characters.
     * @return void
     */
    protected function addHTML5noLetters(): void
    {
        $label = $this->getLabel()->getText() ?: $this->_('This field');
        $this->setAttribute('pattern', '[^a-zA-ZäöüÖÄÜ]+');
        $this->setAttribute('title', sprintf($this->_('%s contains letters, but they are not allowed'), $label));
    }

    /**
     * Add a HTML5 "pattern" attribute requiring the field to contain a specific word, taken from this rule's own configured options.
     * @return void
     */
    protected function addHTML5contains(): void
    {
        $word = $this->getRules()['contains']['options'][0] ?? null;
        if ($word) {
            $this->setAttribute('pattern', '\b' . $word . '\b');
            $this->setAttribute('title', sprintf($this->_('%s must contain the word %s'), $this->getLabel()->getText(), $word));
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a YYYY-MM month value, unless the field is already a native type="month" input (which validates this natively).
     * @return void
     */
    protected function addHTML5month(): void
    {
        if ($this->getAttribute('type') !== 'month') {
            $this->setAttribute('pattern', '^\d{4}-(0[1-9]|1[012])$');
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid month in the format YYYY-MM'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5month(), unless the field is a native type="month" input.
     * @return void
     */
    protected function removeHTML5month(): void
    {
        if ($this->getAttribute('type') !== 'month') {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a HEX color code, unless the field is already a native type="color" input (which only ever holds valid HEX values).
     * @return void
     */
    protected function addHTML5checkHex(): void
    {
        if ($this->getAttribute('type') !== 'color') {
            $this->setAttribute('pattern', '#([a-fA-F0-9]{3}){1,2}\b');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid HEX code in the format #XXX or #XXXXXX'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5checkHex(), unless the field is a native type="color" input.
     * @return void
     */
    protected function removeHTML5checkHex(): void
    {
        if ($this->getAttribute('type') !== 'color') {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a YYYY-Www week value, unless the field is already a native type="week" input (which validates this natively).
     * @return void
     */
    protected function addHTML5week(): void
    {
        if ($this->getAttribute('type') !== 'week') {
            $this->setAttribute('pattern', '^\d{1,4}-[W](\d|[0-4]\d|5[0123])');
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid week in the format YYYY-Www'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5week(), unless the field is a native type="week" input.
     * @return void
     */
    protected function removeHTML5week(): void
    {
        if ($this->getAttribute('type') !== 'week') {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute matching a dd.MM.YYYY date, unless the field is already a native type="date" input (which validates this natively).
     * @return void
     */
    protected function addHTML5date(): void
    {
        if ($this->getAttribute('type') !== 'date') {
            $this->setAttribute('pattern', '^\d{2}.\d{2}.\d{4}');
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid date in the format dd.MM.YYYY'), $this->getLabel()->getText()));
        }
    }

    /**
     * Remove the HTML5 "pattern" attribute added by addHTML5date(), unless the field is a native type="date" input.
     * @return void
     */
    protected function removeHTML5date(): void
    {
        if ($this->getAttribute('type') !== 'date') {
            $this->removeAttribute('pattern');
        }
    }

    /**
     * Add a HTML5 "pattern" attribute from a raw PHP-style regex (e.g. "/foo$/i"), stripping the delimiters/flags that HTML5 patterns don't use.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5regex(array $v): void
    {
        $pattern = str_replace(['$', 'i', '/'], '', $v[0]);
        $this->setAttribute('pattern', $pattern);
        $this->setAttribute('title', sprintf($this->_('%s contains an invalid value'), $this->getLabel()->getText()));
    }

    /**
     * Add a HTML5 "pattern" attribute requiring the field's value to exactly match a given value.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5exactValue(array $v): void
    {
        $this->setAttribute('pattern', $v[0]);
        $this->setAttribute('title', sprintf($this->_('%s should contain the exact value %s'), $this->getLabel()->getText(), $v[0]));
    }

    /**
     * Add a HTML5 "pattern" attribute requiring the field's value to NOT contain a given value.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5differentValue(array $v): void
    {
        $this->setAttribute('pattern', '((?!' . $v[0] . ').)*');
        $this->setAttribute('title', sprintf($this->_('%s should not contain the value %s'), $this->getLabel()->getText(), $v[0]));
    }

    /**
     * Add a HTML5 "accept" attribute restricting a file input to the given file extensions.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5allowedFileExt(array $v): void
    {
        $extensions = $v ? array_map(fn ($e) => '.' . $e, $v[0]) : [];
        $this->setAttribute('accept', implode(',', $extensions));
    }

    /**
     * Add a HTML5 "max" attribute so the browser's native date picker only allows dates before the given value.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateBefore(array $v): void
    {
        $this->setAttribute('max', $v[0]);
    }
    /**
     * Add a HTML5 "min" attribute so the browser's native date picker only allows dates after the given value.
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateAfter(array $v): void
    {
        $this->setAttribute('min', $v[0]);
    }

    /**
     * Add a HTML5 "pattern" attribute matching one of a fixed set of supported date formats (dd.mm.yyyy, yyyy.mm.dd, mm/dd/yyyy).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateFormat(array $v): void
    {
        $format = strtolower($v[0]);
        $dateformats = [
            'dd.mm.yyyy' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}',
            'yyyy.mm.dd' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
            'mm/dd/yyyy' => '(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d\d',
        ];
        if (isset($dateformats[$format])) {
            $this->setAttribute('pattern', $dateformats[$format]);
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid date in the format %s'), $this->getLabel()->getText(), $format));
        }
    }

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function createPasswordRegex(): string|null
    {
        $passwordModule = $this->wire('modules')->get('InputfieldPassword');
        $passwordField  = $this->wire('fields')->get('pass');
        $requirements   = $passwordField->requirements ?: $passwordModule->requirements;
        if (in_array('none', $requirements)) {
            return null;
        }

        $lookAheads = [
            'letter' => '(?=.*[A-Za-z])',
            'lower'  => '(?=.*[a-z])',
            'upper'  => '(?=.*[A-Z])',
            'digit'  => '(?=.*\d)',
            'other'  => '(?=.*\W)',
        ];

        $parts  = array_filter($lookAheads, fn ($k) => in_array($k, $requirements), ARRAY_FILTER_USE_KEY);
        $length = (string) ($passwordField->minlength ?: $passwordModule->minlength);

        return implode('', $parts) . '.{' . $length . ',128}$';
    }

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    /**
     * Add a HTML5 "pattern" attribute built from the site's configured password requirements (see createPasswordRegex()).
     * @return void
     */
    protected function addHTML5meetsPasswordConditions(): void
    {
        $regex = $this->createPasswordRegex();
        if ($regex !== null) {
            $this->setAttribute('pattern', $regex);
        }
    }

    /**
     * Shared implementation for the dateBeforeField/dateAfterField HTML5
     * validators: points the field's data-ff_* attributes at the
     * comparison field, and pre-fills min/max from the submitted value
     * of that field (if the form was already submitted) so client-side
     * validation has something to compare against immediately.
     * @param array $value Validator arguments; $value[0] is the comparison field's name
     * @param bool $before True for "must be before", false for "must be after"
     * @return void
     */
    private function beforeAfter(array $value, bool $before): void
    {
        $fieldName = str_replace($this->getID() . '-', '', $value[0]);
        $attribute = $before ? 'max' : 'min';

        $this->setAttribute('data-ff_field', $fieldName);
        $this->setAttribute('data-ff_attribute', $attribute);
        $this->setAttribute('data-ff_id', $this->getID());

        if ($this->form_id_submitted) {
            $date     = $_REQUEST[$this->form_id_submitted . '-' . $value[0]];
            $modifier = $before ? '- 1 day' : '+ 1 day';
            $this->setAttribute($attribute, date('Y-m-d', strtotime($date . ' ' . $modifier)));
        }
    }

    /**
     * Point this field's data-ff_* attributes at another field it must be dated before (see beforeAfter()).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateBeforeField(array $v): void
    {
        $this->setAttribute('data-ff_validator', 'dateBeforeField');
        $this->beforeAfter($v, true);
    }

    /**
     * Point this field's data-ff_* attributes at another field it must be dated after (see beforeAfter()).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateAfterField(array $v): void
    {
        $this->setAttribute('data-ff_validator', 'dateAfterField');
        $this->beforeAfter($v, false);
    }

    /**
     * Shared implementation for the dateWithinDaysRange/
     * dateOutsideOfDaysRange HTML5 validators: points the field's
     * data-ff_* attributes at the comparison field and day offset, and
     * pre-fills min/max from the submitted value of that field (if the
     * form was already submitted).
     * @param array $value Validator arguments; $value[0] is the comparison field's name, $value[1] the day offset
     * @param string $type The validator name ("dateWithinDaysRange" or "dateOutsideOfDaysRange")
     * @return void
     */
    private function withinOutside(array $value, string $type): void
    {
        $fieldName = str_replace($this->getID() . '-', '', $value[0]);

        $this->setAttribute('data-ff_field', $fieldName);
        $this->setAttribute('data-ff_days', (string) $value[1]);
        $this->setAttribute('data-ff_attribute', $value[1] > 0 ? 'min' : 'max');
        $this->setAttribute('data-ff_validator', $type);
        $this->setAttribute('data-ff_id', $this->getID());

        if ($this->form_id_submitted) {
            $ref = $_REQUEST[$this->form_id_submitted . '-' . $value[0]];
            if ($type === 'withinOutside') {
                $this->setAttribute('min', $ref);
                $this->setAttribute('max', $ref);
            } else {
                $this->setAttribute($value[1] > 0 ? 'min' : 'max', $ref);
            }
        }
    }

    /**
     * Point this field's data-ff_* attributes at another field and a day offset it must fall within (see withinOutside()).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateWithinDaysRange(array $v): void
    {
        $this->withinOutside($v, 'dateWithinDaysRange');
    }
    /**
     * Point this field's data-ff_* attributes at another field and a day offset it must fall outside of (see withinOutside()).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5dateOutsideOfDaysRange(array $v): void
    {
        $this->withinOutside($v, 'dateOutsideOfDaysRange');
    }

    /**
     * Remove the HTML5 "min"/"max" attributes added by addHTML5dateWithinDaysRange().
     * @return void
     */
    protected function removeHTML5dateWithinDaysRange(): void
    {
        $this->removeAttribute('min');
        $this->removeAttribute('max');
    }

    /**
     * Remove the HTML5 "min"/"max" attributes added by addHTML5dateOutsideOfDaysRange().
     * @return void
     */
    protected function removeHTML5dateOutsideOfDaysRange(): void
    {
        $this->removeAttribute('min');
        $this->removeAttribute('max');
    }

    /**
     * Point this field's data-ff_* attributes at another field that, if empty, makes this field required (client-side conditional-required support).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5requiredIfEmpty(array $v): void
    {
        $this->setAttribute('data-ff_field', str_replace($this->getID() . '-', '', $v[0]));
        $this->setAttribute('data-ff_attribute', 'ff-required');
        $this->setAttribute('data-ff_validator', 'requiredIfEmpty');
    }

    /**
     * Remove the data-ff_* attributes added by addHTML5requiredIfEmpty().
     * @return void
     */
    protected function removeHTML5requiredIfEmpty(): void
    {
        $this->removeAttribute('data-ff_field');
        $this->removeAttribute('data-ff_attribute');
        $this->removeAttribute('data-ff_validator');
    }


    /**
     * Point this field's data-ff_* attributes at another field and value(s) that, if matched, make this field required (client-side conditional-required support).
     * @param array $v Validator arguments
     * @return void
     */
    protected function addHTML5requiredIfEqual(array $v): void
    {
        $this->setAttribute('data-ff_field', str_replace($this->getID() . '-', '', $v[0]));
        $this->setAttribute('data-ff_attribute', 'ff-required');
        $this->setAttribute('data-ff_validator', 'requiredIfEqual');

        if (isset($v[1])) {
            if (str_contains($v[1], '|')) {
                $operator = isset($v[2]) ? ($v[2] ? 'AND' : 'OR') : 'OR';
                $this->setAttribute('data-ff_operator', $operator);
            }
            $this->setAttribute('data-ff_equal', $v[1]);
        }
    }

    /**
     * Remove the data-ff_* attributes added by addHTML5requiredIfEqual().
     * @return void
     */
    protected function removeHTML5requiredIfEqual(): void
    {
        $this->removeAttribute('data-ff_field');
        $this->removeAttribute('data-ff_attribute');
        $this->removeAttribute('data-ff_validator');
    }
}
