<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Tag;
use FrontendForms\TraitOption;
use PHPUnit\Framework\TestCase;

/**
 * A minimal Tag subclass using TraitOption, for testing the trait in
 * isolation from Select/Datalist (which layer their own rendering logic on
 * top).
 */
final class ConcreteOptionHolder extends Tag
{
    use TraitOption;

    public function exposeOptions(): array
    {
        return $this->options;
    }
}

/**
 * Unit tests for TraitOption.
 */
final class TraitOptionTest extends TestCase
{
    // --- default state ---

    /**
     * 1) A freshly created element has no options.
     */
    public function testOptionsIsEmptyByDefault(): void
    {
        $holder = new ConcreteOptionHolder();

        $this->assertSame([], $holder->exposeOptions());
    }

    // --- addOption() ---

    /**
     * 2) addOption() creates an Option with the given label as its content
     * and the given value as its "value" attribute.
     */
    public function testAddOptionSetsLabelAndValue(): void
    {
        $holder = new ConcreteOptionHolder();

        $option = $holder->addOption('Red', 'red');

        $this->assertSame('Red', $option->getContent());
        $this->assertSame('red', $option->getAttribute('value'));
    }

    /**
     * 3) The created option is appended to the options list.
     */
    public function testAddOptionAppendsToOptionsList(): void
    {
        $holder = new ConcreteOptionHolder();

        $option = $holder->addOption('Red', 'red');

        $this->assertSame([$option], $holder->exposeOptions());
    }

    /**
     * 4) Multiple calls to addOption() accumulate in order, they don't
     * overwrite each other.
     */
    public function testAddOptionAccumulatesInOrder(): void
    {
        $holder = new ConcreteOptionHolder();

        $red = $holder->addOption('Red', 'red');
        $blue = $holder->addOption('Blue', 'blue');

        $this->assertSame([$red, $blue], $holder->exposeOptions());
    }

    // --- addEmptyOption() ---

    /**
     * 5) Without an explicit label, the empty option's content defaults to
     * a single dash.
     */
    public function testAddEmptyOptionDefaultsLabelToDash(): void
    {
        $holder = new ConcreteOptionHolder();

        $option = $holder->addEmptyOption();

        $this->assertSame('-', $option->getContent());
    }

    /**
     * 6) A custom label can be given instead of the default dash.
     */
    public function testAddEmptyOptionWithCustomLabel(): void
    {
        $holder = new ConcreteOptionHolder();

        $option = $holder->addEmptyOption('Please select');

        $this->assertSame('Please select', $option->getContent());
    }

    /**
     * 7) An empty option always has an empty string as its value,
     * regardless of the label.
     */
    public function testAddEmptyOptionHasEmptyValue(): void
    {
        $holder = new ConcreteOptionHolder();

        $option = $holder->addEmptyOption('Please select');

        $this->assertSame('', $option->getAttribute('value'));
    }

    /**
     * 8) Empty options are also appended to the same options list as
     * regular ones.
     */
    public function testAddEmptyOptionAppendsToOptionsList(): void
    {
        $holder = new ConcreteOptionHolder();

        $empty = $holder->addEmptyOption();
        $red = $holder->addOption('Red', 'red');

        $this->assertSame([$empty, $red], $holder->exposeOptions());
    }

    // --- addHorizontalRule() ---

    /**
     * 9) addHorizontalRule() creates an "hr" element and appends it to the
     * options list alongside regular options.
     */
    public function testAddHorizontalRuleAddsHrElementToList(): void
    {
        $holder = new ConcreteOptionHolder();

        $red = $holder->addOption('Red', 'red');
        $hr = $holder->addHorizontalRule();
        $blue = $holder->addOption('Blue', 'blue');

        $this->assertSame('hr', $hr->getTag());
        $this->assertSame([$red, $hr, $blue], $holder->exposeOptions());
    }

    // --- removeAllOptions() ---

    /**
     * 10) removeAllOptions() clears every previously added option
     * (regular, empty, and horizontal rules alike).
     */
    public function testRemoveAllOptionsClearsEverything(): void
    {
        $holder = new ConcreteOptionHolder();
        $holder->addOption('Red', 'red');
        $holder->addEmptyOption();
        $holder->addHorizontalRule();

        $holder->removeAllOptions();

        $this->assertSame([], $holder->exposeOptions());
    }
}
