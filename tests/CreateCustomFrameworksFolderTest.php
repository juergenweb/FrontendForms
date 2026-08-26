<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for FrontendForms::createCustomFrameworksFolder(), used by
 * ___install() and ___upgrade() to ensure the folder for custom CSS
 * framework class definitions exists.
 */
final class CreateCustomFrameworksFolderTest extends TestCase
{
    /** @var string[] Temp directories created during the test, removed in tearDown(). */
    private array $tempDirs = [];

    private function makeModule(string $customFrameworksPath): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();

        $prop = new ReflectionProperty(FrontendForms::class, 'customFrameworksPath');
        $prop->setAccessible(true);
        $prop->setValue($module, $customFrameworksPath);

        return $module;
    }

    private function callCreateCustomFrameworksFolder(FrontendForms $module): void
    {
        $method = new ReflectionMethod(FrontendForms::class, 'createCustomFrameworksFolder');
        $method->setAccessible(true);
        $method->invoke($module);
    }

    private function makeTempPath(): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-customframeworks-' . uniqid() . '/';
        $this->tempDirs[] = $dir;
        return $dir;
    }

    protected function tearDown(): void
    {
        foreach ($this->tempDirs as $dir) {
            @rmdir($dir);
        }
        $this->tempDirs = [];
        parent::tearDown();
    }

    /**
     * 1) When the folder doesn't exist yet, it gets created.
     */
    public function testCreatesFolderWhenItDoesNotExist(): void
    {
        $path = $this->makeTempPath();
        $this->assertDirectoryDoesNotExist($path);

        $module = $this->makeModule($path);
        $this->callCreateCustomFrameworksFolder($module);

        $this->assertDirectoryExists($path);
    }

    /**
     * 2) When the folder already exists, calling this again is a safe
     * no-op - no exception, folder (and its contents) untouched.
     */
    public function testExistingFolderIsLeftUntouched(): void
    {
        $path = $this->makeTempPath();
        mkdir($path, 0777, true);
        file_put_contents($path . 'custom-framework.json', '{"existing":"content"}');

        $module = $this->makeModule($path);
        $this->callCreateCustomFrameworksFolder($module);

        $this->assertDirectoryExists($path);
        $this->assertFileExists($path . 'custom-framework.json');
        $this->assertSame('{"existing":"content"}', file_get_contents($path . 'custom-framework.json'));
    }
}
