<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputTel;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputTel.
 */
final class InputTelTest extends TestCase
{
    /**
     * 1) The field's type defaults to "tel".
     */
    public function testConstructorSetsTelType(): void
    {
        $field = new InputTel('phone');

        $this->assertSame('tel', $field->getAttribute('type'));
    }

    /**
     * 2) The "noLetters" validation rule is automatically applied.
     */
    public function testConstructorAppliesNoLettersRule(): void
    {
        $field = new InputTel('phone');

        $this->assertArrayHasKey('noLetters', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputTel('phone');

        $out = $field->renderInputTel();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="tel"', $out);
        $this->assertStringContainsString('name="phone"', $out);
    }
}
