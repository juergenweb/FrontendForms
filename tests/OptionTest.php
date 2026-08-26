<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Option;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Option.
 */
final class OptionTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag defaults to "option".
     */
    public function testConstructorSetsOptionTag(): void
    {
        $option = new Option();

        $this->assertSame('option', $option->getTag());
    }

    // --- ___render() ---

    /**
     * 2) An option with no content and no attributes renders as an empty
     * string - renderNonSelfclosingTag() is called with only the tag name,
     * so showNoContent defaults to false, and empty content is suppressed
     * entirely rather than shown as an empty tag pair.
     */
    public function testRenderWithNoContentReturnsEmptyString(): void
    {
        $option = new Option();

        $this->assertSame('', $option->render());
    }

    /**
     * 3) Content set via setContent() (as TraitOption::addOption() does,
     * using the option's label as content) is rendered inside the tag.
     */
    public function testRenderIncludesContent(): void
    {
        $option = new Option();
        $option->setContent('Blue');

        $this->assertSame('<option>Blue</option>', $option->render());
    }

    /**
     * 4) The "value" attribute (as TraitOption::addOption() sets it) is
     * included in the rendered tag.
     */
    public function testRenderIncludesValueAttribute(): void
    {
        $option = new Option();
        $option->setContent('Blue');
        $option->setAttribute('value', 'blue');

        $this->assertSame('<option value="blue">Blue</option>', $option->render());
    }

    /**
     * 5) A "selected" attribute (used by Select/Datalist to mark the
     * default choice) renders as a bare boolean attribute.
     */
    public function testRenderIncludesSelectedAsBooleanAttribute(): void
    {
        $option = new Option();
        $option->setContent('Blue');
        $option->setAttribute('value', 'blue');
        $option->setAttribute('selected');

        $out = $option->render();

        $this->assertStringContainsString('selected', $out);
        $this->assertStringNotContainsString('selected=', $out);
    }
}
