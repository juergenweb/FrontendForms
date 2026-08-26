<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Button;
use FrontendForms\Form;
use FrontendForms\MultiStepController;
use FrontendForms\ResetButton;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MultiStepController.
 *
 * Focuses on getSlices(), which calculates the [start, end] form-element
 * boundaries for each step. A mock Form (only getFormElements() is relevant
 * to the methods under test) is used to isolate this from the rest of the
 * Form class.
 *
 * IMPORTANT: the last slice's "start" is *always* forced back to 0, no matter
 * how many steps precede it or where their markers are. This looks odd in
 * isolation, but it is intentional: the last step of a multi-step form shows
 * a review/summary of every previous step's values (see Form::render()),
 * which needs access to all form elements from the beginning, not just the
 * elements belonging to the last step itself. All expected values below were
 * confirmed by running the actual getSlices() algorithm standalone before
 * writing the assertions, specifically to avoid re-introducing the wrong
 * assumption from an earlier (reverted) "fix" attempt during this session.
 */
final class MultiStepControllerTest extends TestCase
{
    /**
     * Build a MultiStepController with a mock Form whose getFormElements()
     * returns an array with the given number of (dummy) elements.
     *
     * @param int $formElementCount
     * @return MultiStepController
     */
    private function createController(int $formElementCount = 0): MultiStepController
    {
        $form = $this->createMock(Form::class);
        $form->method('getFormElements')
            ->willReturn(array_fill(0, $formElementCount, null));

        return new MultiStepController($form, 'test-form');
    }

    // --- getSlices() ---

    /**
     * 1) A form without any step markers produces no slices at all.
     */
    public function testGetSlicesWithNoStepsReturnsEmptyArray(): void
    {
        $controller = $this->createController();

        $this->assertSame([], $controller->getSlices());
    }

    /**
     * 2) A single step marker produces two slices. The first slice is bounded
     * normally; the last slice's "start" is forced to 0 (see class docblock).
     */
    public function testGetSlicesWithOneStepMarkerProducesTwoSlices(): void
    {
        // 6 elements: fields 0-4, submit button at index 5
        $controller = $this->createController(6);
        $controller->addStep(5);

        $slices = $controller->getSlices();

        $this->assertSame(
            [
                1 => ['start' => 0, 'end' => 4],
                2 => ['start' => 0, 'end' => 5],
            ],
            $slices
        );
    }

    /**
     * 3) Three steps (markers at position 5 and 10, 12 form elements in total
     * including the submit button): the middle slice keeps its real
     * boundaries, but the last slice's "start" is reset to 0 by design (see
     * class docblock) so the review step has access to every field.
     */
    public function testGetSlicesWithThreeStepsResetsStartOfLastSliceToZero(): void
    {
        $controller = $this->createController(12);
        $controller->addStep(5);
        $controller->addStep(10);

        $slices = $controller->getSlices();

        $this->assertSame(
            [
                1 => ['start' => 0, 'end' => 4],
                2 => ['start' => 5, 'end' => 9],
                3 => ['start' => 0, 'end' => 11],
            ],
            $slices
        );
    }

    /**
     * 4) Passing a submit button reduces the last slice's "end" by one
     * (the button itself is appended separately afterward, not part of
     * the field slice). The "start" of the last slice is still 0.
     */
    public function testGetSlicesWithSubmitButtonShrinksLastSliceByOne(): void
    {
        $controller = $this->createController(15);
        $controller->addStep(5);
        $controller->addStep(10);

        $submitButton = $this->createMock(Button::class);

        $slices = $controller->getSlices($submitButton);

        $this->assertSame(['start' => 0, 'end' => 4], $slices[1]);
        $this->assertSame(['start' => 5, 'end' => 9], $slices[2]);
        $this->assertSame(['start' => 0, 'end' => 13], $slices[3]);
    }

