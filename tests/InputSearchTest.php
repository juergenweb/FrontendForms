<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputSearch;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputSearch.
 */
final class InputSearchTest extends TestCase
{
    /**
     * 1) The field's type defaults to "search".
     */
    public function testConstructorSetsSearchType(): void
    {
        $field = new InputSearch('q');

        $this->assertSame('search', $field->getAttribute('type'));
    }

    /**
     * 2) No default validation rule is applied - search fields have no
     * inherent format constraint.
     */
    public function testConstructorAppliesNoDefaultRule(): void
    {
        $field = new InputSearch('q');

        $this->assertSame([], $field->getRules());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new InputSearch('q');

        $out = $field->renderInputSearch();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="search"', $out);
        $this->assertStringContainsString('name="q"', $out);
    }
}
