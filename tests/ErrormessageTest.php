<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Errormessage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Errormessage.
 *
 * Everything inherited from TextElements is already covered by
 * TextElementsTest - this file only tests what Errormessage itself adds:
 * the CSS class on construction, and confirms TraitTags integration is
 * safe from the customTag-poisoning bug fixed in Label earlier in this
 * session (Errormessage's constructor never calls setTag() itself).
 */
final class ErrormessageTest extends TestCase
{
    /**
     * 1) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $error = new Errormessage();

        $this->assertNotEmpty($error->getAttribute('class'));
    }

    /**
     * 2) A freshly created Errormessage has no custom tag recorded, since
     * the constructor never calls setTag() itself.
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $error = new Errormessage();

        $this->assertNull($error->getCustomTag());
    }
}