    /**
     * 5) Passing both a submit and a reset button reduces the last slice's
     * "end" by two in total.
     */
    public function testGetSlicesWithSubmitAndResetButtonShrinksLastSliceByTwo(): void
    {
        $controller = $this->createController(15);
        $controller->addStep(5);
        $controller->addStep(10);

        $submitButton = $this->createMock(Button::class);
        $resetButton = $this->createMock(ResetButton::class);

        $slices = $controller->getSlices($submitButton, $resetButton);

        $this->assertSame(['start' => 0, 'end' => 12], $slices[3]);
    }

    /**
     * 6) getSlices() is idempotent: calling it a second time (e.g. once with
     * button arguments during isValid(), once without during render()) must
     * not change the non-last slices, since the auto-inserted position-0
     * marker must not be inserted twice.
     */
    public function testGetSlicesIsIdempotentAcrossMultipleCalls(): void
    {
        $controller = $this->createController(12);
        $controller->addStep(5);
        $controller->addStep(10);

        $first = $controller->getSlices();
        $second = $controller->getSlices();

        $this->assertSame($first[1], $second[1]);
        $this->assertSame($first[2], $second[2]);
    }

    // --- hasSteps() / addStep() ---

    /**
     * 7) A freshly created controller (no addStep() calls) reports no steps.
     */
    public function testHasStepsIsFalseByDefault(): void
    {
        $controller = $this->createController();

        $this->assertFalse($controller->hasSteps());
        $this->assertSame([], $controller->getSteps());
    }

    /**
     * 8) After adding a step marker, hasSteps() reports true and the marker
     * is stored with the given position.
     */
    public function testAddStepMarksHasStepsTrueAndStoresPosition(): void
    {
        $controller = $this->createController();
        $controller->addStep(7);

        $this->assertTrue($controller->hasSteps());
        $this->assertSame([['position' => 7]], $controller->getSteps());
    }

    // --- simple state getters/setters ---

    /**
     * 9) isFirstStep()/isLastStep()/getCurrentStepNumber()/getTotalSteps()
     * round-trip the values passed to their respective setters.
     */
    public function testStepStateGettersReturnValuesFromSetters(): void
    {
        $controller = $this->createController();

        $controller->setFirstStep(true);
        $controller->setLastStep(false);
        $controller->setCurrentStepNumber(2);
        $controller->setTotalSteps(3);

        $this->assertTrue($controller->isFirstStep());
        $this->assertFalse($controller->isLastStep());
        $this->assertSame(2, $controller->getCurrentStepNumber());
        $this->assertSame(3, $controller->getTotalSteps());
    }

    /**
     * 10) getProgressbar() returns the same Progressbar instance on every call.
     */
    public function testGetProgressbarReturnsSameInstance(): void
    {
        $controller = $this->createController();

        $this->assertSame($controller->getProgressbar(), $controller->getProgressbar());
    }

    /**
     * 11) getFirstElement()/getLastElement() default to null and round-trip
     * whatever object (or null) is passed to their setters.
     */
    public function testFirstAndLastElementDefaultToNullAndRoundTrip(): void
    {
        $controller = $this->createController();

        $this->assertNull($controller->getFirstElement());
        $this->assertNull($controller->getLastElement());

        $firstField = new \stdClass();
        $lastField = new \stdClass();
        $controller->setFirstElement($firstField);
        $controller->setLastElement($lastField);

        $this->assertSame($firstField, $controller->getFirstElement());
        $this->assertSame($lastField, $controller->getLastElement());

        // setters also accept null again (e.g. to clear a previous selection)
        $controller->setFirstElement(null);
        $this->assertNull($controller->getFirstElement());
    }

    /**
     * 12) getLastStepElements() defaults to an empty array and round-trips
     * whatever array is passed to setLastStepElements().
     */
    public function testLastStepElementsDefaultsToEmptyArrayAndRoundTrips(): void
    {
        $controller = $this->createController();

        $this->assertSame([], $controller->getLastStepElements());

        $elements = [new \stdClass(), new \stdClass()];
        $controller->setLastStepElements($elements);

        $this->assertSame($elements, $controller->getLastStepElements());
    }

