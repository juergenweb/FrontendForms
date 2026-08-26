<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use FrontendForms\BaseGuard;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;
use ReflectionProperty;

/**
 * Minimal concrete subclass, since BaseGuard is abstract.
 */
final class ConcreteGuard extends BaseGuard
{
}

/**
 * Unit test for BaseGuard.
 *
 * BaseGuard has no functionality of its own beyond its shared constructor
 * - all seven real guards (TimingGuard, RefererGuard, SubmitGuard,
 * CSRFGuard, IPBlacklistGuard, AttemptGuard, HoneypotGuard) already have
 * their own dedicated tests that exercise this constructor indirectly.
 * This test just confirms the three dependencies are correctly assigned
 * to their promoted, protected readonly properties (read via reflection,
 * since they aren't publicly accessible).
 */
final class BaseGuardTest extends TestCase
{
    /**
     * 1) The input, form, and alert dependencies passed to the
     * constructor are correctly stored in their properties.
     */
    public function testConstructorAssignsDependencies(): void
    {
        $data = [];
        $input = new WireInputData($data);
        $form = new Form('myform');
        $alert = new Alert();

        $guard = new ConcreteGuard($input, $form, $alert);

        foreach (['input' => $input, 'form' => $form, 'alert' => $alert] as $property => $expected) {
            $reflection = new ReflectionProperty($guard, $property);
            $reflection->setAccessible(true);

            $this->assertSame($expected, $reflection->getValue($guard));
        }
    }
}