<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckboxMultiple;
use FrontendForms\InputRadioMultiple;
use FrontendForms\Select;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TraitOptionElements.
 *
 * Tested through the real concrete classes that use it (Select,
 * InputRadioMultiple, InputCheckboxMultiple) rather than a minimal double,
 * since getOptionsPropertyName() depends on the real class name
 * (className()) and each class's own differently-named options-holding
 * property ($options vs $radios vs $checkboxes) - exactly the mechanism
 * the fixed bug was about.
 */
final class TraitOptionElementsTest extends TestCase
{
    // --- getOptionByValue() / removeOptionByValue() on Select ($options) ---

    /**
     * 1) On a Select (whose options live in $options, the "default" case
     * in getOptionsPropertyName()), getOptionByValue() finds an option by
     * its value.
     */
    public function testGetOptionByValueFindsOptionOnSelect(): void
    {
        $select = new Select('mycolor');
        $blue = $select->addOption('Blue', 'blue');
        $select->addOption('Red', 'red');

        $this->assertSame($blue, $select->getOptionByValue('blue'));
    }

    /**
     * 2) getOptionByValue() returns null when no option matches.
     */
    public function testGetOptionByValueReturnsNullWhenNotFound(): void
    {
        $select = new Select('mycolor');
        $select->addOption('Blue', 'blue');

        $this->assertNull($select->getOptionByValue('green'));
    }

    /**
     * 3) removeOptionByValue() removes the matching option from a Select.
     */
    public function testRemoveOptionByValueRemovesFromSelect(): void
    {
        $select = new Select('mycolor');
        $select->addOption('Blue', 'blue');
        $select->addOption('Red', 'red');

        $select->removeOptionByValue('blue');

        $this->assertNull($select->getOptionByValue('blue'));
        $this->assertNotNull($select->getOptionByValue('red'));
    }

    // --- REGRESSION TESTS: InputRadioMultiple ($radios, not $options) ---

    /**
     * 4) REGRESSION TEST for the className()-as-property bug: on an
     * InputRadioMultiple, whose options live in $radios (not $options),
     * getOptionByValue() must still find a matching option. Before the
     * fix, getOptionsPropertyName() always resolved to 'options' (since
     * $this->className, a bare property access, silently evaluated to
     * something other than the expected class name strings) - and since
     * InputRadioMultiple has no $options property at all, this always
     * returned null instead of the real match.
     */
    public function testGetOptionByValueFindsOptionOnInputRadioMultiple(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $red = $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $this->assertSame($red, $field->getOptionByValue('red'));
    }

    /**
     * 5) Same regression, for removeOptionByValue() on
     * InputRadioMultiple.
     */
    public function testRemoveOptionByValueRemovesFromInputRadioMultiple(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $field->removeOptionByValue('red');

        $this->assertNull($field->getOptionByValue('red'));
        $this->assertNotNull($field->getOptionByValue('blue'));
        $this->assertCount(1, $field->getOptions());
    }

    // --- REGRESSION TESTS: InputCheckboxMultiple ($checkboxes, not $options) ---

    /**
     * 6) Same regression, for getOptionByValue() on InputCheckboxMultiple
     * (options live in $checkboxes).
     */
    public function testGetOptionByValueFindsOptionOnInputCheckboxMultiple(): void
    {
        $field = new InputCheckboxMultiple('mygroup');
        $red = $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $this->assertSame($red, $field->getOptionByValue('red'));
    }

    /**
     * 7) Same regression, for removeOptionByValue() on
     * InputCheckboxMultiple.
     */
    public function testRemoveOptionByValueRemovesFromInputCheckboxMultiple(): void
    {
        $field = new InputCheckboxMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $field->removeOptionByValue('red');

        $this->assertNull($field->getOptionByValue('red'));
        $this->assertNotNull($field->getOptionByValue('blue'));
        $this->assertCount(1, $field->getOptions());
    }

    /**
     * 8) getOptionByValue() uses loose comparison (value == attribute), so
     * an integer value matches a numeric-string option value.
     */
    public function testGetOptionByValueMatchesIntegerAgainstStringValue(): void
    {
        $select = new Select('myfield');
        $option = $select->addOption('Ten', '10');

        $this->assertSame($option, $select->getOptionByValue(10));
    }
}
