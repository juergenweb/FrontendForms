<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Email;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email (the pre-configured default field class, not to be
 * confused with InputEmail).
 */
final class EmailTest extends TestCase
{
    /**
     * 1) A non-empty label is set on construction.
     */
    public function testConstructorSetsNonEmptyLabel(): void
    {
        $email = new Email('email');

        $this->assertNotSame('', $email->getLabel()->getText());
    }

    /**
     * 2) The field's type is "email", inherited from InputEmail.
     */
    public function testConstructorSetsEmailType(): void
    {
        $email = new Email('email');

        $this->assertSame('email', $email->getAttribute('type'));
    }

    /**
     * 3) renderEmail() (dispatched via the class-name-based render
     * mechanism in Inputfields - "render" + className() = "renderEmail"
     * for an Email instance) produces the exact same output as the
     * inherited renderInputEmail() it forwards to - it's purely a routing
     * shim, not a behavioural change.
     */
    public function testRenderEmailMatchesRenderInputEmail(): void
    {
        $email = new Email('email');

        $this->assertSame($email->renderInputEmail(), $email->renderEmail());
    }

    /**
     * 4) The rendered output is a real, well-formed email input tag.
     */
    public function testRenderEmailProducesEmailInputTag(): void
    {
        $email = new Email('email');

        $out = $email->renderEmail();

        $this->assertStringContainsString('<input', $out);
        $this->assertStringContainsString('type="email"', $out);
        $this->assertStringContainsString('name="email"', $out);
    }
}
