<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputUrl;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputUrl.
 */
final class InputUrlTest extends TestCase
{
    /**
     * 1) The field's type defaults to "url".
     */
    public function testConstructorSetsUrlType(): void
    {
        $field = new InputUrl('website');

        $this->assertSame('url', $field->getAttribute('type'));
    }

    /**
     * 2) Both the "url" and "urlActive" validation rules are
     * automatically applied.
     */
    public function testConstructorAppliesUrlRules(): void
    {
        $field = new InputUrl('website');

        $rules = $field->getRules();
        $this->assertArrayHasKey('url', $rules);
        $this->assertArrayHasKey('urlActive', $rules);
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputUrl('website');

        $out = $field->renderInputUrl();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="url"', $out);
        $this->assertStringContainsString('name="website"', $out);
    }
}
