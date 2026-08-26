<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FieldsetClose;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FieldsetClose.
 */
final class FieldsetCloseTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag defaults to "fieldset".
     */
    public function testConstructorSetsFieldsetTag(): void
    {
        $close = new FieldsetClose();

        $this->assertSame('fieldset', $close->getTag());
    }

    // --- __toString() ---

    /**
     * 2) Casting the element to a string produces the same output as
     * calling render() directly.
     */
    public function testToStringMatchesRender(): void
    {
        $close = new FieldsetClose();

        $this->assertSame($close->render(), (string) $close);
    }

    // --- ___render() ---

    /**
     * 3) Rendering produces exactly the closing tag, nothing else - no
     * attributes, no opening tag, matching the "Close" half of the
     * FieldsetOpen/FieldsetClose pair.
     */
    public function testRenderProducesExactlyClosingTag(): void
    {
        $close = new FieldsetClose();

        $this->assertSame('</fieldset>', $close->render());
    }

    /**
     * 4) The rendered output stays the closing tag even if attributes are
     * set on the element - they're simply ignored, since render() ignores
     * everything except the tag name.
     */
    public function testRenderIgnoresAnyAttributesSet(): void
    {
        $close = new FieldsetClose();
        $close->setAttribute('id', 'should-be-ignored');
        $close->setAttribute('class', 'also-ignored');

        $this->assertSame('</fieldset>', $close->render());
    }
}
