<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Notes;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Notes.
 *
 * Everything inherited from TextElements is already covered by
 * TextElementsTest - this file only tests what Notes itself adds: the
 * CSS class on construction, and confirms TraitTags integration is safe
 * from the customTag-poisoning bug fixed in Label earlier in this session
 * (Notes's constructor never calls setTag() itself).
 */
final class NotesTest extends TestCase
{
    /**
     * 1) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $notes = new Notes();

        $this->assertNotEmpty($notes->getAttribute('class'));
    }

    /**
     * 2) A freshly created Notes has no custom tag recorded, since the
     * constructor never calls setTag() itself.
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $notes = new Notes();

        $this->assertNull($notes->getCustomTag());
    }
}
