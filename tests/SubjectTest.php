<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Subject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Subject.
 */
final class SubjectTest extends TestCase
{
    /**
     * 1) The field's type defaults to "text" (inherited from InputText).
     */
    public function testConstructorSetsTextType(): void
    {
        $field = new Subject('subject');

        $this->assertSame('text', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Subject('subject');

        $this->assertSame('Subject', $field->getLabel()->getText());
    }

    /**
     * 3) The "required" validation rule is automatically applied.
     */
    public function testConstructorAppliesRequiredRule(): void
    {
        $field = new Subject('subject');

        $this->assertArrayHasKey('required', $field->getRules());
    }

    /**
     * 4) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new Subject('subject');

        $out = $field->renderSubject();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="text"', $out);
        $this->assertStringContainsString('name="subject"', $out);
    }
}
