<?php

declare(strict_types=1);

namespace FrontendForms;

use Exception;

/**
 * CaptchaManager
 *
 * Coordinates CAPTCHA behaviour for a Form instance: selecting the
 * CAPTCHA type/category via AbstractCaptchaFactory, managing the
 * question pool used by the SimpleQuestionCaptcha, building the
 * CAPTCHA input field, and applying its required validation rule.
 *
 * Configuration values (messages, placeholder, position, etc.) are
 * held separately in CaptchaConfig; this class only handles behaviour.
 *
 * @package FrontendForms\Captcha
 */
final class CaptchaManager
{
    private CaptchaConfig $config;
    private object|null $captcha = null; // the underlying AbstractCaptcha instance (e.g. SimpleQuestionCaptcha)
    private object|null $field = null; // the created input field for this CAPTCHA
    private array $questionPool = []; // pool of possible questions for the SimpleQuestionCaptcha
    private int|null $currentQuestionIndex = null; // array key of the currently active random question

    /**
     * @param Form $form the Form instance this CAPTCHA manager belongs to
     */
    public function __construct(private readonly Form $form)
    {
        $this->config = new CaptchaConfig();
    }

    /**
     * Get the CAPTCHA's configuration value object (messages, placeholder,
     * position, ...). Returned by reference, not by copy - callers should
     * always go through setType() to change the CAPTCHA type itself, since
     * that also instantiates the matching CAPTCHA object; changing
     * $config->type directly here would leave getCaptchaObject() out of
     * sync with isActive().
     * @return CaptchaConfig
     */
    public function config(): CaptchaConfig
    {
        return $this->config;
    }

    /**
     * Set (or disable) the CAPTCHA type and instantiate the matching CAPTCHA object
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->config->type = $type;
        if ($type !== 'none') {
            $this->config->category = AbstractCaptchaFactory::getCaptchaTypeFromClass($type);
            $this->captcha = AbstractCaptchaFactory::make($this->config->category, $type);
        }
    }

    /**
     * Disable the CAPTCHA for this form (shorthand for setType('none'))
     * @return void
     */
    public function disable(): void
    {
        $this->setType('none');
    }

    /**
     * Whether a CAPTCHA is currently enabled for this form
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->config->type !== 'none';
    }

    /**
     * Get the underlying AbstractCaptcha-derived instance (e.g.
     * SimpleQuestionCaptcha), or null if no CAPTCHA type has been set yet
     * @return object|null
     */
    public function getCaptchaObject(): object|null
    {
        return $this->captcha;
    }

    /**
     * Get the CAPTCHA input field built by the last buildField() call, or
     * null if buildField() hasn't been called yet
     * @return object|null
     */
    public function getField(): object|null
    {
        return $this->field;
    }

    /**
     * Add a single question to the question pool (used by the SimpleQuestionCaptcha)
     * @param string $question
     * @param array $answers
     * @param array $options - additional per-question overrides (notes, description, placeholder, ...)
     * @return void
     */
    public function addQuestion(string $question, array $answers, array $options = []): void
    {
        if (!str_ends_with($question, '?')) {
            $question .= '?';
        }
        $array = ['question' => $question, 'answers' => $answers];
        foreach ($options as $k => $v) {
            $array[$k] = $v;
        }
        $this->questionPool[] = array_filter($array);
    }

    /**
     * Add multiple questions at once
     * @param array $questions
     * @return void
     */
    public function addQuestions(array $questions): void
    {
        foreach ($questions as $question) {
            $q = $question['question'] ?? null;
            $a = $question['answers'] ?? [];
            unset($question['question'], $question['answers']);
            if ($q !== null) {
                $this->addQuestion($q, $a, $question);
            }
        }
    }

    /**
     * Whether at least one question has been added to the pool
     * @return bool
     */
    public function hasQuestions(): bool
    {
        return (bool)$this->questionPool;
    }

    /**
     * Get the full pool of questions added so far (for the SimpleQuestionCaptcha)
     * @return array
     */
    public function getQuestionPool(): array
    {
        return $this->questionPool;
    }

