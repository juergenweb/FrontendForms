<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\EmailHelper;
use FrontendForms\LoginHelper;
use FrontendForms\UsernameHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for LoginHelper.
 *
 * validateLogin() ultimately queries the real ProcessWire users database -
 * tests here rely on a clearly-fake username/email that is assumed not to
 * exist as a real user in the test environment.
 */
final class LoginHelperTest extends TestCase
{
    private function makeHelper(): LoginHelper
    {
        return new LoginHelper(new UsernameHelper(), new EmailHelper());
    }

    // --- validateLogin() ---

    /**
     * 1) An empty login value fails immediately.
     */
    public function testValidateLoginFailsForEmptyLogin(): void
    {
        $helper = $this->makeHelper();

        $this->assertFalse($helper->validateLogin('', 'somepassword', 'username'));
    }

    /**
     * 2) An empty password fails immediately.
     */
    public function testValidateLoginFailsForEmptyPassword(): void
    {
        $helper = $this->makeHelper();

        $this->assertFalse($helper->validateLogin('someuser', '', 'username'));
    }

    /**
     * 3) A non-existent username, with both login and password given,
     * fails (runs the timing-safe dummy check, but still returns false).
     */
    public function testValidateLoginFailsForNonExistentUsername(): void
    {
        $helper = $this->makeHelper();

        $this->assertFalse($helper->validateLogin('definitely-not-a-real-username-xyz123', 'somepassword', 'username'));
    }

    /**
     * 4) A non-existent email, with both login and password given, fails.
     */
    public function testValidateLoginFailsForNonExistentEmail(): void
    {
        $helper = $this->makeHelper();

        $this->assertFalse($helper->validateLogin('definitely-not-a-real-user-xyz123@nonexistent-domain-xyz.test', 'somepassword', 'email'));
    }

    /**
     * 5) An unsupported login type throws an InvalidArgumentException.
     */
    public function testValidateLoginThrowsForUnsupportedType(): void
    {
        $helper = $this->makeHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->validateLogin('someuser', 'somepassword', 'phone');
    }

    // --- createSessionForLoginAttempts() ---

    /**
     * 6) Recording a login attempt does not throw, and results in the
     * session holding at least one recorded attempt for the given field.
     */
    public function testCreateSessionForLoginAttemptsRecordsAttempt(): void
    {
        $helper = $this->makeHelper();
        $fieldName = 'test-login-attempts-' . uniqid();

        $helper->createSessionForLoginAttempts($fieldName, 'username');

        $attempts = $helper->wire('session')->get($fieldName);

        $this->assertIsArray($attempts);
        $this->assertCount(1, $attempts);
        $this->assertSame('username', $attempts[0]['type']);
    }

    /**
     * 7) More than 20 recorded attempts are trimmed down to the most
     * recent 20.
     */
    public function testCreateSessionForLoginAttemptsCapsAtTwenty(): void
    {
        $helper = $this->makeHelper();
        $fieldName = 'test-login-attempts-cap-' . uniqid();

        for ($i = 0; $i < 25; $i++) {
            $helper->createSessionForLoginAttempts($fieldName, 'username');
        }

        $attempts = $helper->wire('session')->get($fieldName);

        $this->assertCount(20, $attempts);
    }
}
