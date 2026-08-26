<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Surname;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Surname.
 */
final class SurnameTest extends TestCase
{
    /**
     * 1) The field's type defaults to "text" (inherited from InputText).
     */
    public function testConstructorSetsTextType(): void
    {
        $field = new Surname('surname');

        $this->assertSame('text', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Surname('surname');

        $this->assertSame('Surname', $field->getLabel()->getText());
    }

    /**
     * 3) The "firstAndLastname" validation rule is automatically applied.
     */
    public function testConstructorAppliesFirstAndLastnameRule(): void
    {
        $field = new Surname('surname');

        $this->assertArrayHasKey('firstAndLastname', $field->getRules());
    }

    /**
     * 4) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new Surname('surname');

        $out = $field->renderSurname();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="text"', $out);
        $this->assertStringContainsString('name="surname"', $out);
    }
}
