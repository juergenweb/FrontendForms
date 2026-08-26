<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\PasswordHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for PasswordHelper.
 */
final class PasswordHelperTest extends TestCase
{
    // --- matchesCurrentPassword() ---

    /**
     * 1) A guest (not logged in) user never matches any password.
     */
    public function testMatchesCurrentPasswordReturnsFalseForGuest(): void
    {
        $helper = new PasswordHelper();

        $this->assertFalse($helper->matchesCurrentPassword('anypassword'));
    }

    // --- getPasswordRequirements() ---

    /**
     * 2) The requirements are returned as an array, reflecting the live
     * "pass" field's configured requirements (whatever they may be in
     * this environment).
     */
    public function testGetPasswordRequirementsReturnsArray(): void
    {
        $helper = new PasswordHelper();

        $this->assertIsArray($helper->getPasswordRequirements());
    }
}
