<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Alert.
 *
 * Everything inherited from TextElements (setText()/getText(), render(),
 * wrap() handling, __toString()) is already covered by TextElementsTest -
 * this file only tests what Alert itself adds via its constructor.
 */
final class AlertTest extends TestCase
{
    /**
     * 1) The element's tag is "div".
     */
    public function testConstructorSetsDivTag(): void
    {
        $alert = new Alert();

        $this->assertSame('div', $alert->getTag());
    }

    /**
     * 2) A CSS class is applied on construction (the exact class name
     * depends on the live "alertClass" module config, similar to the
     * framework-dependent classes seen in the renderer tests earlier in
     * this session, so only presence - not the specific value - is
     * checked here).
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $alert = new Alert();

        $this->assertNotEmpty($alert->getAttribute('class'));
    }

    /**
     * 3) An id can optionally be passed through to the underlying
     * element.
     */
    public function testConstructorAcceptsOptionalId(): void
    {
        $alert = new Alert('my-alert');

        $this->assertSame('my-alert', $alert->getAttribute('id'));
    }

    /**
     * 4) Without an id, none is set.
     */
    public function testConstructorWithoutIdSetsNoIdAttribute(): void
    {
        $alert = new Alert();

        $this->assertNull($alert->getAttribute('id'));
    }
}
