<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;

/**
 * Unit tests for FrontendForms::createFilesDir(), used by ___install() and
 * ___upgrade() to move the email template images from inside the module
 * folder to site/assets/files/FrontendForms/.
 *
 * createFilesDir() takes $from/$to directly as parameters and only relies
 * on wire('files') internally (no instance properties), so - unlike
 * createRemoveCaptchaImageFile() - it can be tested without needing to
 * touch any real, fixed ProcessWire path.
 */
final class CreateFilesDirTest extends TestCase
{
    /** @var string[] Temp directories created during the test, removed in tearDown(). */
    private array $tempDirs = [];

    private function makeModule(): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();
        return $module;
    }

    private function callCreateFilesDir(FrontendForms $module, string $from, string $to): void
    {
        $method = new ReflectionMethod(FrontendForms::class, 'createFilesDir');
        $method->setAccessible(true);
        $method->invoke($module, $from, $to);
    }

    private function makeTempDir(string $suffix = ''): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-createfilesdir-' . uniqid() . $suffix . '/';
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
     * 1) On success, files are copied to the destination and the source
     * folder is removed afterwards.
     */
    public function testCopiesFilesAndRemovesSourceOnSuccess(): void
    {
        $from = $this->makeTempDir('-source');
        $to = $this->makeTempDir('-dest');
        // the destination itself must not already exist for this test -
        // createFilesDir() is responsible for creating it
        rmdir($to);

        file_put_contents($from . 'logo.png', 'image data');

        $module = $this->makeModule();
        $this->callCreateFilesDir($module, $from, $to);

        $this->assertFileExists($to . 'logo.png');
        $this->assertDirectoryDoesNotExist($from);
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: if copying fails, the source
     * folder must NOT be removed - otherwise the files would be lost from
     * both locations. Simulated here by pointing $to at a path whose
     * parent folder doesn't exist, which makes copy() fail.
     */
    public function testDoesNotRemoveSourceWhenCopyFails(): void
    {
        $from = $this->makeTempDir('-source');
        file_put_contents($from . 'logo.png', 'image data');

        // a destination path that copy() cannot possibly succeed against
        $to = '/nonexistent-parent-dir-' . uniqid() . '/deeply/nested/dest/';

        $module = $this->makeModule();
        $this->callCreateFilesDir($module, $from, $to);

        // the source must still be intact - nothing was successfully copied
        $this->assertFileExists($from . 'logo.png');
    }

    /**
     * 3) If the source folder doesn't exist at all, the destination folder
     * still gets created (that's the method's first, unconditional step),
     * but stays empty - no exception, no attempt to copy anything.
     */
    public function testNonExistentSourceLeavesDestinationEmpty(): void
    {
        $to = $this->makeTempDir('-dest');
        rmdir($to);
        $from = sys_get_temp_dir() . '/frontendforms-does-not-exist-' . uniqid() . '/';

        $module = $this->makeModule();
        $this->callCreateFilesDir($module, $from, $to);

        $this->assertDirectoryExists($to);
        $this->assertSame([], array_diff(scandir($to), ['.', '..']));
    }

    /**
     * 4) An empty source folder (exists, but contains no files) does not
     * get removed - matching the existing "if ($files) { ... }" guard,
     * which only proceeds when find() actually returns files.
     */
    public function testEmptySourceFolderIsNotRemoved(): void
    {
        $from = $this->makeTempDir('-empty-source');
        $to = $this->makeTempDir('-dest');
        rmdir($to);

        $module = $this->makeModule();
        $this->callCreateFilesDir($module, $from, $to);

        $this->assertDirectoryExists($from);
    }

    /**
     * 5) If the destination folder already exists, it is reused rather
     * than causing an error.
     */
    public function testReusesExistingDestinationFolder(): void
    {
        $from = $this->makeTempDir('-source');
        $to = $this->makeTempDir('-dest'); // already exists
        file_put_contents($from . 'logo.png', 'image data');
        file_put_contents($to . 'existing-file.txt', 'already here');

        $module = $this->makeModule();
        $this->callCreateFilesDir($module, $from, $to);

        $this->assertFileExists($to . 'logo.png');
        // the pre-existing, unrelated file in the destination is untouched
        $this->assertFileExists($to . 'existing-file.txt');
    }
}
