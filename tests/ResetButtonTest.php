<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\ResetButton;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ResetButton.
 */
final class ResetButtonTest extends TestCase
{
    /**
     * 1) The button's type defaults to "reset".
     */
    public function testConstructorSetsResetType(): void
    {
        $button = new ResetButton();

        $this->assertSame('reset', $button->getAttribute('type'));
    }

    /**
     * 2) The default value/caption is set.
     */
    public function testConstructorSetsDefaultValue(): void
    {
        $button = new ResetButton();

        $this->assertSame('Reset', $button->getAttribute('value'));
    }

    /**
     * 3) A custom name can be passed to the constructor instead of the
     * default "reset".
     */
    public function testConstructorAcceptsCustomName(): void
    {
        $button = new ResetButton('clearform');

        $this->assertSame('clearform', $button->getAttribute('name'));
    }

    /**
     * 4) Rendering produces a button tag with the correct type attribute,
     * via the inherited (hookable) render() method from Button.
     */
    public function testRenderProducesCorrectButtonTag(): void
    {
        $button = new ResetButton();

        $out = $button->render();

        $this->assertStringStartsWith('<button', $out);
        $this->assertStringContainsString('type="reset"', $out);
    }
}
