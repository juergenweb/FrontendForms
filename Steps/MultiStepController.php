<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * MultiStepController
 *
 * Coordinates multi-step form behaviour: the list of step markers, which
 * step is currently active (first/last/number), the resulting form-element
 * slices per step, the first/last input field of the current step range,
 * per-step session values, and the progress bar (built-in or custom).
 *
 * This class only holds state and pure calculations (getSlices()); the
 * actual step-switching control flow (redirects, button labels, session
 * persistence) remains in Form::___isValid()/render(), which reads and
 * writes this state through the methods below.
 *
 * @package FrontendForms\Steps
 */
final class MultiStepController
{
    private array $steps = []; // array containing the step markers (each: ['position' => int])
    private bool $showStepsOf = true; // show "step x of y" text on each step or not
    private bool $showStepsProgressbar = true; // show the progressbar on top of the multi-step form
    private int|string $totalStepsNumber = 0; // the amount of total steps
    private bool $firstStep = false;
    private bool $lastStep = false;
    private object|null $firstElement = null;
    private object|null $lastElement = null;
    private int $currentStepNumber = 1;
    private string $customProgressbar = '';
    private string $lastStepListText = '';
    private array $lastStepElements = [];
    private Progressbar $progressbar;

    public function __construct(private readonly Form $form, string $id)
    {
        $this->progressbar = new Progressbar($id . '-steps-progress');
    }

    public function getProgressbar(): Progressbar
    {
        return $this->progressbar;
    }

    /**
     * Whether this form has any step markers set (i.e. it is a multi-step form)
     * @return bool
     */
    public function hasSteps(): bool
    {
        return (bool)$this->steps;
    }

    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * Add a step marker at the given position (the index of the form element
     * right after which the step should be split)
     * @param int $position
     * @return void
     */
    public function addStep(int $position): void
    {
        $this->steps[] = ['position' => $position];
    }

    public function isFirstStep(): bool
    {
        return $this->firstStep;
    }

    public function setFirstStep(bool $firstStep): void
    {
        $this->firstStep = $firstStep;
    }

    public function isLastStep(): bool
    {
        return $this->lastStep;
    }

    public function setLastStep(bool $lastStep): void
    {
        $this->lastStep = $lastStep;
    }

    public function getCurrentStepNumber(): int
    {
        return $this->currentStepNumber;
    }

    public function setCurrentStepNumber(int $stepNumber): void
    {
        $this->currentStepNumber = $stepNumber;
    }

    public function getTotalSteps(): int|string
    {
        return $this->totalStepsNumber;
    }

    public function setTotalSteps(int $total): void
    {
        $this->totalStepsNumber = $total;
    }

    public function getFirstElement(): object|null
    {
        return $this->firstElement;
    }

    public function setFirstElement(object|null $element): void
    {
        $this->firstElement = $element;
    }

    public function getLastElement(): object|null
    {
        return $this->lastElement;
    }

    public function setLastElement(object|null $element): void
    {
        $this->lastElement = $element;
    }

    public function getLastStepElements(): array
    {
        return $this->lastStepElements;
    }

    public function setLastStepElements(array $elements): void
    {
        $this->lastStepElements = $elements;
    }

    public function getCustomProgressbar(): string
    {
        return $this->customProgressbar;
    }

    /**
     * Add the markup for a custom progress bar. This disables the default progressbar.
     * @param string $customProgressbar
     * @return void
     */
    public function setCustomProgressbar(string $customProgressbar): void
    {
        $this->customProgressbar = trim($customProgressbar);
    }

    public function showsStepsOf(): bool
    {
        return $this->showStepsOf;
    }

    public function setShowStepsOf(bool $showStepsOf): void
    {
        $this->showStepsOf = $showStepsOf;
    }

    public function showsStepsProgressbar(): bool
    {
        return $this->showStepsProgressbar;
    }

    public function setShowStepsProgressbar(bool $showStepsProgressbar): void
    {
        $this->showStepsProgressbar = $showStepsProgressbar;
    }

    public function getLastStepListText(): string
    {
        return $this->lastStepListText;
    }

    public function setLastStepListText(string $text): void
    {
        $this->lastStepListText = $text;
    }

    /**
     * Get all slices which contain the form elements of each step
     * @param Button|ResetButton|null $submitButton
     * @param Button|ResetButton|null $resetButton
     * @return array
     */
    public function getSlices(Button|ResetButton|null $submitButton = null, Button|ResetButton|null $resetButton = null): array
    {
        // add step on first position (= position 0) if not present
        if ($this->steps && $this->steps[0]['position'] !== 0) {
            array_unshift($this->steps, ['position' => 0]);
        }
        $stepsPositions = [];

        foreach ($this->steps as $key => $step) {
            $stepsPositions[$key] = $step['position'];
        }

        $slices = [];

        foreach ($stepsPositions as $key => $stepPosition) {
            $start = $stepPosition;
            if ($key < array_key_last($stepsPositions)) {
                $end = $stepsPositions[$key + 1] - 1;
            } else {
                // last step
                $start = 0;

                // number of buttons
                $subtract = 1;
                if ($submitButton) {
                    $subtract = $subtract + 1;
                }
                if ($resetButton) {
                    $subtract = $subtract + 1;
                }

                $end = count($this->form->getFormElements()) - $subtract;
            }
            $slices[$key + 1] = ['start' => $start, 'end' => $end];
        }

        return $slices;
    }

    /**
     * Get all values of all steps or of a certain step
     * @param int|null $stepNumber
     * @return array
     */
    public function getStepValues(int|null $stepNumber = null): array
    {
        $result = [];
        $values = $this->form->wire('session')->get($this->form->getID() . '-values');
        if ($values) {
            if ($stepNumber === null) {
                $result = $values;
            } else {
                if (array_key_exists($stepNumber, $values)) {
                    $result = $values[$stepNumber];
                }
            }
        }

        return $result;
    }

    /**
     * Get the value of a specific form field of a multi-step form as stored inside the session of a multi-step form
     * Enter the form field name attribute as parameter to get the value of the field
     * @param string $name
     * @return string|null
     */
    public function getStepValueByName(string $name): ?string
    {
        // check first if $name contains the form id
        if (!str_starts_with($name, $this->form->getID())) {
            // add the id to the beginning first
            $name = $this->form->getID() . '-' . $name;
        }

        // find the given array key $name inside the session array
        foreach ($this->getStepValues() as $step => $values) {
            if (array_key_exists($name, $values)) {
                return $values[$name];
            }
        }
        return null;
    }
}