<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Element;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * A minimal concrete Element subclass for testing. Element is declared
 * abstract only to prevent direct instantiation - it has no actual abstract
 * methods, so an empty subclass is enough to exercise its own logic in
 * isolation. Crucially, this class does NOT extend Inputfields, so it
 * exercises the "non-Inputfields" branch of applyConditionalRules()/
 * removeConditions() - exactly the code path that used to crash before the
 * removeConditions() bugfix (see class docblock on ElementTest below).
 */
final class ConcreteElement extends Element
{
}

/**
 * Unit tests for Element.
 *
 * Uses real objects (a minimal ConcreteElement subclass, and a real
 * InputText for the Inputfields-specific branch) rather than mocks,
 * consistent with the ProcessWire-core-mocking lessons learned earlier in
 * this session (see CaptchaQuestionRepositoryTest, DefaultInputRendererTest,
 * TagTest).
 */
final class ElementTest extends TestCase
{
    // --- getConditions() / containsConditions() defaults ---

    /**
     * 1) A freshly created element has no conditions.
     */
    public function testHasNoConditionsByDefault(): void
    {
        $element = new ConcreteElement('test');

        $this->assertNull($element->getConditions());
        $this->assertFalse($element->containsConditions());
    }

    // --- hideIf() / showIf() / disableIf() / enableIf() ---

    /**
     * 2) hideIf() stores the "hide" action, the given rules and logic, and
     * marks the element as containing conditions.
     */
    public function testHideIfStoresHideAction(): void
    {
        $element = new ConcreteElement('test');
        $element->hideIf(['field' => 'value'], 'and');

        $this->assertTrue($element->containsConditions());
        $this->assertSame('hide', $element->getConditions()['action']);
        $this->assertSame('and', $element->getConditions()['logic']);
        $this->assertSame(['field' => 'value'], $element->getConditions()['rules']);
    }

    /**
     * 3) showIf()/disableIf()/enableIf() each store their own matching
     * action name.
     */
    public function testShowDisableEnableIfStoreMatchingActions(): void
    {
        $show = new ConcreteElement('a');
        $show->showIf(['x' => 1]);
        $this->assertSame('show', $show->getConditions()['action']);

        $disable = new ConcreteElement('b');
        $disable->disableIf(['x' => 1]);
        $this->assertSame('disable', $disable->getConditions()['action']);

        $enable = new ConcreteElement('c');
        $enable->enableIf(['x' => 1]);
        $this->assertSame('enable', $enable->getConditions()['action']);
    }

    /**
     * 4) enableIf() additionally sets the "disabled" attribute on the
     * element itself (presumably re-enabled client-side once the condition
     * is met).
     */
    public function testEnableIfAlsoSetsDisabledAttribute(): void
    {
        $element = new ConcreteElement('test');
        $element->enableIf(['x' => 1]);

        $this->assertSame('disabled', $element->getAttribute('disabled'));
    }

    /**
     * 5) The default container ".fieldwrapper" results in a "class"
     * attribute (without the leading dot) on the wrap() object for
     * non-Inputfields elements.
     */
    public function testDefaultContainerSetsClassAttributeOnWrapper(): void
    {
        $element = new ConcreteElement('test');
        $element->showIf(['x' => 1]);

        $this->assertSame(['fieldwrapper'], array_values($element->getWrap()->getAttribute('class')));
    }

    /**
     * 6) A container starting with "#" results in an "id" attribute
     * instead of "class".
     */
    public function testHashPrefixedContainerSetsIdAttribute(): void
    {
        $element = new ConcreteElement('test');
        $element->showIf(['x' => 1], 'or', '#my-container');

        $this->assertSame('my-container', $element->getWrap()->getAttribute('id'));
        $this->assertNull($element->getWrap()->getAttribute('class'));
    }

    // --- removeConditions() ---

    /**
     * 7) REGRESSION TEST for the removeConditions() bug: calling it on a
     * non-Inputfields element (which uses the wrap()-based container, not
     * getFieldWrapper()) must NOT throw - before the fix, this crashed with
     * "Call to undefined method" because removeConditions() unconditionally
     * called getFieldWrapper(), a method that only exists on Inputfields.
     */
    public function testRemoveConditionsDoesNotCrashOnNonInputfieldsElement(): void
    {
        $element = new ConcreteElement('test');
        $element->showIf(['field' => 'value']);

        $element->removeConditions();

        $this->assertNull($element->getConditions());
        $this->assertFalse($element->containsConditions());
    }

