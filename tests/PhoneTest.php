<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Phone;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Phone.
 */
final class PhoneTest extends TestCase
{
    /**
     * 1) The field's type defaults to "tel" (inherited from InputTel).
     */
    public function testConstructorSetsTelType(): void
    {
        $field = new Phone('phone');

        $this->assertSame('tel', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Phone('phone');

        $this->assertSame('Phone', $field->getLabel()->getText());
    }

    /**
     * 3) The "noLetters" rule is inherited from InputTel.
     */
    public function testConstructorAppliesNoLettersRule(): void
    {
        $field = new Phone('phone');

        $this->assertArrayHasKey('noLetters', $field->getRules());
    }

    /**
     * 4) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new Phone('phone');

        $out = $field->renderPhone();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="tel"', $out);
        $this->assertStringContainsString('name="phone"', $out);
    }
}
