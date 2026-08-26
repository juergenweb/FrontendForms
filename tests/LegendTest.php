<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Legend;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Legend.
 *
 * Everything inherited from TextElements is already covered by
 * TextElementsTest - this file only tests what Legend itself adds: the
 * tag and CSS class on construction.
 *
 * Unlike Label/Description/Notes/Errormessage/Successmessage, Legend does
 * NOT use TraitTags - confirmed there is no "legendtag" global config
 * feature in Form.php that would need it (no changeElementTag() call for
 * Legend), so setTag('legend') in the constructor is safe here without
 * the trait.
 */
final class LegendTest extends TestCase
{
    /**
     * 1) The element's tag is "legend".
     */
    public function testConstructorSetsLegendTag(): void
    {
        $legend = new Legend();

        $this->assertSame('legend', $legend->getTag());
    }

    /**
     * 2) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $legend = new Legend();

        $this->assertNotEmpty($legend->getAttribute('class'));
    }
}
