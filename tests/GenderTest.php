<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Gender;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Gender.
 */
final class GenderTest extends TestCase
{
    /**
     * 1) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Gender('gender');

        $this->assertSame('Gender', $field->getLabel()->getText());
    }

    /**
     * 2) Without a PW field name, the three default gender options are
     * added, alongside the empty "please select" option.
     */
    public function testConstructorAddsDefaultOptionsWithoutFieldName(): void
    {
        $field = new Gender('gender');

        $options = $field->getOptions();

        $this->assertCount(4, $options);
    }

    /**
     * 3) The "required" validation rule is automatically applied.
     */
    public function testConstructorAppliesRequiredRule(): void
    {
        $field = new Gender('gender');

        $this->assertArrayHasKey('required', $field->getRules());
    }

    /**
     * 4) REGRESSION-STYLE TEST: rendering works through the hookable
     * ___renderGender() method (fixed to match the parent Select class's
     * own hookable ___renderSelect() pattern - it was previously a plain,
     * non-hookable renderGender()).
     */
    public function testRenderProducesCorrectSelectTag(): void
    {
        $field = new Gender('gender');

        $out = $field->renderGender();

        $this->assertStringStartsWith('<select', $out);
        $this->assertStringContainsString('name="gender"', $out);
    }
}
