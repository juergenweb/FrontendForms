<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for FrontendForms::getDataFromGitHub().
 *
 * To avoid any real network dependency (slow, flaky, and hitting the real
 * GitHub API from a test run), these tests point $url at local temp files
 * instead of a real https:// URL. file_get_contents() reads local files
 * just as well as remote ones, and the 'http' stream context options
 * passed by getDataFromGitHub() are simply irrelevant/unused for local
 * file access - so the method's actual logic (does file_get_contents()
 * succeed, is the result valid JSON, what gets returned) is exercised
 * exactly as it would be for a real GitHub response, without ever making
 * a real HTTP request.
 *
 * Session/log side effects (the warning message, the error log entry) on
 * the failure path are intentionally not asserted on here - there's no
 * already-established, verified pattern elsewhere in this test suite for
 * checking wire('session') notices, and introducing an unverified one
 * risks the same kind of trouble as the wire('config')->paths->root
 * experiment in the CreateRemoveCaptchaImageFileTest. The return value
 * (the part every caller actually depends on) is fully covered instead.
 */
final class GetDataFromGitHubTest extends TestCase
{
    /** @var string[] Temp files created during the test, removed in tearDown(). */
    private array $tempFiles = [];

    private function makeModule(): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        return $ref->newInstanceWithoutConstructor();
    }

    private function callGetDataFromGitHub(FrontendForms $module, string $url): mixed
    {
        $method = new ReflectionMethod(FrontendForms::class, 'getDataFromGitHub');
        $method->setAccessible(true);
        return $method->invoke($module, $url);
    }

    private function makeTempFile(string $content): string
    {
        $path = sys_get_temp_dir() . '/frontendforms-github-' . uniqid() . '.json';
        file_put_contents($path, $content);
        $this->tempFiles[] = $path;
        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }
        $this->tempFiles = [];
        parent::tearDown();
    }

    /**
     * 1) A successful fetch of valid JSON returns the decoded object.
     */
    public function testReturnsDecodedJsonObjectOnSuccess(): void
    {
        $path = $this->makeTempFile(json_encode([
            'download_url' => 'https://example.com/passwords.txt',
            'name' => 'passwords.txt',
        ]));

        $module = $this->makeModule();
        $result = $this->callGetDataFromGitHub($module, $path);

        $this->assertIsObject($result);
        $this->assertSame('https://example.com/passwords.txt', $result->download_url);
        $this->assertSame('passwords.txt', $result->name);
    }

    /**
     * 2) A successful fetch of a JSON array (as returned by GitHub's
     * commits API, used for LAST_PASSWORDS_MODIFICATION_URL) returns a
     * decoded array of objects, matching how downloadPasswords() accesses
     * $github_data[0]->commit->author->date.
     */
    public function testReturnsDecodedJsonArrayForArrayResponses(): void
    {
        $path = $this->makeTempFile(json_encode([
            ['commit' => ['author' => ['date' => '2026-01-15T10:00:00Z']]],
        ]));

        $module = $this->makeModule();
        $result = $this->callGetDataFromGitHub($module, $path);

        $this->assertIsArray($result);
        $this->assertSame('2026-01-15T10:00:00Z', $result[0]->commit->author->date);
    }

    /**
     * 3) If the URL/path cannot be fetched at all (file_get_contents()
     * fails), the method returns false rather than throwing.
     */
    public function testReturnsFalseWhenFetchFails(): void
    {
        $nonExistent = sys_get_temp_dir() . '/frontendforms-does-not-exist-' . uniqid() . '.json';

        $module = $this->makeModule();
        $result = $this->callGetDataFromGitHub($module, $nonExistent);

        $this->assertFalse($result);
    }

    /**
     * 4) If the fetch succeeds but the content isn't valid JSON,
     * json_decode() returns null, and that null is passed through as the
     * method's result (not silently converted to false) - callers using a
     * loose truthiness check (if ($result)) still correctly treat this as
     * "no usable data", same as the false case.
     */
    public function testReturnsNullForSuccessfulFetchOfInvalidJson(): void
    {
        $path = $this->makeTempFile('this is not valid json {{{');

        $module = $this->makeModule();
        $result = $this->callGetDataFromGitHub($module, $path);

        $this->assertNull($result);
    }

    /**
     * 5) An empty (but successfully fetched) response is treated the same
     * as a failed fetch, since file_get_contents() returning an empty
     * string is falsy in the "if ($file)" check.
     */
    public function testTreatsEmptyContentAsFailure(): void
    {
        $path = $this->makeTempFile('');

        $module = $this->makeModule();
        $result = $this->callGetDataFromGitHub($module, $path);

        $this->assertFalse($result);
    }
}
