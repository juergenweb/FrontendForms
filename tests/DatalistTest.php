<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Datalist;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Datalist.
 *
 * setOptionsFromField() is NOT covered here - it loads options from a live
 * ProcessWire "SelectOptions" field, which needs real field/database setup
 * and is better suited to an integration test (same reasoning as
 * SelectTest).
 */
final class DatalistTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The "list" attribute is set to "datalist-{id}", linking the input
     * to its <datalist> element.
     */
    public function testConstructorSetsListAttribute(): void
    {
        $datalist = new Datalist('mydatalist');

        $this->assertSame('datalist-mydatalist', $datalist->getAttribute('list'));
    }

    // --- ___renderDatalist() ---

    /**
     * 2) With no options at all, rendering returns an empty string.
     */
    public function testRenderReturnsEmptyStringWithNoOptions(): void
    {
        $datalist = new Datalist('mydatalist');

        $this->assertSame('', $datalist->renderDatalist());
    }

    /**
     * 3) With options present, the rendered output contains a <datalist>
     * element (matching the "list" attribute's id) with each option inside.
     */
    public function testRenderIncludesDatalistElementWithOptions(): void
    {
        $datalist = new Datalist('mydatalist');
        $datalist->addOption('Berlin', 'berlin');
        $datalist->addOption('Vienna', 'vienna');

        $out = $datalist->renderDatalist();

        $this->assertStringContainsString('<datalist id="datalist-mydatalist">', $out);
        $this->assertStringContainsString('value="berlin"', $out);
        $this->assertStringContainsString('value="vienna"', $out);
    }

    /**
     * 4) An option marked "selected" has its value copied to the main
     * input's "value" attribute, and the (in a <datalist>, meaningless)
     * "selected" attribute is stripped from the rendered option.
     */
    public function testSelectedOptionValueIsCopiedToInputAndSelectedIsStripped(): void
    {
        $datalist = new Datalist('mydatalist');
        $option = $datalist->addOption('Berlin', 'berlin');
        $option->setAttribute('selected');

        $out = $datalist->renderDatalist();

        $this->assertSame('berlin', $datalist->getAttribute('value'));
        $this->assertStringNotContainsString('selected', $out);
    }

    /**
     * 5) If the input already has a "value" attribute set beforehand, a
     * "selected" option does NOT overwrite it.
     */
    public function testExistingValueIsNotOverwrittenBySelectedOption(): void
    {
        $datalist = new Datalist('mydatalist');
        $datalist->setAttribute('value', 'pre-existing');
        $option = $datalist->addOption('Berlin', 'berlin');
        $option->setAttribute('selected');

        $datalist->renderDatalist();

        $this->assertSame('pre-existing', $datalist->getAttribute('value'));
    }

    /**
     * 6) REGRESSION TEST: when multiple options are marked "selected", only
     * the FIRST one's value is copied to the input (since the value is only
     * set once), but "selected" is stripped from ALL of them - not just the
     * first. Before the fix, only the first option's "selected" was
     * removed, because both actions shared the same
     * "!$this->hasAttribute('value')" guard.
     */
    public function testSelectedIsStrippedFromAllMatchingOptionsNotJustTheFirst(): void
    {
        $datalist = new Datalist('mydatalist');
        $first = $datalist->addOption('Berlin', 'berlin');
        $first->setAttribute('selected');
        $second = $datalist->addOption('Vienna', 'vienna');
        $second->setAttribute('selected');

        $out = $datalist->renderDatalist();

        // only the first selected option's value wins for the input itself
        $this->assertSame('berlin', $datalist->getAttribute('value'));
        // but neither option should still carry "selected" in the markup
        $this->assertStringNotContainsString('selected', $out);
    }

    /**
     * 7) An option without "selected" does not affect the input's "value"
     * attribute at all.
     */
    public function testUnselectedOptionsDoNotSetValue(): void
    {
        $datalist = new Datalist('mydatalist');
        $datalist->addOption('Berlin', 'berlin');

        $datalist->renderDatalist();

        $this->assertNull($datalist->getAttribute('value'));
    }
}
