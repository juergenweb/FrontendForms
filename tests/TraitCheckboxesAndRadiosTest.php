<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Tag;
use FrontendForms\TraitCheckboxesAndRadios;
use PHPUnit\Framework\TestCase;

/**
 * A minimal Tag subclass using TraitCheckboxesAndRadios, for testing the
 * trait in isolation from InputCheckboxMultiple/InputRadioMultiple (which
 * layer their own rendering logic on top).
 */
final class ConcreteCheckboxRadioHolder extends Tag
{
    use TraitCheckboxesAndRadios;
}

/**
 * Unit tests for TraitCheckboxesAndRadios.
 */
final class TraitCheckboxesAndRadiosTest extends TestCase
{
    /**
     * 1) A freshly created element does not append the label by default.
     */
    public function testAppendLabelIsFalseByDefault(): void
    {
        $holder = new ConcreteCheckboxRadioHolder();

        $this->assertFalse($holder->getAppendLabel());
    }

    /**
     * 2) Calling appendLabel() with no argument defaults to true.
     */
    public function testAppendLabelDefaultsArgumentToTrue(): void
    {
        $holder = new ConcreteCheckboxRadioHolder();

        $holder->appendLabel();

        $this->assertTrue($holder->getAppendLabel());
    }

    /**
     * 3) appendLabel() can also be explicitly set to false, e.g. to
     * switch back after previously enabling it.
     */
    public function testAppendLabelCanBeExplicitlyDisabled(): void
    {
        $holder = new ConcreteCheckboxRadioHolder();
        $holder->appendLabel(true);

        $holder->appendLabel(false);

        $this->assertFalse($holder->getAppendLabel());
    }

    /**
     * 4) appendLabel() returns $this, supporting fluent chaining.
     */
    public function testAppendLabelReturnsSelf(): void
    {
        $holder = new ConcreteCheckboxRadioHolder();

        $this->assertSame($holder, $holder->appendLabel());
    }
}
