<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Message;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Message.
 */
final class MessageTest extends TestCase
{
    /**
     * 1) The default label is set.
     */
    public function testConstructorSetsDefaultLabel(): void
    {
        $field = new Message('message');

        $this->assertSame('Message', $field->getLabel()->getText());
    }

    /**
     * 2) The "required" validation rule is automatically applied.
     */
    public function testConstructorAppliesRequiredRule(): void
    {
        $field = new Message('message');

        $this->assertArrayHasKey('required', $field->getRules());
    }

    /**
     * 3) REGRESSION-STYLE TEST: rendering works through the hookable
     * ___renderMessage() method (fixed to match the parent Textarea
     * class's own hookable ___renderTextarea() pattern - it was
     * previously a plain, non-hookable renderMessage()).
     */
    public function testRenderProducesCorrectTextareaTag(): void
    {
        $field = new Message('message');

        $out = $field->renderMessage();

        $this->assertStringStartsWith('<textarea', $out);
        $this->assertStringContainsString('name="message"', $out);
    }
}