    /**
     * 13) getCustomProgressbar() defaults to an empty string, and
     * setCustomProgressbar() trims the value before storing it.
     */
    public function testCustomProgressbarDefaultsToEmptyStringAndIsTrimmed(): void
    {
        $controller = $this->createController();

        $this->assertSame('', $controller->getCustomProgressbar());

        $controller->setCustomProgressbar('  <div class="my-bar"></div>  ');

        $this->assertSame('<div class="my-bar"></div>', $controller->getCustomProgressbar());
    }

    /**
     * 14) showsStepsOf() defaults to true and round-trips the value passed
     * to setShowStepsOf().
     */
    public function testShowsStepsOfDefaultsToTrueAndRoundTrips(): void
    {
        $controller = $this->createController();

        $this->assertTrue($controller->showsStepsOf());

        $controller->setShowStepsOf(false);

        $this->assertFalse($controller->showsStepsOf());
    }

    /**
     * 15) showsStepsProgressbar() defaults to true and round-trips the value
     * passed to setShowStepsProgressbar().
     */
    public function testShowsStepsProgressbarDefaultsToTrueAndRoundTrips(): void
    {
        $controller = $this->createController();

        $this->assertTrue($controller->showsStepsProgressbar());

        $controller->setShowStepsProgressbar(false);

        $this->assertFalse($controller->showsStepsProgressbar());
    }

    /**
     * 16) getLastStepListText() defaults to an empty string and round-trips
     * the value passed to setLastStepListText().
     */
    public function testLastStepListTextDefaultsToEmptyStringAndRoundTrips(): void
    {
        $controller = $this->createController();

        $this->assertSame('', $controller->getLastStepListText());

        $controller->setLastStepListText('Please review your entries below.');

        $this->assertSame('Please review your entries below.', $controller->getLastStepListText());
    }

    // --- getStepValueByName() ---

    /**
     * getStepValueByName() reads from the real session, so a real Form
     * instance (not the getFormElements()-only mock used above) is needed
     * here for working wire('session') access.
     */
    private function controllerWithRealForm(string $formId = 'stepform'): array
    {
        $form = new Form($formId);
        $controller = new MultiStepController($form, $formId);

        return [$form, $controller];
    }

    /**
     * 17) A field name is found across the stored per-step session values,
     * regardless of which step it was recorded under, and the form-id
     * prefix is added automatically if not already present.
     */
    public function testGetStepValueByNameFindsValueAcrossSteps(): void
    {
        [$form, $controller] = $this->controllerWithRealForm('stepform-find');

        $form->wire('session')->set($form->getID() . '-values', [
            1 => ['stepform-find-email' => 'test@example.com'],
            2 => ['stepform-find-message' => 'Hello'],
        ]);

        $this->assertSame('test@example.com', $controller->getStepValueByName('email'));
        $this->assertSame('Hello', $controller->getStepValueByName('stepform-find-message'));
    }

    /**
     * 18) A field name that doesn't exist in any step's stored values
     * returns null.
     */
    public function testGetStepValueByNameReturnsNullForUnknownField(): void
    {
        [$form, $controller] = $this->controllerWithRealForm('stepform-unknown');

        $form->wire('session')->set($form->getID() . '-values', [
            1 => ['stepform-unknown-email' => 'test@example.com'],
        ]);

        $this->assertNull($controller->getStepValueByName('nonexistent'));
    }

    /**
     * 19) With no step values stored in the session at all, the lookup
     * returns null rather than throwing. Uses its own unique form id to
     * guarantee no session state leaked in from the other tests above
     * (session data persists across test methods within the same run,
     * since it isn't reset between them).
     */
    public function testGetStepValueByNameReturnsNullWhenNoSessionValuesExist(): void
    {
        [, $controller] = $this->controllerWithRealForm('stepform-empty-' . uniqid());

        $this->assertNull($controller->getStepValueByName('email'));
    }
}
