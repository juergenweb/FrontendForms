<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputRadioMultiple;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for InputRadioMultiple.
 *
 * setOptionsFromField() and applyRadioMarkupFormatting()'s framework-specific
 * branches are NOT covered here - the former needs live ProcessWire field
 * data, the latter depends on the live test environment's configured CSS
 * framework (same reasoning as SelectTest/InputCheckboxMultipleTest).
 */
final class InputRadioMultipleTest extends TestCase
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
     * 1) The element's "type" attribute is "radio".
     */
    public function testConstructorSetsRadioType(): void
    {
        $field = new InputRadioMultiple('mygroup');

        $this->assertSame('radio', $field->getAttribute('type'));
    }

    // --- getOptions() / addOption() ---

    /**
     * 2) A freshly created field has no radio options.
     */
    public function testGetOptionsIsEmptyByDefault(): void
    {
        $field = new InputRadioMultiple('mygroup');

        $this->assertSame([], $field->getOptions());
    }

    /**
     * 3) addOption() appends a new InputRadio to getOptions() (now callable
     * from outside the class since getOptions() was widened to public),
     * sharing the SAME "name" as the group (no brackets - unlike
     * InputCheckboxMultiple - since radio grouping relies on all options
     * having an identical "name").
     */
    public function testAddOptionAppearsInGetOptionsWithSharedGroupName(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $radio = $field->addOption('Red', 'red');

        $this->assertCount(1, $field->getOptions());
        $this->assertSame('mygroup', $radio->getAttribute('name'));
        $this->assertSame('red', $radio->getAttribute('value'));
    }

    /**
     * 4) Multiple calls to addOption() accumulate.
     */
    public function testAddOptionAccumulatesMultipleRadios(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $this->assertCount(2, $field->getOptions());
    }

    // --- alignVertical() ---

    /**
     * 5) alignVertical() switches the internal orientation flag from its
     * horizontal default to vertical.
     */
    public function testAlignVerticalChangesOrientationFlag(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $prop = new ReflectionProperty($field, 'directionHorizontal');
        $prop->setAccessible(true);
        $this->assertTrue($prop->getValue($field));

        $field->alignVertical();

        $this->assertFalse($prop->getValue($field));
    }

    // --- ___renderInputRadioMultiple() ---

    /**
     * 6) With no options at all, rendering returns an empty string.
     */
    public function testRenderReturnsEmptyStringWithNoOptions(): void
    {
        $field = new InputRadioMultiple('mygroup');

        $this->assertSame('', $field->renderInputRadioMultiple());
    }

    /**
     * 7) Each rendered radio gets a unique id built from the group's name
     * and its position in the list.
     */
    public function testRenderAssignsUniqueIdPerRadio(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputRadioMultiple();

        $this->assertStringContainsString('id="mygroup-0"', $out);
        $this->assertStringContainsString('id="mygroup-1"', $out);
    }

    /**
     * 8) The radio whose value matches the submitted POST value is checked.
     */
    public function testRenderChecksRadioMatchingPostValue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['mygroup'] = 'blue';

        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputRadioMultiple();

        $this->assertMatchesRegularExpression('/value="blue"[^>]*checked/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="red"[^>]*checked/', $out);
    }

    /**
     * 9) REGRESSION-STYLE TEST for the "only one radio may be checked"
     * safety net: if the default-values array happens to contain BOTH
     * radios' values (each independently matches the in_array() check),
     * only the FIRST one ends up checked in the rendered output - the
     * duplicate-removal logic strips "checked" from every subsequent match.
     */
    public function testOnlyFirstMatchingRadioStaysCheckedWhenMultipleWouldMatch(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');
        $field->setDefaultValue(['red', 'blue']);

        $out = $field->renderInputRadioMultiple();

        $this->assertMatchesRegularExpression('/value="red"[^>]*checked/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="blue"[^>]*checked/', $out);
        // exactly one "checked" in the whole output, not two
        $this->assertSame(1, substr_count($out, 'checked'));
    }

    /**
     * 10) When the group itself is marked "required", that requirement is
     * propagated to every individual radio.
     */
    public function testRenderPropagatesRequiredToEachRadio(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->setAttribute('required');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputRadioMultiple();

        $this->assertSame(2, substr_count($out, 'required'));
    }

    /**
     * 11) With no matching post or default value, no radio is checked.
     */
    public function testRenderChecksNothingWithoutMatch(): void
    {
        $field = new InputRadioMultiple('mygroup');
        $field->addOption('Red', 'red');
        $field->addOption('Blue', 'blue');

        $out = $field->renderInputRadioMultiple();

        $this->assertStringNotContainsString('checked', $out);
    }
}
