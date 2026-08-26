<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputTime;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputTime.
 *
 * The "time" rule itself used to reference a validation rule that was
 * never implemented anywhere, causing Valitron to throw for every form
 * containing this field as soon as validation ran - see the dedicated
 * regression tests in DateRulesTest for that fix at the rule-registration
 * level. These tests cover InputTime's own field-level behaviour.
 */
final class InputTimeTest extends TestCase
{
    /**
     * 1) The field's type defaults to "time".
     */
    public function testConstructorSetsTimeType(): void
    {
        $field = new InputTime('starttime');

        $this->assertSame('time', $field->getAttribute('type'));
    }

    /**
     * 2) The "time" validation rule is automatically applied.
     */
    public function testConstructorAppliesTimeRule(): void
    {
        $field = new InputTime('starttime');

        $this->assertArrayHasKey('time', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputTime('starttime');

        $out = $field->renderInputTime();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="time"', $out);
        $this->assertStringContainsString('name="starttime"', $out);
    }

    /**
     * 4) A value set on the field appears in the rendered output.
     */
    public function testRenderIncludesValueAttribute(): void
    {
        $field = new InputTime('starttime');
        $field->setAttribute('value', '14:30');

        $out = $field->renderInputTime();

        $this->assertStringContainsString('value="14:30"', $out);
    }
}
