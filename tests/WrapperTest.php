<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Wrapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Wrapper.
 */
final class WrapperTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag defaults to "div".
     */
    public function testConstructorSetsDivTag(): void
    {
        $wrapper = new Wrapper();

        $this->assertSame('div', $wrapper->getTag());
    }

    // --- ___render() ---

    /**
     * 2) With no content, rendering returns an empty string - matches the
     * general renderNonSelfclosingTag() default (showNoContent not passed,
     * so it defaults to false).
     */
    public function testRenderWithNoContentReturnsEmptyString(): void
    {
        $wrapper = new Wrapper();

        $this->assertSame('', $wrapper->render());
    }

    /**
     * 3) Content set via setContent() (as callers like FieldsetOpen or
     * Inputfields wrappers do to insert a field's own markup) is rendered
     * inside the tag.
     */
    public function testRenderIncludesContent(): void
    {
        $wrapper = new Wrapper();
        $wrapper->setContent('<input type="text">');

        $this->assertSame('<div><input type="text"></div>', $wrapper->render());
    }

    /**
     * 4) A CSS class set on the wrapper appears in the rendered tag.
     */
    public function testRenderIncludesCssClass(): void
    {
        $wrapper = new Wrapper();
        $wrapper->setContent('x');
        $wrapper->setAttribute('class', 'my-wrapper');

        $out = $wrapper->render();

        $this->assertStringContainsString('class="my-wrapper"', $out);
    }

    /**
     * 5) The tag can be changed away from the default "div".
     */
    public function testTagCanBeChangedFromDefault(): void
    {
        $wrapper = new Wrapper();
        $wrapper->setTag('span');
        $wrapper->setContent('x');

        $this->assertSame('<span>x</span>', $wrapper->render());
    }

    // --- __toString() ---

    /**
     * 6) Casting the element to a string produces the same output as
     * calling render() directly.
     */
    public function testToStringMatchesRender(): void
    {
        $wrapper = new Wrapper();
        $wrapper->setContent('x');

        $this->assertSame($wrapper->render(), (string) $wrapper);
    }
}
