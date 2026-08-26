<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SendCopy;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SendCopy.
 */
final class SendCopyTest extends TestCase
{
    /**
     * 1) The field's type defaults to "checkbox" (inherited from
     * InputCheckbox).
     */
    public function testConstructorSetsCheckboxType(): void
    {
        $field = new SendCopy('sendcopy');

        $this->assertSame('checkbox', $field->getAttribute('type'));
    }

    /**
     * 2) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new SendCopy('sendcopy');

        $this->assertSame('Send a copy of my message to me', $field->getLabel()->getText());
    }

    /**
     * 3) Rendering produces a self-closing input tag with the correct
     * type and name attributes.
     */
    public function testRenderProducesCorrectInputTag(): void
    {
        $field = new SendCopy('sendcopy');

        $out = $field->renderSendCopy();

        $this->assertStringStartsWith('<input', $out);
        $this->assertStringContainsString('type="checkbox"', $out);
        $this->assertStringContainsString('name="sendcopy"', $out);
    }
}
