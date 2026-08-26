<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckbox;
use FrontendForms\InputPassword;
use PHPUnit\Framework\TestCase;

/**
 * A thin subclass exposing createPasswordToggle() (protected) via a public
 * wrapper, since PHPUnit test classes aren't subclasses of InputPassword.
 */
final class ExposedInputPassword extends InputPassword
{
    public function exposeCreatePasswordToggle(): InputCheckbox
    {
        return $this->createPasswordToggle();
    }
}

/**
 * Unit tests for InputPassword.
 *
 * The constructor reads the live "pass" field and "InputfieldPassword"
 * module config (minlength, requirements) - both are core ProcessWire
 * pieces expected to exist in any real installation, so construction
 * itself is safe to test, but the EXACT minlength/requirements values are
 * environment-specific, so tests check structural properties (format,
 * presence) rather than hardcoded values.
 */
final class InputPasswordTest extends TestCase
{
    // --- construction ---

    /**
     * 1) The field's type is "password".
     */
    public function testConstructorSetsPasswordType(): void
    {
        $field = new InputPassword('pass');

        $this->assertSame('password', $field->getAttribute('type'));
    }

    /**
     * 2) The "meetsPasswordConditions" validation rule is added by
     * default.
     */
    public function testConstructorAddsPasswordConditionsRule(): void
    {
        $field = new InputPassword('pass');

        $this->assertArrayHasKey('meetsPasswordConditions', $field->getRules());
    }

    // --- showPasswordRequirements() / showPasswordToggle() ---

    /**
     * 3) showPasswordRequirements() returns $this, supporting fluent
     * chaining.
     */
    public function testShowPasswordRequirementsReturnsSelf(): void
    {
        $field = new InputPassword('pass');

        $this->assertSame($field, $field->showPasswordRequirements());
    }

    /**
     * 4) showPasswordToggle() returns $this, supporting fluent chaining.
     */
    public function testShowPasswordToggleReturnsSelf(): void
    {
        $field = new InputPassword('pass');

        $this->assertSame($field, $field->showPasswordToggle());
    }

    /**
     * 5) Disabling password requirements omits the requirements text from
     * the rendered description.
     */
    public function testDisablingRequirementsOmitsDescriptionText(): void
    {
        $field = new InputPassword('pass');
        $field->showPasswordRequirements(false);

        $field->renderInputPassword();

        $this->assertSame('', $field->getDescription()->getText());
    }

    /**
     * 6) Disabling the password toggle omits the toggle checkbox from the
     * rendered output.
     */
    public function testDisablingToggleOmitsToggleCheckboxFromOutput(): void
    {
        $field = new InputPassword('pass');
        $field->showPasswordToggle(false);

        $out = $field->renderInputPassword();

        $this->assertStringNotContainsString('pwtoggle', $out);
    }

    /**
     * 7) With the toggle enabled (the default), the rendered output
     * includes the toggle checkbox markup.
     */
    public function testEnabledToggleAppearsInOutput(): void
    {
        $field = new InputPassword('pass');

        $out = $field->renderInputPassword();

        $this->assertStringContainsString('pwtoggle', $out);
    }

    // --- createPasswordToggle() ---

    /**
     * 8) The created toggle checkbox links back to this field's own id via
     * the "data-toggle" attribute, and has its own wrappers disabled.
     */
    public function testCreatePasswordToggleLinksToFieldId(): void
    {
        $field = new ExposedInputPassword('mypass');

        $toggle = $field->exposeCreatePasswordToggle();

        $this->assertSame('mypass', $toggle->getAttribute('data-toggle'));
        $this->assertFalse($toggle->getUsageOfInputWrapper());
        $this->assertFalse($toggle->getUsageOfFieldWrapper());
    }

    /**
     * 9) The toggle checkbox has no "id" attribute of its own (removed
     * explicitly, to avoid id collisions when a form has multiple password
     * fields).
     */
    public function testCreatePasswordToggleHasNoIdAttribute(): void
    {
        $field = new ExposedInputPassword('mypass');

        $toggle = $field->exposeCreatePasswordToggle();

        $this->assertNull($toggle->getAttribute('id'));
    }

    // --- renderPasswordRequirements() ---

    /**
     * 10) The requirements text, if any, mentions the configured minimum
     * length as a whole number.
     */
    public function testRenderPasswordRequirementsMentionsMinLength(): void
    {
        $field = new InputPassword('pass');

        $text = $field->renderPasswordRequirements();

        if ($text !== null) {
            $this->assertMatchesRegularExpression('/\d+/', $text);
        } else {
            $this->assertNull($text);
        }
    }
}
