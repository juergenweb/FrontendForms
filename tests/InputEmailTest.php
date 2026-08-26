<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputEmail;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputEmail.
 */
final class InputEmailTest extends TestCase
{
    /**
     * 1) The field's type defaults to "email".
     */
    public function testConstructorSetsEmailType(): void
    {
        $field = new InputEmail('email');

        $this->assertSame('email', $field->getAttribute('type'));
    }

    /**
     * 2) Both the "email" and "emailDNS" validation rules are
     * automatically applied.
     */
    public function testConstructorAppliesEmailRules(): void
    {
        $field = new InputEmail('email');

        $rules = $field->getRules();
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('emailDNS', $rules);
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes, using the InputText render path.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputEmail('email');

        $out = $field->renderInputEmail();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="email"', $out);
        $this->assertStringContainsString('name="email"', $out);
    }
}
