<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;

/**
 * Unit tests for FrontendForms::getCSSClassFile().
 *
 * This is a static method that internally reads wire('config')->paths->
 * siteModules - a fixed, global ProcessWire path that isn't something this
 * test suite can safely override (see the wire('config')->paths->root
 * experiment in CreateRemoveCaptchaImageFileTest, which is exactly the
 * kind of risk being avoided here). Because of that, these tests focus on
 * what IS fully controllable and safe:
 * - the $customFrameworksPath parameter, which the method takes directly
 *   and which can point at a real, isolated temp folder
 * - the "found nowhere" fallback path, whose returned string can be
 *   asserted on without the underlying none.json file needing to actually
 *   exist for the assertion itself
 * - the fixed-separator regression (no DIRECTORY_SEPARATOR anywhere in the
 *   returned paths)
 *
 * The "framework found inside the module's own /frameworks/ folder"
 * branch is not covered here, since exercising it safely would require
 * controlling wire('config')->paths->siteModules.
 */
final class GetCssClassFileTest extends TestCase
{
    /** @var string[] Temp files/dirs created during the test, removed in tearDown(). */
    private array $tempPaths = [];

    private function makeTempFrameworksDir(): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-customfw-' . uniqid() . '/';
        mkdir($dir, 0777, true);
        $this->tempPaths[] = $dir;
        return $dir;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tempPaths) as $path) {
            if (is_dir($path)) {
                $this->removeDirRecursively($path);
            } else {
                @unlink($path);
            }
        }
        $this->tempPaths = [];
        parent::tearDown();
    }

    private function removeDirRecursively(string $dir): void
    {
        $items = scandir($dir);
        if ($items === false) {
            return;
        }
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_dir($path)) {
                $this->removeDirRecursively($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }

    /**
     * 1) A framework file that exists in the custom frameworks folder is
     * found and its full path returned.
     */
    public function testReturnsCustomFrameworkPathWhenFileExistsThere(): void
    {
        $customPath = $this->makeTempFrameworksDir();
        $frameworkFile = 'my-framework-' . uniqid() . '.json';
        file_put_contents($customPath . $frameworkFile, '{}');

        $result = FrontendForms::getCSSClassFile($frameworkFile, $customPath);

        $this->assertSame($customPath . $frameworkFile, $result);
    }

    /**
     * 2) When the requested framework doesn't exist anywhere (neither in
     * the module's own frameworks folder nor in the custom folder), the
     * method falls back to the module's none.json.
     */
    public function testFallsBackToNoneJsonWhenFrameworkNotFoundAnywhere(): void
    {
        $customPath = $this->makeTempFrameworksDir(); // empty - nothing in it

        $result = FrontendForms::getCSSClassFile('does-not-exist-anywhere-' . uniqid() . '.json', $customPath);

        $this->assertStringEndsWith('frameworks/none.json', $result);
    }

    /**
     * 3) REGRESSION TEST for the fixed bug: the path portion the method
     * itself constructs (module path + "frameworks/" + filename) never
     * contains a backslash, regardless of OS (the method previously mixed
     * DIRECTORY_SEPARATOR, which is "\" on Windows, into otherwise
     * forward-slash-only ProcessWire paths).
     *
     * Only the fallback case is checked here, not the "found in custom
     * frameworks path" case - $customFrameworksPath is caller-supplied
     * and passed through untouched, so it may legitimately contain
     * backslashes on Windows (e.g. from sys_get_temp_dir()) without that
     * being a bug in getCSSClassFile() itself.
     */
    public function testFallbackPathNeverContainsBackslashes(): void
    {
        $customPath = $this->makeTempFrameworksDir(); // empty - nothing in it

        $fallback = FrontendForms::getCSSClassFile('does-not-exist-' . uniqid() . '.json', $customPath);

        $this->assertStringNotContainsString('\\', $fallback);
    }

    /**
     * 4) The custom frameworks folder takes priority check only applies
     * when the file actually exists there - a non-existent custom path
     * combined with a non-existent framework still correctly falls back
     * to none.json rather than erroring.
     */
    public function testNonExistentCustomFrameworksPathFallsBackGracefully(): void
    {
        $nonExistentCustomPath = sys_get_temp_dir() . '/frontendforms-does-not-exist-' . uniqid() . '/';

        $result = FrontendForms::getCSSClassFile('anything-' . uniqid() . '.json', $nonExistentCustomPath);

        $this->assertStringEndsWith('frameworks/none.json', $result);
    }
}
