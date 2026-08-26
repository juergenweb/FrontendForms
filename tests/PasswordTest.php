<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Password;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Password.
 */
final class PasswordTest extends TestCase
{
    /**
     * 1) The field's type defaults to "password".
     */
    public function testConstructorSetsPasswordType(): void
    {
        $field = new Password('password');

        $this->assertSame('password', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Password('password');

        $this->assertSame('Password', $field->getLabel()->getText());
    }

    /**
     * 3) The "required", "safePassword", "lengthMin" and "lengthMax"
     * validation rules are all automatically applied.
     */
    public function testConstructorAppliesAllExpectedRules(): void
    {
        $field = new Password('password');

        $rules = $field->getRules();
        $this->assertArrayHasKey('required', $rules);
        $this->assertArrayHasKey('safePassword', $rules);
        $this->assertArrayHasKey('lengthMin', $rules);
        $this->assertArrayHasKey('lengthMax', $rules);
    }

    /**
     * 4) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new Password('password');

        $out = $field->renderPassword();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="password"', $out);
        $this->assertStringContainsString('name="password"', $out);
    }
}
