<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * A minimal test subclass that overrides getDataFromGitHub() to return a
 * controlled value directly, instead of making a real request to the
 * hardcoded self::TOP_PASSWORDS GitHub URL. This gives full, real-network-
 * free control over downloadPasswordsFromGitHub()'s behavior: the second
 * file_get_contents() call inside it (fetching $filedata->download_url)
 * still runs for real, but against a local temp file path set as
 * $mockGitHubResponse->download_url - exercising the actual download +
 * save logic without ever touching the real GitHub API.
 */
class TestableFrontendFormsForDownload extends FrontendForms
{
    public mixed $mockGitHubResponse = null;

    protected function getDataFromGitHub(string $url): mixed
    {
        return $this->mockGitHubResponse;
    }
}

/**
 * Unit tests for FrontendForms::downloadPasswordsFromGitHub().
 */
final class DownloadPasswordsFromGitHubTest extends TestCase
{
    /** @var string[] Temp files/dirs created during the test, removed in tearDown(). */
    private array $tempPaths = [];

    private function makeModule(string $passwordPath): TestableFrontendFormsForDownload
    {
        $ref = new ReflectionClass(TestableFrontendFormsForDownload::class);
        /** @var TestableFrontendFormsForDownload $module */
        $module = $ref->newInstanceWithoutConstructor();

        $prop = new ReflectionProperty(FrontendForms::class, 'passwordPath');
        $prop->setAccessible(true);
        $prop->setValue($module, $passwordPath);

        return $module;
    }

    private function callDownloadPasswordsFromGitHub(TestableFrontendFormsForDownload $module): bool
    {
        $method = new ReflectionMethod(FrontendForms::class, 'downloadPasswordsFromGitHub');
        $method->setAccessible(true);
        return $method->invoke($module);
    }

    private function makeTempPath(string $suffix = '.txt'): string
    {
        $path = sys_get_temp_dir() . '/frontendforms-dlpw-' . uniqid() . $suffix;
        $this->tempPaths[] = $path;
        return $path;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempPaths as $path) {
            @unlink($path);
        }
        $this->tempPaths = [];
        parent::tearDown();
    }

    /**
     * 1) A successful GitHub metadata fetch + successful download of the
     * actual file content saves it to passwordPath and returns true.
     */
    public function testSuccessfulDownloadSavesFileAndReturnsTrue(): void
    {
        $downloadUrl = $this->makeTempPath();
        file_put_contents($downloadUrl, "123456\npassword\nqwerty\n");

        $passwordPath = $this->makeTempPath('-saved.txt');

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = (object) ['download_url' => $downloadUrl];

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertTrue($result);
        $this->assertFileExists($passwordPath);
        $this->assertSame("123456\npassword\nqwerty\n", file_get_contents($passwordPath));
    }

    /**
     * 2) If getDataFromGitHub() itself failed (returned false, e.g. the
     * GitHub metadata request failed), the method returns false without
     * attempting anything further.
     */
    public function testReturnsFalseWhenGitHubMetadataFetchFails(): void
    {
        $passwordPath = $this->makeTempPath('-saved.txt');

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = false;

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($passwordPath);
    }

    /**
     * 3) REGRESSION TEST for the fixed bug: if getDataFromGitHub() returns
     * an array instead of an object (a plausible shape for some JSON
     * responses), the method must not fatally error trying to access
     * ->download_url on it - it should just return false.
     */
    public function testReturnsFalseWhenGitHubResponseIsAnArrayNotAnObject(): void
    {
        $passwordPath = $this->makeTempPath('-saved.txt');

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = ['download_url' => 'https://example.com/passwords.txt'];

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($passwordPath);
    }

    /**
     * 4) REGRESSION TEST for the fixed bug: if getDataFromGitHub() returns
     * an object without a (or with an empty) download_url property, the
     * method returns false rather than trying to fetch an empty/undefined
     * URL.
     */
    public function testReturnsFalseWhenDownloadUrlIsMissing(): void
    {
        $passwordPath = $this->makeTempPath('-saved.txt');

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = (object) ['name' => 'passwords.txt']; // no download_url

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($passwordPath);
    }

    /**
     * 5) If the metadata fetch succeeds, but the actual file download
     * (the second request, to download_url) fails, the method returns
     * false and doesn't create/overwrite passwordPath.
     */
    public function testReturnsFalseWhenActualFileDownloadFails(): void
    {
        $nonExistentDownloadUrl = sys_get_temp_dir() . '/frontendforms-dlpw-missing-' . uniqid() . '.txt';
        $passwordPath = $this->makeTempPath('-saved.txt');

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = (object) ['download_url' => $nonExistentDownloadUrl];

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertFalse($result);
        $this->assertFileDoesNotExist($passwordPath);
    }

    /**
     * 6) A successful download correctly overwrites an already-existing
     * passwords.txt with the freshly downloaded content.
     */
    public function testOverwritesExistingPasswordFile(): void
    {
        $downloadUrl = $this->makeTempPath();
        file_put_contents($downloadUrl, "new-password-list\n");

        $passwordPath = $this->makeTempPath('-saved.txt');
        file_put_contents($passwordPath, "old-password-list\n");

        $module = $this->makeModule($passwordPath);
        $module->mockGitHubResponse = (object) ['download_url' => $downloadUrl];

        $result = $this->callDownloadPasswordsFromGitHub($module);

        $this->assertTrue($result);
        $this->assertSame("new-password-list\n", file_get_contents($passwordPath));
    }
}
