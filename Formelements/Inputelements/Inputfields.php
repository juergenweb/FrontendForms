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

    const patternInputs = ['text', 'password', 'email', 'search', 'url'];

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

    public function patternAttributeAllowed(): bool
    {
        return in_array($this->getAttribute('type'), ['email', 'password', 'search', 'tel', 'text', 'url']);
    }

    public function useCustomWrapper(bool $use = true): Wrapper
    {
        $this->useCustomWrapper = $use;
        return $this->customWrapper;
    }

    public function getCustomWrapper(): Wrapper
    {
        return $this->customWrapper;
    }

    private function getFormIDFromRequest(array $arr): string
    {
        foreach ($arr as $key => $val) {
            if (str_ends_with($key, '-form_id')) {
                return $val;
            }
        }
        return '';
    }

    public function useInputWrapper(bool $useInputWrapper): void
    {
        $this->useInputWrapper = $useInputWrapper;
    }

    public function getUsageOfInputWrapper(): bool|null
    {
        return $this->useInputWrapper;
    }

    public function useFieldWrapper(bool $useFieldWrapper): void
    {
        $this->useFieldWrapper = $useFieldWrapper;
    }

    public function getUsageOfFieldWrapper(): bool|null
    {
        return $this->useFieldWrapper;
    }

    public function getInputWrapper(): InputWrapper
    {
        return $this->inputWrapper;
    }

    public function getFieldWrapper(): FieldWrapper
    {
        return $this->fieldWrapper;
    }

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

    private function array_filter_recursive(array $input): array
    {
        foreach ($input as &$value) {
            if (is_array($value)) {
                $value = self::array_filter_recursive($value);
            }
        }
        return array_filter($input);
    }

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

        // Prefix field name for equals/different validators
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

    private function applyValidatorNote(string $validator, array $variables): void
    {
        $notes = &$this->notes_array;

        switch ($validator) {
            case 'minFilesInZIPFolder':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP folder(s) must contain at least %s files'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxFilesInZIPFolder':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP folders may not contain more than %s files'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxTotalFileSizeZipUncompressed':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files must not exceed a total size of %s when extracted'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'requiredFileNamesInZip':
                $notes[$validator] = ['text' => sprintf($this->_('ZIP files must contain the following files: %s'), implode(', ', $variables[0])), 'value' => $variables[0]];
                break;
            case 'maxNumberOfZipFolders':
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload more than %s ZIP file(s)'), $variables[0]), 'value' => $variables[0]];
                break;
            case 'maxDepthOfZipFolders':
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
                $notes[$validator] = ['text' => sprintf($this->_('Please do not upload files larger than %s'), wireBytesStr($variables[0])), 'value' => $variables[0]];
                break;
            case 'allowedTotalFileSize':
                $notes[$validator] = ['text' => sprintf($this->_('The total size of all uploaded files must not exceed %s.'), wireBytesStr($variables[0])), 'value' => $variables[0]];
                break;
            case 'allowedFileNumber':
                if (isset($variables[0])) {
                    $notes[$validator] = ['text' => sprintf($this->_('Please do not upload more than %s files'), $variables[0]), 'value' => $variables[0]];
                }
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
        }
    }

    public function removeRule(string $rule): self
    {
        unset($this->validatonRules[$rule], $this->notes_array[$rule]);

        $method = 'removeHTML5' . $rule;
        if (method_exists($this, $method)) {
            $this->$method();
        }

        return $this;
    }

    public function setCustomMessage(string $msg): self
    {
        $this->api->setCustomMessage($msg);
        $this->validatonRules[$this->api->getValidator()] = array_merge(
            $this->validatonRules[$this->api->getValidator()],
            ['customMsg' => $msg]
        );
        return $this;
    }

    public function setCustomFieldname(string $fieldname): self
    {
        $this->api->setCustomFieldName($fieldname);
        $this->validatonRules[$this->api->getValidator()] = array_merge(
            $this->validatonRules[$this->api->getValidator()],
            ['customFieldName' => $fieldname]
        );
        return $this;
    }

    public function __toString(): string
    {
        return $this->render();
    }

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

        if ($className !== 'InputHidden') {
            if ($this->getDescription()->getText()) {
                $this->setAttribute('aria-describedby', $this->getID() . '-desc');
            }
            if ($this->getNotes()->getText()) {
                $this->setAttribute('aria-describedby', $this->getID() . '-notes');
            }
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

    public function ___renderDefault(string $className, string $input): string
    {
        $out         = '';
        $errormsg    = $this->getErrorMessage()->render() . PHP_EOL;
        $successmsg  = $this->getSuccessMessage()->render() . PHP_EOL;
        $description = $this->description;

        switch ($className) {
            case 'InputHidden':
                $this->removeAttribute('class');
                $input_markup = $input;
                break;

            case 'InputCheckbox':
            case 'InputRadio':
            case 'Privacy':
            case 'SendCopy':
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                $this->label->removeAttributeValue('class', $this->getCSSClass('checklabel'));
                if ($this->appendLabel) {
                    $input_markup = $input . PHP_EOL . $this->label->setContent($this->getLabel()->getText())->render() . PHP_EOL;
                } else {
                    $this->label->setContent($input . $this->getLabel()->getText());
                    $input_markup = $this->label->render() . PHP_EOL;
                }
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup .= $errormsg . $successmsg;
                break;

            default:
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                if ($this->getLabel()->getText()) {
                    $out .= $this->getLabel()->render() . PHP_EOL;
                }
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup = $input . $errormsg . $successmsg;
        }

        if ($this->useInputWrapper) {
            $this->inputWrapper->setContent($input_markup);
            $out .= $this->inputWrapper->render() . PHP_EOL;
        } else {
            $out .= $input_markup;
        }

        $out .= $this->getNotes()->render();

        if ($description->getText() && $description->getPosition() === 'afterInput') {
            $out .= $description->render();
        }

        return $out;
    }

    public function ___renderUikit3(string $className, string $input): string
    {
        return $this->renderDefault($className, $input);
    }

    public function ___renderBootstrap5(string $className, string $input): string
    {
        $out        = '';
        $content    = '';
        $errormsg   = $this->getErrorMessage()->render() . PHP_EOL;
        $successmsg = $this->getSuccessMessage()->render() . PHP_EOL;
        $description = $this->description;

        if ($this->getErrorMessage()->getText()) {
            $this->getLabel()->setCSSClass('input_errorClass');
        } elseif ($_POST) {
            $this->getLabel()->setCSSClass('input_successClass');
        }

        switch ($className) {
            case 'InputHidden':
                $this->removeAttribute('class');
                $input_markup = $input;
                break;

            case 'InputCheckbox':
            case 'InputRadio':
            case 'Privacy':
            case 'SendCopy':
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $content .= $description->render();
                }
                $this->label->removeAttributeValue('class', $this->getCSSClass('labelClass'));
                $this->label->setCSSClass('checklabelClass');
                $content .= $input . $this->label->render() . PHP_EOL;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $content .= $description->render();
                }
                $input_markup = $content . $errormsg . $successmsg;
                break;

            default:
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $content .= $description->render();
                }
                if ($this->getLabel()->getText()) {
                    if (in_array($className, ['InputRadioMultiple', 'InputCheckboxMultiple'])) {
                        $this->getLabel()->setCSSClass(
                            $this->getErrorMessage()->getText() ? 'input_errorClass' : 'input_successClass'
                        );
                    }
                    $content .= $this->getLabel()->render() . PHP_EOL;
                }
                $content .= $input;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $content .= $description->render();
                }
                $input_markup = $content . $errormsg . $successmsg;
        }

        if ($this->useInputWrapper) {
            $this->inputWrapper->setContent($input_markup);
            $out .= $this->inputWrapper->render() . PHP_EOL;
        } else {
            $out .= $input_markup;
        }

        $out .= $this->getNotes()->render();

        if ($description->getText() && $description->getPosition() === 'afterInput') {
            $out .= $description->render();
        }

        return $out;
    }

    public function ___renderPico2(string $className, string $input): string
    {
        $out        = '';
        $errormsg   = $this->getErrorMessage()->render() . PHP_EOL;
        $successmsg = $this->getSuccessMessage()->render() . PHP_EOL;
        $description = $this->description;

        switch ($className) {
            case 'InputHidden':
                $this->removeAttribute('class');
                $input_markup = $input;
                break;

            case 'InputCheckbox':
            case 'InputRadio':
            case 'Privacy':
            case 'SendCopy':
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                $asterisk = '';
                if ($this->getRules() && array_key_exists('required', $this->getRules())) {
                    $asterisk = $this->frontendforms['input_showasterisk']
                        ? strip_tags($this->getLabel()->renderAsterisk())
                        : '';
                }
                $this->label->setContent($input . $this->getLabel()->getText() . $asterisk . $errormsg . $successmsg);
                $this->getLabel()->disableAsterisk();
                $out .= $this->label->render() . PHP_EOL;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup = '';
                break;

            default:
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                if ($this->getLabel()->getText()) {
                    $out .= $this->getLabel()->render() . PHP_EOL;
                }
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup = $input . $errormsg . $successmsg;
        }

        if ($this->useInputWrapper) {
            $this->inputWrapper->setContent($input_markup);
            $out .= $this->inputWrapper->render() . PHP_EOL;
        } else {
            $out .= $input_markup;
        }

        $out .= $this->getNotes()->render();

        if ($description->getText() && $description->getPosition() === 'afterInput') {
            $out .= $description->render();
        }

        return $out;
    }

    public function hasRule(string $ruleName): bool
    {
        return array_key_exists(trim($ruleName), $this->getRules());
    }

    public function getRules(): array
    {
        return $this->validatonRules;
    }

    public function removeAllRules(): void
    {
        $this->validatonRules = [];
    }

    public function getLabel(): Label
    {
        return $this->label;
    }

    public function setLabel(string $label): Label
    {
        $this->label->setText($label);
        return $this->label;
    }

    public function getErrorMessage(): Errormessage
    {
        return $this->errormessage;
    }

    protected function setErrorMessage(string $errorMessage): Errormessage
    {
        $this->errormessage->setText($errorMessage);
        return $this->errormessage;
    }

    public function getSuccessMessage(): Successmessage
    {
        return $this->successmessage;
    }

    protected function setSuccessMessage(string $successMessage): Successmessage
    {
        $this->successmessage->setText($successMessage);
        return $this->successmessage;
    }

    public function getDescription(): Description
    {
        return $this->description;
    }

    public function setDescription(string $description): Description
    {
        $this->description->setText($description);
        return $this->description;
    }

    public function getNotes(): Notes
    {
        return $this->notes;
    }

    public function setNotes(string $notes): Notes
    {
        $this->notes->setText($notes);
        return $this->notes;
    }

    public function getNotesArray(): array
    {
        return $this->notes_array;
    }

    public function removeNotesByKey(int|string $key): Notes
    {
        unset($this->notes_array[$key]);
        return $this->notes;
    }

    protected function getDefaultValue(): string|array|null
    {
        return $this->defaultValue;
    }

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
            array_walk($default, fn(&$item) => $item = trim($item));
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

    public function getSanitizers(): array
    {
        return $this->sanitizer;
    }

    public function hasSanitizer(string $sanitizer): bool
    {
        return in_array(trim(strtolower($sanitizer)), $this->sanitizer);
    }

    protected function getPostValue(): mixed
    {
        if ($this->hasPostValue()) {
            $name = str_replace('[]', '', $this->getAttribute('name'));
            return $this->getServerMethod()[$name];
        }
        return [];
    }

    protected function hasPostValue(): bool
    {
        $name = str_replace('[]', '', $this->getAttribute('name'));
        return isset($this->getServerMethod()[$name]);
    }

    protected function addAriaAttributes(): void
    {
        if (!$this->useAriaAttr) {
            return;
        }

        if ($this->getDescription()->getText()) {
            $this->setAttribute('aria-describedby', $this->getID() . '-desc');
        }
        if ($this->getNotes()->getText()) {
            $this->setAttribute('aria-describedby', $this->getID() . '-notes');
        }
    }

    public function useAriaAttributes(bool $ariaAttr): self
    {
        $this->useAriaAttr = $ariaAttr;
        return $this;
    }

    // HTML5 attribute methods — add/remove pattern, min, max, etc.

    protected function addHTML5required(): void         { $this->setAttribute('required'); }
    protected function removeHTML5required(): void      { $this->removeAttribute('required'); }
    protected function addHTML5min(array $v): void      { $this->setAttribute('min', $v[0]); }
    protected function removeHTML5min(): void           { $this->removeAttribute('min'); }
    protected function addHTML5max(array $v): void      { $this->setAttribute('max', $v[0]); }
    protected function removeHTML5max(): void           { $this->removeAttribute('max'); }
    protected function removeHTML5alpha(): void         { $this->removeAttribute('pattern'); }
    protected function removeHTML5checkBic(): void      { $this->removeAttribute('pattern'); }
    protected function removeHTML5NoNumbers(): void     { $this->removeAttribute('pattern'); }
    protected function removeHTML5checkIban(): void     { $this->removeAttribute('pattern'); }
    protected function removeHTML5alphaNum(): void      { $this->removeAttribute('pattern'); }
    protected function removeHTML5slug(): void          { $this->removeAttribute('pattern'); }
    protected function removeHTML5ascii(): void         { $this->removeAttribute('pattern'); }
    protected function removeHTML5regex(): void         { $this->removeAttribute('pattern'); }
    protected function removeHTML5exactValue(): void    { $this->removeAttribute('pattern'); }
    protected function removeHTML5differentValue(): void { $this->removeAttribute('pattern'); }
    protected function removeHTML5integer(): void       { $this->removeAttribute('pattern'); }
    protected function removeHTML5numeric(): void       { $this->removeAttribute('pattern'); }
    protected function removeHTML5noLetters(): void     { $this->removeAttribute('pattern'); }
    protected function removeHTML5firstAndLastname(): void { $this->removeAttribute('pattern'); }
    protected function removeHTML5contains(): void      { $this->removeAttribute('pattern'); }
    protected function removeHTML5time(): void          { $this->removeAttribute('pattern'); }
    protected function removeHTML5alphaNum2(): void     { $this->removeAttribute('pattern'); }
    protected function removeHTML5usernameSyntax(): void { $this->removeAttribute('pattern'); }
    protected function removeHTML5ip(): void            { $this->removeAttribute('pattern'); }
    protected function removeHTML5ipv4(): void          { $this->removeAttribute('pattern'); }
    protected function removeHTML5ipv6(): void          { $this->removeAttribute('pattern'); }
    protected function removeHTML5allowedFileExt(): void { $this->removeAttribute('accept'); }
    protected function removeHTML5dateBefore(): void    { $this->removeAttribute('max'); }
    protected function removeHTML5dateAfter(): void     { $this->removeAttribute('min'); }
    protected function removeHTML5dateBeforeField(): void { $this->removeAttribute('max'); }
    protected function removeHTML5dateAfterField(): void  { $this->removeAttribute('min'); }
    protected function removeHTML5meetsPasswordConditions(): void { $this->removeAttribute('pattern'); }
    protected function removeHTML5dateFormat(): void    { $this->removeAttribute('pattern'); }

    protected function addHTML5lengthMin(array $v): void
    {
        $this->setAttribute('minlength', (string) $v[0]);
    }

    protected function removeHTML5lengthMin(): void
    {
        $this->removeAttribute('minlength');
    }

    protected function addHTML5lengthMax(array $v): void
    {
        $this->setAttribute('maxlength', $v[0]);
    }

    protected function removeHTML5lengthMax(): void
    {
        $this->removeAttribute('maxlength');
    }

    protected function addHTML5lengthBetween(array $v): void
    {
        $this->setAttribute('minlength ', $v[0]);
        $this->setAttribute('maxlength ', $v[1]);
    }

    protected function removeHTML5lengthBetween(): void
    {
        $this->removeAttribute('minlength');
        $this->removeAttribute('maxlength');
    }

    protected function addHTML5alpha(): void
    {
        $this->setAttribute('pattern', '[a-zA-Z]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters'), $this->getLabel()->getText()));
    }

    protected function addHTML5checkBic(): void
    {
        $this->setAttribute('pattern', '[A-Z0-9]{4}[A-Z]{2}[A-Z0-9]{2}(?:[A-Z0-9]{3})?');
    }

    protected function addHTML5NoNumbers(): void
    {
        $this->setAttribute('pattern', '[^0-9]+');
    }

    protected function addHTML5checkIban(): void
    {
        $this->setAttribute('pattern', '[A-Z]{2}\d{13,32}|(?=.{18,42}$)[A-Z]{2}\d{2}( )(\d{4}\1){2,7}\d{1,4}');
        $this->setAttribute('data-checkiban', 'true');
    }

    protected function addHTML5alphaNum(): void
    {
        $this->setAttribute('pattern', '[a-zA-Z0-9]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters and numbers'), $this->getLabel()->getText()));
    }

    protected function addHTML5firstAndLastname(): void
    {
        $this->setAttribute('pattern ', '[a-zA-ZàáâäãåąčćęèéêëėįìíîïłńòóôöõøùúûüųūÿýżźñçčšžÀÁÂÄÃÅĄĆČĖĘÈÉÊËÌÍÎÏĮŁŃÒÓÔÖÕØÙÚÛÜŲŪŸÝŻŹÑßÇŒÆČŠŽ∂ð,-.\'  ]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain allowed characters for names.'), $this->getLabel()->getText()));
    }

    protected function addHTML5ascii(): void
    {
        $this->setAttribute('pattern ', '[\x00-\x7F]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain ascii characters'), $this->getLabel()->getText()));
    }

    protected function addHTML5slug(): void
    {
        $this->setAttribute('pattern ', '[a-z0-9_\-]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain letters, numbers, underscores or hyphens'), $this->getLabel()->getText()));
    }

    protected function addHTML5url(): void
    {
        if ($this->className() !== 'InputUrl' || is_subclass_of($this, 'InputUrl')) {
            $this->setAttribute('pattern ', 'https?://.{1,63}\.[A-z]{2,13}');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid URL starting with http:// or https://'), $this->getLabel()->getText()));
        }
    }

    protected function removeHTML5url(): void
    {
        if ($this->className() !== 'InputUrl' || is_subclass_of($this, 'InputUrl')) {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5email(): void
    {
        if ($this->className() !== 'InputEmail' || is_subclass_of($this, 'InputEmail')) {
            $this->setAttribute('pattern ', '^([a-zA-Z0-9_\-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid email address'), $this->getLabel()->getText()));
        }
    }

    protected function removeHTML5email(): void
    {
        if ($this->className() !== 'InputEmail' || is_subclass_of($this, 'InputEmail')) {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5time(): void
    {
        if ($this->getAttribute('type') !== 'time') {
            $this->setAttribute('pattern', '([0-1][0-9]|2[0-3]):[0-5][0-9]:[0-5][0-9]');
        }
        $this->setAttribute('title', sprintf($this->_('%s is not a valid time. You have to enter the time in this format: HH:MM:SS (fe. 19:00:00)'), $this->getLabel()->getText()));
    }

    protected function addHTML5numeric(): void
    {
        $this->setAttribute('pattern ', '(([0-9]*)|(([0-9]*)\.([0-9]*)))');
        $this->setAttribute('title', sprintf($this->_('%s should only contain numbers (integers or floats with a dot, not a comma)'), $this->getLabel()->getText()));
    }

    protected function addHTML5integer(): void
    {
        $this->setAttribute('pattern', '[0-9]+');
        $this->setAttribute('title', sprintf($this->_('%s should only contain integers'), $this->getLabel()->getText()));
    }

    protected function addHTML5ip(): void
    {
        $this->setAttribute('pattern ', '(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)_*){3}');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IP address in the format x.x.x.x'), $this->getLabel()->getText()));
    }

    protected function addHTML5ipv4(): void
    {
        $this->setAttribute('pattern ', '((^|\.)((25[0-5])|(2[0-4]\d)|(1\d\d)|([1-9]?\d))){4}$');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IPv4 address in the format x.x.x.x'), $this->getLabel()->getText()));
    }

    protected function addHTML5ipv6(): void
    {
        $this->setAttribute('pattern ', '((^|:)([0-9a-fA-F]{0,4})){1,8}$');
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid IPv6 address in the format x:x:x:x:x:x:x:x'), $this->getLabel()->getText()));
    }

    protected function addHTML5usernameSyntax(): void
    {
        $this->setAttribute('pattern ', '[a-z0-9_\-]{1,128}');
        $this->setAttribute('title', sprintf($this->_('%s contains not allowed characters or is longer than 128 characters. Allowed characters are: letters, numbers, underscores and dashes (no whitespaces)'), $this->getLabel()->getText()));
    }

    protected function addHTML5noLetters(): void
    {
        $label = $this->getLabel()->getText() ?: $this->_('This field');
        $this->setAttribute('pattern', '[^a-zA-ZäöüÖÄÜ]+');
        $this->setAttribute('title', sprintf($this->_('%s contains letters, but they are not allowed'), $label));
    }

    protected function addHTML5contains(): void
    {
        $word = $this->getRules()['contains']['options'][0] ?? null;
        if ($word) {
            $this->setAttribute('pattern', '\b' . $word . '\b');
            $this->setAttribute('title', sprintf($this->_('%s must contain the word %s'), $this->getLabel()->getText(), $word));
        }
    }

    protected function addHTML5month(): void
    {
        if ($this->getAttribute('type') !== 'month') {
            $this->setAttribute('pattern', '^\d{4}-(0[1-9]|1[012])$');
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid month in the format YYYY-MM'), $this->getLabel()->getText()));
        }
    }

    protected function removeHTML5month(): void
    {
        if ($this->getAttribute('type') !== 'month') {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5checkHex(): void
    {
        if ($this->getAttribute('type') !== 'color') {
            $this->setAttribute('pattern', '#([a-fA-F0-9]{3}){1,2}\b');
            $this->setAttribute('title', sprintf($this->_('%s should be a valid HEX code in the format #XXX or #XXXXXX'), $this->getLabel()->getText()));
        }
    }

    protected function removeHTML5checkHex(): void
    {
        if ($this->getAttribute('type') !== 'color') {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5week(): void
    {
        if ($this->getAttribute('type') !== 'week') {
            $this->setAttribute('pattern', '^\d{1,4}-[W](\d|[0-4]\d|5[0123])');
        }
        $this->setAttribute('title', sprintf($this->_('%s should only contain a valid week in the format YYYY-Www'), $this->getLabel()->getText()));
    }

    protected function removeHTML5week(): void
    {
        if ($this->getAttribute('type') !== 'week') {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5date(): void
    {
        if ($this->getAttribute('type') !== 'date') {
            $this->setAttribute('pattern', '^\d{2}.\d{2}.\d{4}');
            $this->setAttribute('title', sprintf($this->_('%s should only contain a valid date in the format dd.MM.YYYY'), $this->getLabel()->getText()));
        }
    }

    protected function removeHTML5date(): void
    {
        if ($this->getAttribute('type') !== 'date') {
            $this->removeAttribute('pattern');
        }
    }

    protected function addHTML5regex(array $v): void
    {
        $pattern = str_replace(['$', 'i', '/'], '', $v[0]);
        $this->setAttribute('pattern ', $pattern);
        $this->setAttribute('title', sprintf($this->_('%s contains an invalid value'), $this->getLabel()->getText()));
    }

    protected function addHTML5exactValue(array $v): void
    {
        $this->setAttribute('pattern ', $v[0]);
        $this->setAttribute('title', sprintf($this->_('%s should contain the exact value %s'), $this->getLabel()->getText(), $v[0]));
    }

    protected function addHTML5differentValue(array $v): void
    {
        $this->setAttribute('pattern', '((?!' . $v[0] . ').)*');
        $this->setAttribute('title', sprintf($this->_('%s should not contain the value %s'), $this->getLabel()->getText(), $v[0]));
    }

    protected function addHTML5allowedFileExt(array $v): void
    {
        $extensions = $v ? array_map(fn($e) => '.' . $e, $v[0]) : [];
        $this->setAttribute('accept', implode(',', $extensions));
    }

    protected function addHTML5dateBefore(array $v): void  { $this->setAttribute('max', $v[0]); }
    protected function addHTML5dateAfter(array $v): void   { $this->setAttribute('min', $v[0]); }

    protected function addHTML5dateFormat(array $v): void
    {
        $format = strtolower($v[0]);
        $dateformats = [
            'dd.mm.yyyy' => '(0[1-9]|1[0-9]|2[0-9]|3[01]).(0[1-9]|1[012]).[0-9]{4}',
            'yyyy.mm.dd' => '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])',
            'mm/dd/yyyy' => '(0[1-9]|1[012])[- /.](0[1-9]|[12][0-9]|3[01])[- /.](19|20)\d\d',
        ];
        if (isset($dateformats[$format])) {
            $this->setAttribute('pattern ', $dateformats[$format]);
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

        $parts  = array_filter($lookAheads, fn($k) => in_array($k, $requirements), ARRAY_FILTER_USE_KEY);
        $length = (string) ($passwordField->minlength ?: $passwordModule->minlength);

        return implode('', $parts) . '.\{' . $length . ',128\}$';
    }

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    protected function addHTML5meetsPasswordConditions(): void
    {
        $regex = $this->createPasswordRegex();
        if ($regex !== null) {
            $this->setAttribute('pattern', $regex);
        }
    }

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

    protected function addHTML5dateBeforeField(array $v): void
    {
        $this->setAttribute('data-ff_validator', 'dateBeforeField');
        $this->beforeAfter($v, true);
    }

    protected function addHTML5dateAfterField(array $v): void
    {
        $this->setAttribute('data-ff_validator', 'dateAfterField');
        $this->beforeAfter($v, false);
    }

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

    protected function addHTML5dateWithinDaysRange(array $v): void   { $this->withinOutside($v, 'dateWithinDaysRange'); }
    protected function addHTML5dateOutsideOfDaysRange(array $v): void { $this->withinOutside($v, 'dateOutsideOfDaysRange'); }

    protected function removeHTML5dateWithinDaysRange(): void
    {
        $this->removeAttribute('min');
        $this->removeAttribute('max');
    }

    protected function removeHTML5dateOutsideOfDaysRange(): void
    {
        $this->removeAttribute('min');
        $this->removeAttribute('max');
    }

    protected function addHTML5requiredIfEmpty(array $v): void
    {
        $this->setAttribute('data-ff_field', str_replace($this->getID() . '-', '', $v[0]));
        $this->setAttribute('data-ff_attribute', 'ff-required');
        $this->setAttribute('data-ff_validator', 'requiredIfEmpty');
    }

    protected function removeHTML5requiredIfEmpty(): void
    {
        $this->removeAttribute('data-ff_field');
        $this->removeAttribute('data-ff_attribute');
        $this->removeAttribute('data-ff_validator');
    }


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

    protected function removeHTML5requiredIfEqual(): void
    {
        $this->removeAttribute('data-ff_field');
        $this->removeAttribute('data-ff_attribute');
        $this->removeAttribute('data-ff_validator');
    }
}
