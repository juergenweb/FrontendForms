<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Description;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Description.
 *
 * Everything inherited from TextElements (setText()/getText(), render(),
 * wrap() handling, __toString()) is already covered by TextElementsTest -
 * this file focuses on what Description itself adds: the position
 * property/setter/getter, and TraitTags integration (confirmed safe from
 * the customTag-poisoning bug fixed in Label earlier in this session,
 * since Description's constructor never calls setTag() itself).
 */
final class DescriptionTest extends TestCase
{
    // --- construction ---

    /**
     * 1) A freshly created Description has no custom tag recorded - unlike
     * the pre-fix Label bug, Description's constructor never calls
     * setTag() itself, so there's nothing to poison getCustomTag() with.
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $description = new Description();

        $this->assertNull($description->getCustomTag());
    }

    // --- setPosition() / getPosition() ---

    /**
     * 2) A freshly created Description has no position set.
     */
    public function testPositionIsNullByDefault(): void
    {
        $description = new Description();

        $this->assertNull($description->getPosition());
    }

    /**
     * 3) setPosition() accepts each of the three valid position values.
     */
    public function testSetPositionAcceptsValidValues(): void
    {
        foreach (['beforeLabel', 'afterLabel', 'afterInput'] as $position) {
            $description = new Description();
            $description->setPosition($position);

            $this->assertSame($position, $description->getPosition());
        }
    }

    /**
     * 4) An invalid position value is silently ignored - the position
     * stays unchanged rather than being set to the invalid value.
     */
    public function testSetPositionIgnoresInvalidValue(): void
    {
        $description = new Description();

        $description->setPosition('not-a-real-position');

        $this->assertNull($description->getPosition());
    }

    /**
     * 5) A valid position adds a matching CSS class (e.g. "afterLabel"
     * becomes "afterLabel-desc").
     */
    public function testSetPositionAddsMatchingCssClass(): void
    {
        $description = new Description();

        $description->setPosition('afterLabel');

        $this->assertContains('afterLabel-desc', $description->getAttribute('class'));
    }

    /**
     * 6) setPosition() returns $this, supporting fluent chaining.
     */
    public function testSetPositionReturnsSelf(): void
    {
        $description = new Description();

        $this->assertSame($description, $description->setPosition('afterLabel'));
    }
}
