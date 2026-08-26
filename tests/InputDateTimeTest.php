<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputDateTime;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputDateTime.
 *
 * The inherited "date" rule uses Valitron's native validateDate(), which
 * is a loose strtotime()-based check (confirmed standalone to correctly
 * parse the "datetime-local" format, e.g. "2025-06-15T14:30") - so it
 * remains valid for this subclass despite the different HTML5 type.
 */
final class InputDateTimeTest extends TestCase
{
    /**
     * 1) The field's type defaults to "datetime-local".
     */
    public function testConstructorSetsDateTimeLocalType(): void
    {
        $field = new InputDateTime('appointment');

        $this->assertSame('datetime-local', $field->getAttribute('type'));
    }

    /**
     * 2) The "date" validation rule is inherited from InputDate.
     */
    public function testConstructorAppliesDateRule(): void
    {
        $field = new InputDateTime('appointment');

        $this->assertArrayHasKey('date', $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputDateTime('appointment');

        $out = $field->renderInputDateTime();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="datetime-local"', $out);
        $this->assertStringContainsString('name="appointment"', $out);
    }
}
