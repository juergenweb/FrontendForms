<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Username;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Username.
 */
final class UsernameTest extends TestCase
{
    /**
     * 1) The field's type defaults to "text" (inherited from InputText).
     */
    public function testConstructorSetsTextType(): void
    {
        $field = new Username('username');

        $this->assertSame('text', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Username('username');

        $this->assertSame('Username', $field->getLabel()->getText());
    }

    /**
     * 3) The "required" and "usernameSyntax" validation rules are
     * automatically applied.
     */
    public function testConstructorAppliesExpectedRules(): void
    {
        $field = new Username('username');

        $rules = $field->getRules();
        $this->assertArrayHasKey('required', $rules);
        $this->assertArrayHasKey('usernameSyntax', $rules);
    }

    /**
     * 4) For a guest (not logged in) visitor, no default value is
     * pre-filled.
     */
    public function testConstructorLeavesValueEmptyForGuest(): void
    {
        $field = new Username('username');

        $this->assertEmpty((string) $field->getAttribute('value'));
    }

    /**
     * 5) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new Username('username');

        $out = $field->renderUsername();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="text"', $out);
        $this->assertStringContainsString('name="username"', $out);
    }
}
