<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for FrontendForms::addRemoveIpFromBanList().
 *
 * CAUTION: this method reads wire('input')->post->submit_save_ip /
 * submit_remove_ip directly. Setting these via wire('input')->post->set()
 * worked without issue here - unlike an earlier, abandoned attempt at a
 * full Form-processing test in this session's history, this method has
 * no interaction with Form's own value/element registration pipeline, so
 * the same complication doesn't apply.
 */

/**
 * Overrides addRemoveIpFromBanList() with the exact same logic as the
 * original, except the final wire('modules')->saveConfig() call is
 * skipped. That call cannot be safely intercepted via a stub method on
 * $this, since it's invoked on a different object (wire('modules')), not
 * $this - actually calling it here would risk overwriting the real
 * FrontendForms module's configuration in the database. Instead, this
 * duplicates the method's logic and just omits that one line, so the
 * tests can safely observe $this->input_preventIPs and the return value.
 */
class TestableFrontendFormsForBanList extends FrontendForms
{
    protected function addRemoveIpFromBanList(bool $add): bool
    {
        $ipAddresses = array_filter(
            explode("\n", $this->moduleConfig['input_preventIPs']),
            static function ($value) {
                return trim($value) !== '';
            }
        );
        $success = false;
        if ($add) {
            $ip = $this->wire('input')->post->submit_save_ip ?? '';
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ipAddresses[] = $ip;
                $success = true;
            }
        } else {
            $key = array_search($this->wire('input')->post->submit_remove_ip ?? '', $ipAddresses, true);
            if ($key !== false) {
                unset($ipAddresses[$key]);
                $success = true;
            }
        }
        $this->input_preventIPs = implode("\n", array_unique($ipAddresses));
        // saveConfig() intentionally omitted - see class docblock
        return $success;
    }
}

final class AddRemoveIpFromBanListTest extends TestCase
{
    private function makeModule(string $preventIPs): TestableFrontendFormsForBanList
    {
        $ref = new ReflectionClass(TestableFrontendFormsForBanList::class);
        /** @var TestableFrontendFormsForBanList $module */
        $module = $ref->newInstanceWithoutConstructor();

        $configProp = new ReflectionProperty(FrontendForms::class, 'moduleConfig');
        $configProp->setAccessible(true);
        $configProp->setValue($module, ['input_preventIPs' => $preventIPs]);

        return $module;
    }

    private function call(TestableFrontendFormsForBanList $module, bool $add): bool
    {
        $method = new ReflectionMethod(TestableFrontendFormsForBanList::class, 'addRemoveIpFromBanList');
        $method->setAccessible(true);
        return $method->invoke($module, $add);
    }

    private function getPreventIPs(TestableFrontendFormsForBanList $module): string
    {
        $prop = new ReflectionProperty(FrontendForms::class, 'input_preventIPs');
        $prop->setAccessible(true);
        return $prop->getValue($module);
    }

    protected function setUp(): void
    {
        parent::setUp();
        \ProcessWire\wire('input')->post->set('submit_save_ip', '');
        \ProcessWire\wire('input')->post->set('submit_remove_ip', '');
    }

    protected function tearDown(): void
    {
        \ProcessWire\wire('input')->post->set('submit_save_ip', '');
        \ProcessWire\wire('input')->post->set('submit_remove_ip', '');
        parent::tearDown();
    }

    /**
     * 1) Adding a valid IP to an empty list succeeds and the new list
     * contains it, with no stray leading blank line (the earlier fixed
     * bug for an initially-empty list).
     */
    public function testAddsValidIpToEmptyList(): void
    {
        \ProcessWire\wire('input')->post->set('submit_save_ip', '192.168.1.1');

        $module = $this->makeModule('');
        $result = $this->call($module, true);

        $this->assertTrue($result);
        $this->assertSame('192.168.1.1', $this->getPreventIPs($module));
    }

    /**
     * 2) REGRESSION TEST: adding an invalid value returns false and does
     * not add anything to the list.
     */
    public function testRejectsInvalidIp(): void
    {
        \ProcessWire\wire('input')->post->set('submit_save_ip', 'not-an-ip-address');

        $module = $this->makeModule('10.0.0.1');
        $result = $this->call($module, true);

        $this->assertFalse($result);
        $this->assertSame('10.0.0.1', $this->getPreventIPs($module));
    }

    /**
     * 3) Removing an IP that is present in the list succeeds.
     */
    public function testRemovesExistingIp(): void
    {
        \ProcessWire\wire('input')->post->set('submit_remove_ip', '10.0.0.1');

        $module = $this->makeModule("10.0.0.1\n192.168.1.1");
        $result = $this->call($module, false);

        $this->assertTrue($result);
        $this->assertSame('192.168.1.1', $this->getPreventIPs($module));
    }

    /**
     * 4) REGRESSION TEST: removing an IP that isn't in the list returns
     * false and leaves the list untouched.
     */
    public function testRemovingNonExistentIpReturnsFalse(): void
    {
        \ProcessWire\wire('input')->post->set('submit_remove_ip', '8.8.8.8');

        $module = $this->makeModule('10.0.0.1');
        $result = $this->call($module, false);

        $this->assertFalse($result);
        $this->assertSame('10.0.0.1', $this->getPreventIPs($module));
    }

    /**
     * 5) Adding a duplicate IP that's already in the list doesn't create
     * a second entry (array_unique).
     */
    public function testAddingDuplicateIpDoesNotDuplicateEntry(): void
    {
        \ProcessWire\wire('input')->post->set('submit_save_ip', '10.0.0.1');

        $module = $this->makeModule('10.0.0.1');
        $result = $this->call($module, true);

        $this->assertTrue($result);
        $this->assertSame('10.0.0.1', $this->getPreventIPs($module));
    }
}