    /**
     * 8) removeConditions() also works correctly on an Inputfields element
     * (the branch that already worked before the fix), confirming the fix
     * didn't break the original, working path.
     */
    public function testRemoveConditionsWorksOnInputfieldsElement(): void
    {
        $field = new InputText('myfield');
        $field->showIf(['other' => 'value']);

        $field->removeConditions();

        $this->assertNull($field->getConditions());
        $this->assertFalse($field->containsConditions());
    }

    /**
     * 9) removeConditions() on an element that never had any conditions set
     * in the first place is a harmless no-op (no wrap() object exists yet,
     * so the null-check branch must be taken).
     */
    public function testRemoveConditionsOnElementWithoutPriorConditionsIsNoOp(): void
    {
        $element = new ConcreteElement('test');

        $element->removeConditions();

        $this->assertNull($element->getConditions());
        $this->assertFalse($element->containsConditions());
    }

    // --- setConditionContainerClass() / getConditionContainerClass() ---

    /**
     * 10) A class name without a leading dot gets one added automatically.
     */
    public function testSetConditionContainerClassAddsLeadingDot(): void
    {
        $element = new ConcreteElement('test');
        $element->setConditionContainerClass('my-class');

        $this->assertSame('.my-class', $element->getConditionContainerClass());
    }

    /**
     * 11) A class name that already has a leading dot is left unchanged.
     */
    public function testSetConditionContainerClassKeepsExistingDot(): void
    {
        $element = new ConcreteElement('test');
        $element->setConditionContainerClass('.my-class');

        $this->assertSame('.my-class', $element->getConditionContainerClass());
    }

    /**
     * 12) With nothing set, the condition container class is null.
     */
    public function testConditionContainerClassIsNullByDefault(): void
    {
        $this->assertNull((new ConcreteElement('test'))->getConditionContainerClass());
    }

    // --- wrap() / removeWrap() / getWrap() ---

    /**
     * 13) wrap() creates and returns a Wrapper object, retrievable
     * afterward via getWrap().
     */
    public function testWrapCreatesAndReturnsWrapper(): void
    {
        $element = new ConcreteElement('test');
        $wrapper = $element->wrap();

        $this->assertSame($wrapper, $element->getWrap());
    }

    /**
     * 14) Before wrap() is called, getWrap() returns null.
     */
    public function testGetWrapReturnsNullBeforeWrapIsCalled(): void
    {
        $this->assertNull((new ConcreteElement('test'))->getWrap());
    }

    /**
     * 15) removeWrap() clears a previously created wrapper.
     */
    public function testRemoveWrapClearsWrapper(): void
    {
        $element = new ConcreteElement('test');
        $element->wrap();
        $element->removeWrap();

        $this->assertNull($element->getWrap());
    }

    /**
     * 16) REGRESSION TEST: removeWrap() must set the wrapper property back
     * to null (a normal, readable state) rather than unset() it. unset() on
     * a typed property (even a nullable one) puts it into an "uninitialized"
     * state, and reading it afterward - e.g. via getWrap(), or the
     * removeConditions() fix from earlier, which calls
     * "$this->getWrap() !== null" - throws an Error ("must not be accessed
     * before initialization") instead of yielding null. This test calls
     * getWrap() a second time after removeWrap() to make sure no such error
     * occurs.
     */
    public function testGetWrapAfterRemoveWrapDoesNotThrow(): void
    {
        $element = new ConcreteElement('test');
        $element->wrap();
        $element->removeWrap();

        $this->assertNull($element->getWrap());
        $this->assertNull($element->getWrap());
    }

    /**
     * 17) REGRESSION TEST: removeConditions() after removeWrap() must not
     * throw either - this combines both fixes (removeWrap() setting null
     * instead of unset(), and removeConditions() null-checking getWrap()
     * for non-Inputfields elements).
     */
    public function testRemoveConditionsAfterRemoveWrapDoesNotThrow(): void
    {
        $element = new ConcreteElement('test');
        $element->showIf(['x' => 1]);
        $element->removeWrap();

        $element->removeConditions();

        $this->assertNull($element->getConditions());
    }
}
