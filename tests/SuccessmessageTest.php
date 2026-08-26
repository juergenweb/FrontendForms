<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Successmessage;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Successmessage.
 *
 * Everything inherited from TextElements is already covered by
 * TextElementsTest - this file only tests what Successmessage itself
 * adds: the CSS class on construction, and confirms TraitTags integration
 * is safe from the customTag-poisoning bug fixed in Label earlier in this
 * session (Successmessage's constructor never calls setTag() itself).
 */
final class SuccessmessageTest extends TestCase
{
    /**
     * 1) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $success = new Successmessage();

        $this->assertNotEmpty($success->getAttribute('class'));
    }

    /**
     * 2) A freshly created Successmessage has no custom tag recorded,
     * since the constructor never calls setTag() itself.
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $success = new Successmessage();

        $this->assertNull($success->getCustomTag());
    }
}
