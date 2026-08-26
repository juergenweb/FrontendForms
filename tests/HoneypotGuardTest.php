<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\Form;
use FrontendForms\HoneypotGuard;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for HoneypotGuard.
 *
 * Fully self-contained (no live session state, no redirects), so all
 * methods are deterministically testable.
 */
final class HoneypotGuardTest extends TestCase
{

    /**
     * WireInputData's constructor takes its array argument by reference,
     * so it can't be passed a literal array expression directly at the
     * call site (new WireInputData([...])) - wrapping it in a real
     * parameter variable here satisfies that requirement.
     */
    private function inputData(array $data = []): WireInputData
    {
        return new WireInputData($data);
    }
    private function guard(): HoneypotGuard
    {
        return new HoneypotGuard($this->inputData([]), new Form('myform'), new Alert());
    }

    // --- getMessage() ---

    /**
     * 1) getMessage() returns a non-empty string.
     */
    public function testGetMessageReturnsNonEmptyString(): void
    {
        $this->assertNotSame('', $this->guard()->getMessage());
    }

    // --- createField() ---

    /**
     * 2) The honeypot field's "name" attribute is set to the given fully
     * qualified element name (not the internal FIELD_NAME constant).
     */
    public function testCreateFieldSetsNameFromElementName(): void
    {
        $field = $this->guard()->createField('myform-seca', true, true);

        $this->assertSame('myform-seca', $field->getAttribute('name'));
    }

    /**
     * 3) The field, its label, its field wrapper, and its input wrapper all
     * carry the honeypot CSS class (FIELD_NAME).
     */
    public function testCreateFieldAppliesHoneypotCssClassEverywhere(): void
    {
        $field = $this->guard()->createField('myform-seca', true, true);

        $this->assertContains(HoneypotGuard::FIELD_NAME, $field->getAttribute('class'));
        $this->assertContains(HoneypotGuard::FIELD_NAME, $field->getFieldWrapper()->getAttribute('class'));
        $this->assertContains(HoneypotGuard::FIELD_NAME, $field->getInputWrapper()->getAttribute('class'));
    }

    /**
     * 4) The field is excluded from the tab order (tabindex="-1"), so
     * keyboard users tabbing through the form never land on it.
     */
    public function testCreateFieldSetsNegativeTabindex(): void
    {
        $field = $this->guard()->createField('myform-seca', true, true);

        $this->assertSame('-1', $field->getAttribute('tabindex'));
    }

    /**
     * 5) The field's label is set to the guard's translated message.
     */
    public function testCreateFieldUsesMessageAsLabel(): void
    {
        $guard = $this->guard();

        $field = $guard->createField('myform-seca', true, true);

        $this->assertSame($guard->getMessage(), $field->getLabel()->getText());
    }

    // --- insertIntoElements() ---

    /**
     * 6) With rotation stopped, the honeypot is always inserted at the
     * very first position, regardless of the given input field keys.
     */
    public function testInsertIntoElementsWithStoppedRotationInsertsFirst(): void
    {
        $guard = $this->guard();
        $honeypot = new InputText('seca');
        $elements = [new InputText('a'), new InputText('b')];

        $guard->insertIntoElements($elements, [0, 1], $honeypot, true);

        $this->assertSame($honeypot, $elements[0]);
        $this->assertCount(3, $elements);
    }

    /**
     * 7) With rotation enabled and only a single candidate position given,
     * the honeypot is deterministically inserted at that exact position
     * (shuffling a one-element array has only one possible outcome).
     */
    public function testInsertIntoElementsWithRotationAndSinglePositionIsDeterministic(): void
    {
        $guard = $this->guard();
        $honeypot = new InputText('seca');
        $a = new InputText('a');
        $b = new InputText('b');
        $elements = [$a, $b];

        $guard->insertIntoElements($elements, [1], $honeypot, false);

        $this->assertSame([$a, $honeypot, $b], $elements);
    }

    /**
     * 8) With rotation enabled, the honeypot ends up somewhere in the
     * resulting element list exactly once, regardless of which of the
     * given positions the shuffle picks.
     */
    public function testInsertIntoElementsWithRotationAddsHoneypotExactlyOnce(): void
    {
        $guard = $this->guard();
        $honeypot = new InputText('seca');
        $elements = [new InputText('a'), new InputText('b'), new InputText('c')];

        $guard->insertIntoElements($elements, [0, 1, 2], $honeypot, false);

        $this->assertCount(4, $elements);
        $this->assertSame(1, count(array_filter($elements, fn ($e) => $e === $honeypot)));
    }
}