<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for FrontendForms::removeOrphanedDataPath(), the cleanup
 * helper used by ___upgrade() (driven by migrateV2V3.json) to remove files and
 * folders left over at their old location from a previous module version's
 * directory layout.
 *
 * The module instance is created via newInstanceWithoutConstructor() to
 * avoid running FrontendForms::__construct() (which calls
 * $modules->getConfig(), checks the GD library, etc. - all irrelevant to
 * this specific method and an unnecessary risk/dependency in a unit test).
 * modulePath is then set directly via reflection to point at a unique,
 * isolated temp directory for each test, so nothing here ever touches the
 * real module installation on disk.
 */
final class RemoveOrphanedDataPathTest extends TestCase
{
    /** @var string[] Temp directories created during the test, removed in tearDown(). */
    private array $tempDirs = [];

    private function makeModule(string $modulePath): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();

        $modulePathProp = new ReflectionProperty(FrontendForms::class, 'modulePath');
        $modulePathProp->setAccessible(true);
        $modulePathProp->setValue($module, $modulePath);

        return $module;
    }

    private function callRemoveOrphanedDataPath(FrontendForms $module, string $relativePath): void
    {
        $method = new ReflectionMethod(FrontendForms::class, 'removeOrphanedDataPath');
        $method->setAccessible(true);
        $method->invoke($module, $relativePath);
    }

    private function makeTempDir(): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-orphaned-path-' . uniqid() . '/';
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;
        return $dir;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            $this->removeDirRecursively($dir);
        }
        $this->tempDirs = [];
        parent::tearDown();
    }

    private function removeDirRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
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
     * 1) A single orphaned file directly inside the module root is removed.
     */
    public function testRemovesSingleFileInRoot(): void
    {
        $modulePath = $this->makeTempDir();
        file_put_contents($modulePath . 'passwords.txt', 'old content');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'passwords.txt');

        $this->assertFileDoesNotExist($modulePath . 'passwords.txt');
    }

    /**
     * 2) A folder and all of its nested contents are removed recursively.
     */
    public function testRemovesFolderRecursivelyWithAllContents(): void
    {
        $modulePath = $this->makeTempDir();
        mkdir($modulePath . 'old-folder/nested', 0777, true);
        file_put_contents($modulePath . 'old-folder/file1.txt', 'x');
        file_put_contents($modulePath . 'old-folder/nested/file2.txt', 'x');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'old-folder');

        $this->assertDirectoryDoesNotExist($modulePath . 'old-folder');
    }

    /**
     * 3) A folder path with a trailing slash (as used in migrateV2V3.json, e.g.
     * "img/") is also removed correctly.
     */
    public function testRemovesFolderWithTrailingSlashInPath(): void
    {
        $modulePath = $this->makeTempDir();
        mkdir($modulePath . 'img', 0777, true);
        file_put_contents($modulePath . 'img/logo.png', 'x');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'img/');

        $this->assertDirectoryDoesNotExist($modulePath . 'img');
    }

    /**
     * 4) A file at a nested (non-root) path is removed correctly, not just
     * files directly inside the module root.
     */
    public function testRemovesFileAtNestedPath(): void
    {
        $modulePath = $this->makeTempDir();
        mkdir($modulePath . 'some/deep/path', 0777, true);
        file_put_contents($modulePath . 'some/deep/path/orphaned.txt', 'x');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'some/deep/path/orphaned.txt');

        $this->assertFileDoesNotExist($modulePath . 'some/deep/path/orphaned.txt');
        // the containing folder itself is untouched - only the file was targeted
        $this->assertDirectoryExists($modulePath . 'some/deep/path');
    }

    /**
     * 5) A leading slash on the given relative path (e.g. "/passwords.txt")
     * doesn't break resolution - ltrim() should normalize it away rather
     * than accidentally referencing the filesystem root.
     */
    public function testHandlesLeadingSlashInRelativePath(): void
    {
        $modulePath = $this->makeTempDir();
        file_put_contents($modulePath . 'passwords.txt', 'old content');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, '/passwords.txt');

        $this->assertFileDoesNotExist($modulePath . 'passwords.txt');
    }

    /**
     * 6) A path that doesn't exist at all is a safe no-op - no exception,
     * nothing else in the module folder is touched. This is the common
     * case for a fresh install or an already-cleaned-up installation.
     */
    public function testNonExistentPathIsSafeNoOp(): void
    {
        $modulePath = $this->makeTempDir();
        file_put_contents($modulePath . 'unrelated.txt', 'should stay');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'does-not-exist.txt');

        $this->assertFileExists($modulePath . 'unrelated.txt');
    }

    /**
     * 7) Removing one specific file/folder does not affect unrelated
     * sibling files - the cleanup is scoped exactly to the given path.
     */
    public function testDoesNotTouchUnrelatedSiblingFiles(): void
    {
        $modulePath = $this->makeTempDir();
        file_put_contents($modulePath . 'passwords.txt', 'x');
        file_put_contents($modulePath . 'stopwords.txt', 'x');
        mkdir($modulePath . 'data', 0777, true);
        file_put_contents($modulePath . 'data/passwords.txt', 'current version');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'passwords.txt');

        $this->assertFileDoesNotExist($modulePath . 'passwords.txt');
        $this->assertFileExists($modulePath . 'stopwords.txt');
        $this->assertFileExists($modulePath . 'data/passwords.txt');
        $this->assertSame('current version', file_get_contents($modulePath . 'data/passwords.txt'));
    }

    /**
     * 8) Calling removeOrphanedDataPath() a second time for an already
     * removed path is a safe no-op (idempotent) - important since
     * ___upgrade() could run again on a subsequent version bump.
     */
    public function testIsIdempotentOnRepeatedCalls(): void
    {
        $modulePath = $this->makeTempDir();
        file_put_contents($modulePath . 'passwords.txt', 'x');

        $module = $this->makeModule($modulePath);
        $this->callRemoveOrphanedDataPath($module, 'passwords.txt');
        // second call for the same, now-already-removed path must not throw
        $this->callRemoveOrphanedDataPath($module, 'passwords.txt');

        $this->assertFileDoesNotExist($modulePath . 'passwords.txt');
    }
}
