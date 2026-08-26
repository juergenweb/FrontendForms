<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Progressbar;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Progressbar.
 */
final class ProgressbarTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The element's tag defaults to "progress".
     */
    public function testConstructorSetsProgressTag(): void
    {
        $bar = new Progressbar();

        $this->assertSame('progress', $bar->getTag());
    }

    /**
     * 2) An id can optionally be passed through to the underlying element.
     */
    public function testConstructorAcceptsOptionalId(): void
    {
        $bar = new Progressbar('my-progress');

        $this->assertSame('my-progress', $bar->getAttribute('id'));
    }

    // --- ___render() ---

    /**
     * 3) With no content set, the element still renders as an open/close
     * tag pair (empty content is shown, not suppressed) - this matches how
     * Progressbar is actually used throughout the module (e.g. the
     * MultiStepController's steps progressbar has no textual content of
     * its own, just attributes like "value"/"max").
     */
    public function testRenderShowsEmptyProgressTag(): void
    {
        $bar = new Progressbar();

        $this->assertSame('<progress></progress>', $bar->render());
    }

    /**
     * 4) Attributes set on the element (e.g. "value"/"max", as used for a
     * real progress indicator) appear in the rendered tag.
     */
    public function testRenderIncludesValueAndMaxAttributes(): void
    {
        $bar = new Progressbar();
        $bar->setAttribute('value', '3');
        $bar->setAttribute('max', '5');

        $out = $bar->render();

        $this->assertStringContainsString('value="3"', $out);
        $this->assertStringContainsString('max="5"', $out);
    }

    /**
     * 5) The tag can be changed away from the default "progress" (as
     * Form.php does for CSS-framework-specific progress bar markup, e.g.
     * a styled "div" instead of a native <progress> element).
     */
    public function testTagCanBeChangedFromDefault(): void
    {
        $bar = new Progressbar();
        $bar->setTag('div');

        $this->assertSame('<div></div>', $bar->render());
    }

    /**
     * 6) Explicit content set on the element is rendered inside the tag.
     */
    public function testRenderIncludesExplicitContent(): void
    {
        $bar = new Progressbar();
        $bar->setContent('<div class="bar-indicator"></div>');

        $this->assertSame('<progress><div class="bar-indicator"></div></progress>', $bar->render());
    }
}
