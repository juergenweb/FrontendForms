<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckboxMultiple;
use FrontendForms\Wrapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TraitCheckboxesAndRadiosMultiple.
 *
 * Tested through InputCheckboxMultiple (a real, concrete user of the
 * trait) rather than a minimal Tag-based double, since
 * setCheckBoxRadioAlignmentClass() requires an
 * InputCheckboxMultiple|InputRadioMultiple parameter type by its own
 * signature - a generic double wouldn't satisfy that constraint.
 *
 * setCheckBoxRadioAlignmentClass() itself is protected and its
 * framework-dependent CSS class ("horizontalWrapperClass"/
 * "verticalWrapperClass" resolve to live config values), so it's already
 * covered indirectly through InputCheckboxMultipleTest/
 * InputRadioMultipleTest's full render() tests - this file focuses on
 * getMultipleWrapper() itself.
 */
final class TraitCheckboxesAndRadiosMultipleTest extends TestCase
{
    /**
     * 1) getMultipleWrapper() returns a properly initialized Wrapper
     * instance right after construction (confirms the constructor's own
     * "new Wrapper()" assignment works, unlike the removed
     * $topLabelWrapper, which was never initialized at all).
     */
    public function testGetMultipleWrapperReturnsInitializedWrapper(): void
    {
        $field = new InputCheckboxMultiple('mygroup');

        $this->assertInstanceOf(Wrapper::class, $field->getMultipleWrapper());
    }

    /**
     * 2) The same Wrapper instance is returned on repeated calls - it's
     * not recreated each time.
     */
    public function testGetMultipleWrapperReturnsSameInstanceEachCall(): void
    {
        $field = new InputCheckboxMultiple('mygroup');

        $this->assertSame($field->getMultipleWrapper(), $field->getMultipleWrapper());
    }
}
