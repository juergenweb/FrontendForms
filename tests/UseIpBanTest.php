<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Alert;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for the IP-ban re-check fix: Form::useIPBan() and
 * Form::testIPBan() must actually re-run the IP blacklist check after
 * changing the settings it depends on, rather than only updating a
 * config value / the visitor IP without any further effect on the
 * already-computed result from construction time.
 *
 * newInstanceWithoutConstructor() avoids Form's full, heavy constructor;
 * only the properties runIpBanCheck() actually reads/writes (frontendforms
 * from Tag.php, alert, visitorIP, showForm, ipCheckPassed) are populated
 * via reflection. IPBlacklistGuard::check() - the method actually doing
 * the work - only reads its three passed-in parameters and doesn't use
 * its stored $input/$form/$alert dependencies at all, so a plain, minimal
 * Alert instance is enough to satisfy BaseGuard's constructor type hints.
 */
final class UseIpBanTest extends TestCase
{
    private function makeForm(bool $useIPBan, string $preventIPs, string $visitorIP): \FrontendForms\Form
    {
        $ref = new ReflectionClass(\FrontendForms\Form::class);
        /** @var \FrontendForms\Form $form */
        $form = $ref->newInstanceWithoutConstructor();

        $frontendformsProp = new ReflectionProperty(\FrontendForms\Tag::class, 'frontendforms');
        $frontendformsProp->setAccessible(true);
        $frontendformsProp->setValue($form, [
            'input_useIPBan' => $useIPBan ? 1 : 0,
            'input_preventIPs' => $preventIPs,
        ]);

        $alertProp = new ReflectionProperty(\FrontendForms\Form::class, 'alert');
        $alertProp->setAccessible(true);
        $alertProp->setValue($form, new Alert());

        $visitorIpProp = new ReflectionProperty(\FrontendForms\Form::class, 'visitorIP');
        $visitorIpProp->setAccessible(true);
        $visitorIpProp->setValue($form, $visitorIP);

        return $form;
    }

    private function runInitialCheck(\FrontendForms\Form $form): void
    {
        $method = new ReflectionMethod(\FrontendForms\Form::class, 'runIpBanCheck');
        $method->setAccessible(true);
        $method->invoke($form);
    }

    private function getShowForm(\FrontendForms\Form $form): bool
    {
        $prop = new ReflectionProperty(\FrontendForms\Form::class, 'showForm');
        $prop->setAccessible(true);
        return $prop->getValue($form);
    }

    private function getIpCheckPassed(\FrontendForms\Form $form): bool
    {
        $prop = new ReflectionProperty(\FrontendForms\Form::class, 'ipCheckPassed');
        $prop->setAccessible(true);
        return $prop->getValue($form);
    }

    /**
     * 1) Baseline: with the ban enabled and the visitor's IP on the list,
     * the initial check (matching what the constructor does) correctly
     * blocks the form.
     */
    public function testInitialCheckBlocksBannedIp(): void
    {
        $form = $this->makeForm(true, "192.168.1.1\n10.0.0.1", '192.168.1.1');
        $this->runInitialCheck($form);

        $this->assertFalse($this->getShowForm($form));
        $this->assertFalse($this->getIpCheckPassed($form));
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: calling useIPBan(false) after
     * the initial (constructor-equivalent) check blocked a banned IP must
     * actually re-run the check and correctly show the form again - not
     * silently leave the earlier, stale "blocked" result in place.
     */
    public function testUseIPBanFalseUnblocksAPreviouslyBannedVisitor(): void
    {
        $form = $this->makeForm(true, "192.168.1.1\n10.0.0.1", '192.168.1.1');
        $this->runInitialCheck($form);
        $this->assertFalse($this->getShowForm($form), 'sanity check: form should be blocked initially');

        $form->useIPBan(false);

        $this->assertTrue($this->getShowForm($form));
        $this->assertTrue($this->getIpCheckPassed($form));
    }

    /**
     * 3) The reverse direction also works: enabling the ban after
     * construction (on a form built with it disabled) correctly blocks a
     * banned visitor.
     */
    public function testUseIPBanTrueBlocksABannedVisitorOnceEnabled(): void
    {
        $form = $this->makeForm(false, '192.168.1.1', '192.168.1.1');
        $this->runInitialCheck($form);
        $this->assertTrue($this->getShowForm($form), 'sanity check: form should be visible initially (ban disabled)');

        $form->useIPBan(true);

        $this->assertFalse($this->getShowForm($form));
        $this->assertFalse($this->getIpCheckPassed($form));
    }

    /**
     * 4) REGRESSION TEST for the fixed bug, mirrored for testIPBan(): the
     * initial check passes for a non-banned "real" visitor, but simulating
     * a banned IP via testIPBan() must actually re-run the check with that
     * simulated IP - not silently keep the earlier, real-IP result.
     */
    public function testTestIPBanReEvaluatesWithTheSimulatedIp(): void
    {
        $form = $this->makeForm(true, '192.168.1.1', '10.0.0.1'); // real visitor: not banned
        $this->runInitialCheck($form);
        $this->assertTrue($this->getShowForm($form), 'sanity check: real visitor should not be blocked');

        $form->testIPBan('192.168.1.1'); // simulate the banned IP

        $this->assertFalse($this->getShowForm($form));
        $this->assertFalse($this->getIpCheckPassed($form));
    }

    /**
     * 5) testIPBan() with an invalid IP address throws, rather than
     * silently simulating a nonsensical value.
     */
    public function testTestIPBanRejectsInvalidIpAddress(): void
    {
        $form = $this->makeForm(true, '192.168.1.1', '10.0.0.1');
        $this->runInitialCheck($form);

        $this->expectException(\Exception::class);
        $form->testIPBan('not-a-valid-ip');
    }

    /**
     * 6) An empty prevent-IPs list means nothing is ever blocked, even
     * with the ban enabled - and this remains correctly reflected after
     * calling useIPBan() again.
     */
    public function testEmptyPreventIpsListNeverBlocksRegardlessOfToggle(): void
    {
        $form = $this->makeForm(true, '', '192.168.1.1');
        $this->runInitialCheck($form);
        $this->assertTrue($this->getShowForm($form));

        $form->useIPBan(true); // re-toggling to the same value
        $this->assertTrue($this->getShowForm($form));
    }
}
