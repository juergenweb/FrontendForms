<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\UsernameHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for UsernameHelper.
 */
final class UsernameHelperTest extends TestCase
{
    // --- normalizeUsername() ---

    /**
     * 1) A mixed-case, padded username is trimmed and lowercased.
     */
    public function testNormalizeUsernameTrimsAndLowercases(): void
    {
        $helper = new UsernameHelper();

        $this->assertSame('johndoe', $helper->normalizeUsername('  JohnDoe  '));
    }

    // --- sanitizeUsername() ---

    /**
     * 2) An empty (or whitespace-only) username sanitizes to an empty
     * string.
     */
    public function testSanitizeUsernameWithEmptyStringReturnsEmpty(): void
    {
        $helper = new UsernameHelper();

        $this->assertSame('', $helper->sanitizeUsername(''));
        $this->assertSame('', $helper->sanitizeUsername('   '));
    }

    /**
     * 3) A valid username is sanitized without throwing.
     */
    public function testSanitizeUsernameWithValidUsername(): void
    {
        $helper = new UsernameHelper();

        $this->assertSame('johndoe', $helper->sanitizeUsername('johndoe'));
    }

    // --- usernameExists() ---

    /**
     * 4) An empty username never exists.
     */
    public function testUsernameExistsWithEmptyUsernameReturnsFalse(): void
    {
        $helper = new UsernameHelper();

        $this->assertFalse($helper->usernameExists(''));
    }

    /**
     * 5) A clearly non-existent username returns false.
     */
    public function testUsernameExistsWithNonExistentUsernameReturnsFalse(): void
    {
        $helper = new UsernameHelper();

        $this->assertFalse($helper->usernameExists('definitely-not-a-real-username-xyz123'));
    }
}