    /**
     * Get the array key (within the question pool) of the question picked
     * by the last pickRandomQuestion() call, or null if none was picked yet
     * @return int|null
     */
    public function getCurrentQuestionIndex(): int|null
    {
        return $this->currentQuestionIndex;
    }

    /**
     * Pick a random question out of the pool for the SimpleQuestionCaptcha.
     * Ported from the former Form::getRandomQuestion(). Per-question option keys
     * (e.g. 'notes', 'description', 'placeholder') are applied by dynamically calling
     * the matching setCaptcha{Name}() setter on the Form, exactly as before, so any
     * externally added setCaptcha*() method keeps working the same way.
     * @return void
     */
    public function pickRandomQuestion(): void
    {
        if (!$this->questionPool) {
            return;
        }
        if ($this->config->type !== 'SimpleQuestionCaptcha') {
            return;
        }

        $randomIndex = array_rand($this->questionPool);
        $randomQuestion = $this->questionPool[$randomIndex];

        // only apply a random question if at least the question and the answer keys are present
        if (!empty($randomQuestion['question']) && !empty($randomQuestion['answers'])) {
            if (is_string($randomQuestion['question']) && is_array($randomQuestion['answers'])) {
                $this->addQuestion($randomQuestion['question'], $randomQuestion['answers']);

                unset($randomQuestion['question'], $randomQuestion['answers']);
                // unset values that will be displayed only after post
                unset($randomQuestion['successMsg'], $randomQuestion['errorMsg']);

                // apply additional per-question properties if present, via the Form's public setters
                foreach ($randomQuestion as $name => $value) {
                    $methodName = 'setCaptcha' . ucfirst($name);
                    if (method_exists($this->form, $methodName)) {
                        $this->form->$methodName($value);
                    }
                }
            }
        }

        $this->currentQuestionIndex = $randomIndex;
    }

    /**
     * Build the CAPTCHA input field: create it, apply label/placeholder/notes/description,
     * and set the required validation rule.
     * Ported from the former captcha block inside Form::___isValid().
     * @param string $formId
     * @param mixed $input - the WireInput(Data) object for the current request method (get/post)
     * @return object|null
     * @throws Exception
     */
    public function buildField(string $formId, mixed $input): object|null
    {
        if (!$this->isActive() || !$this->captcha) {
            return null;
        }

        $field = $this->captcha->createCaptchaInputField($formId);

        // check if multi question -> if yes, add the question as label to the CAPTCHA
        if ($this->config->type === 'SimpleQuestionCaptcha' && $this->questionPool && $this->currentQuestionIndex !== null) {
            $currentQuestion = $this->questionPool[$this->currentQuestionIndex];
            $field->setLabel($currentQuestion['question']);
        }

        // add placeholder attribute if present
        if ($this->config->placeholder && ($field instanceof InputText)) {
            $field->setAttribute('placeholder', $this->config->placeholder);
        }

        // add notes to the captcha input field if set
        if ($this->config->notes) {
            $field->setNotes($this->config->notes);
        }

        // add description and position to the captcha input field if set
        if ($this->config->description) {
            $field->setDescription($this->config->description)->setPosition($this->config->descriptionPosition);
        }

        if ($this->config->requiredErrorMsg) {
            $field->setRule('required')->setCustomMessage($this->config->requiredErrorMsg);
        } else {
            if ($this->config->type === 'SliderCaptcha') {
                $checkboxID = $formId . '-' . $field->getID();
                // workaround for checked checkbox with empty string as value
                // will be needed for the required validator to work properly in this case
                if (!is_null($input->$checkboxID) && ($input->$checkboxID == '')) {
                    $input->set($checkboxID, '1');
                }
                $field->setRule('required')->setCustomMessage(
                    $this->form->_('Please verify that you are a human and not a bot.')
                );
            } else {
                $field->setRule('required')->setCustomMessage(
                    $this->form->_('Please fill out the security question.')
                );
            }
        }

        $this->field = $field;
        return $field;
    }
}