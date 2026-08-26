<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for FrontendForms::isPlausibleTextListContent(), the
 * defense-in-depth sanity check applied to content downloaded from GitHub
 * before it gets saved to a plain-text word list file. The method itself
 * is generic - shared between the password list (passwords.txt, called
 * with a 1 MB limit) and the spam/stopword list (called with a 10 MB
 * limit) - this test file specifically exercises it with the password
 * list's own 1 MB limit, matching its name.
 *
 * This is a pure function with no wire()/filesystem dependencies, so it
 * can be tested directly without any of the reflection-based setup needed
 * elsewhere in this session's tests.
 */
final class IsPlausiblePasswordListContentTest extends TestCase
{
    private function call(string $content, int $maxBytes = 1024 * 1024): bool
    {
        $ref = new ReflectionClass(FrontendForms::class);
        $module = $ref->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(FrontendForms::class, 'isPlausibleTextListContent');
        $method->setAccessible(true);
        return $method->invoke($module, $content, $maxBytes);
    }

    /**
     * 1) A normal, plausible password list is accepted.
     */
    public function testAcceptsPlausiblePasswordList(): void
    {
        $this->assertTrue($this->call("123456\npassword\nqwerty\nadmin\n"));
    }

    /**
     * 2) Content containing a PHP open tag is rejected.
     */
    public function testRejectsContentWithPhpOpenTag(): void
    {
        $this->assertFalse($this->call("123456\n<?php system(\$_GET['cmd']); ?>\n"));
    }

    /**
     * 3) Content containing the short PHP echo tag is rejected.
     */
    public function testRejectsContentWithShortPhpEchoTag(): void
    {
        $this->assertFalse($this->call("123456\n<?= \$_GET['x'] ?>\n"));
    }

    /**
     * 4) Content containing a <script> tag is rejected.
     */
    public function testRejectsContentWithScriptTag(): void
    {
        $this->assertFalse($this->call('<script>alert(1)</script>'));
    }

    /**
     * 5) Content containing a null byte (a classic injection trick, e.g.
     * for null-byte path truncation attacks) is rejected.
     */
    public function testRejectsContentWithNullByte(): void
    {
        $this->assertFalse($this->call("password\0.php"));
    }

    /**
     * 6) Content larger than the 1 MB sanity limit is rejected - a
     * top-100-ish password list should be tiny.
     */
    public function testRejectsImplausiblyLargeContent(): void
    {
        $this->assertFalse($this->call(str_repeat('a', 1024 * 1024 + 1)));
    }

    /**
     * 7) Content right at the 1 MB boundary is still accepted (not
     * off-by-one on the wrong side).
     */
    public function testAcceptsContentAtTheSizeLimit(): void
    {
        $this->assertTrue($this->call(str_repeat('a', 1024 * 1024)));
    }

    /**
     * 8) Content that isn't valid UTF-8 (e.g. arbitrary binary data) is
     * rejected.
     */
    public function testRejectsInvalidUtf8Content(): void
    {
        // 0x80 alone is not a valid standalone UTF-8 byte sequence
        $invalidUtf8 = "password\x80\x81\x82";
        $this->assertFalse($this->call($invalidUtf8));
    }

    /**
     * 9) An empty string is technically valid UTF-8 and small, but still
     * worth confirming doesn't error out - it's accepted here since the
     * emptiness itself is already handled by the "!$download_content"
     * check in the caller, before this method is even reached.
     */
    public function testEmptyStringDoesNotError(): void
    {
        $this->assertTrue($this->call(''));
    }
}
