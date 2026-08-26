<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\EmailHelper;
use PHPUnit\Framework\TestCase;
use ProcessWire\NullPage;

/**
 * Unit tests for EmailHelper.
 *
 * getUserByEmail()/emailExists() query the real ProcessWire users
 * database - tests here rely on a fixed, clearly-fake email address that
 * is assumed not to exist as a real user in the test environment (same
 * assumption used elsewhere in this session for environment-dependent
 * checks).
 */
final class EmailHelperTest extends TestCase
{
    private const NONEXISTENT_EMAIL = 'definitely-not-a-real-user-xyz123@nonexistent-domain-xyz.test';

    // --- sanitizeEmail() ---

    /**
     * 1) A valid email is trimmed and returned.
     */
    public function testSanitizeEmailReturnsTrimmedValidEmail(): void
    {
        $helper = new EmailHelper();

        $this->assertSame('test@example.com', $helper->sanitizeEmail('  test@example.com  '));
    }

    /**
     * 2) An empty (or whitespace-only) string sanitizes to an empty
     * string.
     */
    public function testSanitizeEmailWithEmptyStringReturnsEmpty(): void
    {
        $helper = new EmailHelper();

        $this->assertSame('', $helper->sanitizeEmail(''));
        $this->assertSame('', $helper->sanitizeEmail('   '));
    }

    /**
     * 3) An invalid (non-email) string sanitizes to an empty string, since
     * ProcessWire's own email sanitizer rejects it before the selector
     * sanitizer runs.
     */
    public function testSanitizeEmailWithInvalidEmailReturnsEmpty(): void
    {
        $helper = new EmailHelper();

        $this->assertSame('', $helper->sanitizeEmail('not-an-email'));
    }

    // --- getUserByEmail() ---

    /**
     * 4) An empty email returns a NullPage without querying the database.
     */
    public function testGetUserByEmailWithEmptyEmailReturnsNullPage(): void
    {
        $helper = new EmailHelper();

        $this->assertInstanceOf(NullPage::class, $helper->getUserByEmail(''));
    }

    /**
     * 5) A well-formed but non-existent email returns a page with id 0
     * (ProcessWire's convention for "not found", whether that's a
     * NullPage or an empty PageArray result).
     */
    public function testGetUserByEmailWithNonExistentEmailReturnsZeroId(): void
    {
        $helper = new EmailHelper();

        $result = $helper->getUserByEmail(self::NONEXISTENT_EMAIL);

        $this->assertSame(0, $result->id);
    }

    // --- emailExists() ---

    /**
     * 6) An empty email does not exist.
     */
    public function testEmailExistsWithEmptyEmailReturnsFalse(): void
    {
        $helper = new EmailHelper();

        $this->assertFalse($helper->emailExists(''));
    }

    /**
     * 7) A well-formed but non-existent email returns false.
     */
    public function testEmailExistsWithNonExistentEmailReturnsFalse(): void
    {
        $helper = new EmailHelper();

        $this->assertFalse($helper->emailExists(self::NONEXISTENT_EMAIL));
    }
}
