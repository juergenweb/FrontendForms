<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\RequiredTextHint;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RequiredTextHint.
 *
 * Everything inherited from TextElements is already covered by
 * TextElementsTest - this file only tests what RequiredTextHint itself
 * adds: the tag and CSS class on construction. Like Legend, it does not
 * use TraitTags - confirmed there is no global tag config feature for it
 * in Form.php (no changeElementTag() call), so setTag('p') in the
 * constructor is safe here without the trait.
 */
final class RequiredTextHintTest extends TestCase
{
    /**
     * 1) The element's tag is "p".
     */
    public function testConstructorSetsParagraphTag(): void
    {
        $hint = new RequiredTextHint();

        $this->assertSame('p', $hint->getTag());
    }

    /**
     * 2) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $hint = new RequiredTextHint();

        $this->assertNotEmpty($hint->getAttribute('class'));
    }
}
