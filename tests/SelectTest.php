<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Select;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Select.
 *
 * setOptionsFromField() is NOT covered here - it loads options from a live
 * ProcessWire "SelectOptions" field, which needs real field/database setup
 * and is better suited to an integration test.
 *
 * getSelectWrapper()/the Bulma-specific wrapping in ___renderSelect() depend
 * on which CSS framework is actually configured in the live test
 * environment (see the Bootstrap5InputRendererTest lesson from earlier in
 * this session) - the relevant test below asserts internal consistency
 * (does the wrapper only get used when it exists) rather than a fixed
 * framework-dependent expectation.
 */
final class SelectTest extends TestCase
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
     * 1) The element's tag is "select".
     */
    public function testConstructorSetsSelectTag(): void
    {
        $select = new Select('myselect');

        $this->assertSame('select', $select->getTag());
    }

    // --- getOptions() / addOption() ---

    /**
     * 2) A freshly created select has no options.
     */
    public function testGetOptionsIsEmptyByDefault(): void
    {
        $select = new Select('myselect');

        $this->assertSame([], $select->getOptions());
    }

    /**
     * 3) addOption() appends a new Option to the list returned by
     * getOptions(), now callable from outside the class since getOptions()
     * was widened to public.
     */
    public function testAddOptionAppearsInGetOptions(): void
    {
        $select = new Select('myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');

        $this->assertCount(2, $select->getOptions());
    }

    // --- getSelectWrapper() ---

    /**
     * 4) getSelectWrapper() never throws and is consistent with itself
     * (calling it twice returns the same value/instance) - it's either
     * null (most frameworks) or a Wrapper object (Bulma 1 only), depending
     * on the live test environment's configured framework.
     */
    public function testGetSelectWrapperIsConsistentAcrossCalls(): void
    {
        $select = new Select('myselect');

        $this->assertSame($select->getSelectWrapper(), $select->getSelectWrapper());
    }

    // --- ___renderSelect() ---

    /**
     * 5) With no options at all, rendering returns an empty string.
     */
    public function testRenderSelectReturnsEmptyStringWithNoOptions(): void
    {
        $select = new Select('myselect');

        $this->assertSame('', $select->renderSelect());
    }

    /**
     * 6) With options present, the rendered output contains each option's
     * label text and value attribute.
     */
    public function testRenderSelectRendersEachOption(): void
    {
        $select = new Select('myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');

        $out = $select->renderSelect();

        $this->assertStringContainsString('Red', $out);
        $this->assertStringContainsString('Blue', $out);
        $this->assertStringContainsString('value="red"', $out);
        $this->assertStringContainsString('value="blue"', $out);
    }

    /**
     * 7) The option whose value matches the field's POST value is marked
     * "selected".
     */
    public function testRenderSelectMarksPostValueAsSelected(): void
    {
        $select = new Select('myselect');
        $select->setAttribute('name', 'myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['myselect'] = 'blue';

        $out = $select->renderSelect();

        // the "blue" option (and only that one) should carry "selected"
        $this->assertMatchesRegularExpression('/value="blue"[^>]*selected/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="red"[^>]*selected/', $out);
    }

    /**
     * 8) The option whose value matches the field's default value is marked
     * "selected" when the form was not submitted.
     */
    public function testRenderSelectMarksDefaultValueAsSelected(): void
    {
        $select = new Select('myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');
        $select->setDefaultValue('red');

        $out = $select->renderSelect();

        $this->assertMatchesRegularExpression('/value="red"[^>]*selected/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="blue"[^>]*selected/', $out);
    }

    /**
     * 9) With no matching post/default value, no option is marked selected.
     */
    public function testRenderSelectMarksNothingSelectedByDefault(): void
    {
        $select = new Select('myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');

        $out = $select->renderSelect();

        $this->assertStringNotContainsString('selected', $out);
    }

    /**
     * 9b) REGRESSION TEST for the fixed bug: after the form has been
     * submitted with a value posted for this select that does not match
     * the configured default (e.g. the visitor picked a different, valid
     * option, or the blank "please choose" option), the default must NOT
     * be merged back in and marked selected too. Only the actual posted
     * value should determine what's selected once the form has been
     * submitted.
     */
    public function testRenderSelectDoesNotFallBackToDefaultWhenSubmittedWithDifferentValue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['myselect'] = 'blue';

        $select = new Select('myselect');
        $select->setAttribute('name', 'myselect');
        $select->addOption('Red', 'red');
        $select->addOption('Blue', 'blue');
        $select->setDefaultValue('red');

        $out = $select->renderSelect();

        $this->assertMatchesRegularExpression('/value="blue"[^>]*selected/', $out);
        $this->assertDoesNotMatchRegularExpression('/value="red"[^>]*selected/', $out);
    }

    /**
     * 10) A TextElements-based option with the "hr" tag (a separator) is
     * rendered as a self-closing <hr> tag, not as a regular <option>.
     */
    public function testRenderSelectRendersHrSeparator(): void
    {
        $select = new Select('myselect');
        $select->addOption('Red', 'red');
        $select->addHorizontalRule();

        $out = $select->renderSelect();

        $this->assertStringContainsString('<hr>', $out);
    }
}
