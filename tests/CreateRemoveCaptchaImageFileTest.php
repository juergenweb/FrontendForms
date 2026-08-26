<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for FrontendForms::createRemoveCaptchaImageFile(), used by
 * ___install()/___upgrade() (copying captchaimage.php from the module
 * folder to the site root - the module's own copy is deliberately never
 * deleted, see the method's own docblock) and ___uninstall() (removing
 * the root copy again).
 *
 * CAUTION: unlike createFilesDir()/createCustomFrameworksFolder(), this
 * method reads wire('config')->paths->root directly rather than taking it
 * as a parameter or instance property that can simply be overridden via
 * reflection. These tests temporarily reassign wire('config')->paths->root
 * to an isolated temp directory for the duration of each test and restore
 * the original value in tearDown() (using a try/finally-safe pattern),
 * so the real site root is never touched even if a test fails partway
 * through. This relies on ProcessWire's Paths object supporting property
 * reassignment - if that assumption turns out to be wrong in your
 * installation, these specific tests may need adjusting; the other tests
 * in this session's test suite don't depend on this pattern.
 */
final class CreateRemoveCaptchaImageFileTest extends TestCase
{
    /** @var string[] Temp directories created during the test, removed in tearDown(). */
    private array $tempDirs = [];

    private ?string $originalRoot = null;

    private function makeModule(string $modulePath): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();

        $prop = new ReflectionProperty(FrontendForms::class, 'modulePath');
        $prop->setAccessible(true);
        $prop->setValue($module, $modulePath);

        return $module;
    }

    private function callCreateRemoveCaptchaImageFile(FrontendForms $module, bool $install): void
    {
        $method = new ReflectionMethod(FrontendForms::class, 'createRemoveCaptchaImageFile');
        $method->setAccessible(true);
        $method->invoke($module, $install);
    }

    private function makeTempDir(string $suffix = ''): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-captchafile-' . uniqid() . $suffix . '/';
        mkdir($dir, 0777, true);
        $this->tempDirs[] = $dir;
        return $dir;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalRoot = \ProcessWire\wire('config')->paths->root;
    }

    protected function tearDown(): void
    {
        if ($this->originalRoot !== null) {
            \ProcessWire\wire('config')->paths->root = $this->originalRoot;
        }
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
     * 1) On install (install=true), the module's
     * Formelements/Captcha/captchaimage.php is copied to the site root.
     * Per this method's own documented design, the module's copy is the
     * permanent, authoritative source and is deliberately never deleted -
     * this is a copy, not a move - so the source file still exists
     * afterwards too.
     */
    public function testInstallDirectionCopiesFileFromModuleToRoot(): void
    {
        $modulePath = $this->makeTempDir('-module');
        mkdir($modulePath . 'Formelements/Captcha', 0777, true);
        file_put_contents($modulePath . 'Formelements/Captcha/captchaimage.php', '<?php // captcha');

        $root = $this->makeTempDir('-root');
        \ProcessWire\wire('config')->paths->root = $root;

        $module = $this->makeModule($modulePath);
        $this->callCreateRemoveCaptchaImageFile($module, true);

        $this->assertFileExists($root . 'captchaimage.php');
        $this->assertFileExists($modulePath . 'Formelements/Captcha/captchaimage.php');
    }

    /**
     * NOTE: a "copy fails" regression test (matching the one in
     * CreateFilesDirTest) was deliberately left out here. Simulating a
     * guaranteed copy failure would require pointing
     * wire('config')->paths->root at a genuinely non-existent path, which
     * risks destabilizing ProcessWire internals that rely on paths->root
     * being a valid, existing directory - beyond just this test failing.
     * The same "only unlink on successful copy" logic is already covered,
     * without that risk, by CreateFilesDirTest::
     * testDoesNotRemoveSourceWhenCopyFails().
     */

    /**
     * 3) On uninstall (install=false), only the root copy is removed.
     * The module's own file is left untouched - per this method's
     * documented design it was never deleted during install in the
     * first place, so there is nothing to restore/copy back here.
     */
    public function testUninstallDirectionRemovesOnlyTheRootCopy(): void
    {
        $modulePath = $this->makeTempDir('-module');
        mkdir($modulePath . 'Formelements/Captcha', 0777, true);
        // the module's own copy is always present at this point in a
        // real install/uninstall cycle, since install() never deletes it
        file_put_contents($modulePath . 'Formelements/Captcha/captchaimage.php', '<?php // captcha');

        $root = $this->makeTempDir('-root');
        file_put_contents($root . 'captchaimage.php', '<?php // captcha');
        \ProcessWire\wire('config')->paths->root = $root;

        $module = $this->makeModule($modulePath);
        $this->callCreateRemoveCaptchaImageFile($module, false);

        $this->assertFileExists($modulePath . 'Formelements/Captcha/captchaimage.php');
        $this->assertFileDoesNotExist($root . 'captchaimage.php');
    }
}
