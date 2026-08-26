<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckboxMultiple;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for InputCheckboxMultiple.
 *
 * setOptionsFromField() is NOT covered here - it loads options from a live
 * ProcessWire "SelectOptions" field, which needs real field/database setup
 * and is better suited to an integration test (same reasoning as
 * SelectTest/DatalistTest).
 *
 * applyMarkupFormatting()'s framework-specific branches are not asserted
 * against exact markup, since which branch runs depends on the live test
 * environment's configured CSS framework (see the Bootstrap5InputRendererTest
 * lesson from earlier in this session).
 */
final class InputCheckboxMultipleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    // --- construction ---

    /**
     * 1) The element's "type" attribute is "checkbox".
     */
    public function testConstructorSetsCheckboxType(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');

        $this->assertSame('checkbox', $field->getAttribute('type'));
    }

    /**
     * 2) The default "text" sanitizer is removed and replaced with
     * "arrayVal", since multiple checkboxes submit an array of values.
     */
    public function testConstructorUsesArrayValSanitizer(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');

        $this->assertFalse($field->hasSanitizer('text'));
        $this->assertTrue($field->hasSanitizer('arrayVal'));
    }

    // --- getOptions() / addOption() ---

    /**
     * 3) A freshly created field has no checkbox options.
     */
    public function testGetOptionsIsEmptyByDefault(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');

        $this->assertSame([], $field->getOptions());
    }

    /**
     * 4) addOption() appends a new InputCheckbox to getOptions() (now
     * callable from outside the class since getOptions() was widened to
     * public), with its "name" attribute set to the parent field's name
     * plus "[]".
     */
    public function testAddOptionAppendsToGetOptionsWithBracketedName(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $checkbox = $field->addOption('Red', 'red');

        $this->assertCount(1, $field->getOptions());
        $this->assertSame('mycheckboxes[]', $checkbox->getAttribute('name'));
        $this->assertSame('red', $checkbox->getAttribute('value'));
    }

    /**
     * 5) An added option has its own field/input wrappers disabled and its
     * required-asterisk disabled, since InputCheckboxMultiple handles its
     * own layout around the individual checkboxes.
     */
    public function testAddOptionDisablesWrappersAndAsterisk(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $checkbox = $field->addOption('Red', 'red');

        $this->assertFalse($checkbox->getUsageOfInputWrapper());
        $this->assertFalse($checkbox->getUsageOfFieldWrapper());
    }

    /**
     * 6) Multiple calls to addOption() accumulate, they don't overwrite
     * each other.
     */
    public function testAddOptionAccumulatesMultipleCheckboxes(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $this->assertCount(2, $field->getOptions());
    }

    // --- alignVertical() ---

    /**
     * 7) alignVertical() switches the internal orientation flag from its
     * horizontal default to vertical.
     */
    public function testAlignVerticalChangesOrientationFlag(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $prop = new ReflectionProperty($field, 'directionHorizontal');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($field));

        $field->alignVertical();

        $this->assertFalse($prop->getValue($field));
    }

    // --- ___renderInputCheckboxMultiple() ---

    /**
     * 8) With no options at all, rendering returns an empty string.
     */
    public function testRenderReturnsEmptyStringWithNoOptions(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');

        $this->assertSame('', $field->renderInputCheckboxMultiple());
    }

    /**
     * 9) Each rendered checkbox gets a unique id built from the parent
     * field's name and its position in the list.
     */
    public function testRenderAssignsUniqueIdPerCheckbox(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputCheckboxMultiple();

        $this->assertStringContainsString('id="mycheckboxes-0"', $out);
        $this->assertStringContainsString('id="mycheckboxes-1"', $out);
    }

    /**
     * 10) A checkbox whose value matches a submitted POST value is rendered
     * checked; others are not.
     */
    public function testRenderMarksMatchingPostValuesAsChecked(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['mycheckboxes'] = ['blue'];

        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputCheckboxMultiple();

        $this->assertMatchesRegularExpression('/value="blue"[^>]*checked/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="red"[^>]*checked/', $out);
    }

    /**
     * 11) A checkbox whose value matches a configured default value (form
     * not submitted) is rendered checked.
     */
    public function testRenderMarksMatchingDefaultValuesAsChecked(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');
        $field->setDefaultValue(['red']);

        $out = $field->renderInputCheckboxMultiple();

        $this->assertMatchesRegularExpression('/value="red"[^>]*checked/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="blue"[^>]*checked/', $out);
    }

    /**
     * 12) When the field itself is marked "required", that requirement is
     * propagated to every individual checkbox, along with a
     * "data-multicheckbox" attribute pointing back at the group's name.
     */
    public function testRenderPropagatesRequiredToEachCheckbox(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->setAttribute('required');
        $field->addOption('Red', 'red');

        $out = $field->renderInputCheckboxMultiple();

        $this->assertStringContainsString('required', $out);
        $this->assertStringContainsString('data-multicheckbox="mycheckboxes"', $out);
    }

    /**
     * 13) When the field is not required, no per-checkbox required/grouping
     * attributes are added.
     */
    public function testRenderDoesNotPropagateRequiredWhenNotSet(): void
    {
        $field = new InputCheckboxMultiple('mycheckboxes');
        $field->addOption('Red', 'red');

        $out = $field->renderInputCheckboxMultiple();

        $this->assertStringNotContainsString('data-multicheckbox', $out);
    }
}
