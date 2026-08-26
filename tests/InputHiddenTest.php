<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputHidden;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputHidden.
 */
final class InputHiddenTest extends TestCase
{
    /**
     * 1) The field's type defaults to "hidden".
     */
    public function testConstructorSetsHiddenType(): void
    {
        $field = new InputHidden('token');

        $this->assertSame('hidden', $field->getAttribute('type'));
    }

    /**
     * 2) Input and field wrappers are disabled by default, since a hidden
     * field has no visible label/description to wrap.
     */
    public function testConstructorDisablesWrappers(): void
    {
        $field = new InputHidden('token');

        $this->assertFalse($field->getUsageOfInputWrapper());
        $this->assertFalse($field->getUsageOfFieldWrapper());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputHidden('token');

        $out = $field->renderInputHidden();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="hidden"', $out);
        $this->assertStringContainsString('name="token"', $out);
    }

    /**
     * 4) A value set on the field appears in the rendered output.
     */
    public function testRenderIncludesValueAttribute(): void
    {
        $field = new InputHidden('token');
        $field->setAttribute('value', 'abc123');

        $out = $field->renderInputHidden();

        $this->assertStringContainsString('value="abc123"', $out);
    }
}
