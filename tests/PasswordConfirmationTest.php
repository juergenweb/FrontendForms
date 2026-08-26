<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\PasswordConfirmation;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PasswordConfirmation.
 */
final class PasswordConfirmationTest extends TestCase
{
    /**
     * 1) The field's type defaults to "password".
     */
    public function testConstructorSetsPasswordType(): void
    {
        $field = new PasswordConfirmation('password_confirm', 'password');

        $this->assertSame('password', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new PasswordConfirmation('password_confirm', 'password');

        $this->assertSame('Password Confirmation', $field->getLabel()->getText());
    }

    /**
     * 3) The "required", "equals", "lengthMin" and "lengthMax" validation
     * rules are all automatically applied.
     */
    public function testConstructorAppliesAllExpectedRules(): void
    {
        $field = new PasswordConfirmation('password_confirm', 'password');

        $rules = $field->getRules();
        $this->assertArrayHasKey('required', $rules);
        $this->assertArrayHasKey('equals', $rules);
        $this->assertArrayHasKey('lengthMin', $rules);
        $this->assertArrayHasKey('lengthMax', $rules);
    }

    /**
     * 4) The "equals" rule's stored options reference the given password
     * field name (with the form-id prefix that setRule() applies for
     * equals/different rules specifically).
     */
    public function testConstructorEqualsRuleReferencesPasswordField(): void
    {
        $field = new PasswordConfirmation('password_confirm', 'password');

        $equalsOptions = $field->getRules()['equals']['options'];

        $this->assertStringEndsWith('password', $equalsOptions[0]);
    }

    /**
     * 5) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new PasswordConfirmation('password_confirm', 'password');

        $out = $field->renderPasswordConfirmation();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="password"', $out);
        $this->assertStringContainsString('name="password_confirm"', $out);
    }
}
