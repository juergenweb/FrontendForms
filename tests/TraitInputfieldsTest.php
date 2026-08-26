<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Tag;
use FrontendForms\TraitInputfields;
use PHPUnit\Framework\TestCase;

/**
 * A minimal Tag subclass using TraitInputfields, for testing the trait in
 * isolation from Datalist (which layers its own rendering logic on top).
 */
final class ConcreteInputfieldsHolder extends Tag
{
    use TraitInputfields;
}

/**
 * Unit tests for TraitInputfields.
 */
final class TraitInputfieldsTest extends TestCase
{
    // --- removeFromLastStep() / getRemoveFromLastStep() ---

    /**
     * 1) A freshly created element is not removed from the last step by
     * default.
     */
    public function testRemoveFromLastStepIsFalseByDefault(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $this->assertFalse($holder->getRemoveFromLastStep());
    }

    /**
     * 2) Calling removeFromLastStep() enables it.
     */
    public function testRemoveFromLastStepEnablesFlag(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $holder->removeFromLastStep();

        $this->assertTrue($holder->getRemoveFromLastStep());
    }

    /**
     * 3) removeFromLastStep() returns $this, supporting fluent chaining.
     */
    public function testRemoveFromLastStepReturnsSelf(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $this->assertSame($holder, $holder->removeFromLastStep());
    }

    // --- setCustomListLabel() / getCustomListLabel() ---

    /**
     * 4) A freshly created element has no custom list label.
     */
    public function testCustomListLabelIsEmptyByDefault(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $this->assertSame('', $holder->getCustomListLabel());
    }

    /**
     * 5) setCustomListLabel()/getCustomListLabel() round-trip the label.
     */
    public function testSetAndGetCustomListLabel(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $holder->setCustomListLabel('Agreement');

        $this->assertSame('Agreement', $holder->getCustomListLabel());
    }

    /**
     * 6) setCustomListLabel() returns $this, supporting fluent chaining.
     */
    public function testSetCustomListLabelReturnsSelf(): void
    {
        $holder = new ConcreteInputfieldsHolder();

        $this->assertSame($holder, $holder->setCustomListLabel('Agreement'));
    }
}
