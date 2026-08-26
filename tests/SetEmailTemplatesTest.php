<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * Unit tests for FrontendForms::setEmailTemplates(), which scans the
 * built-in and custom email template folders and populates
 * $this->emailTemplates / $this->email_template_files.
 */
final class SetEmailTemplatesTest extends TestCase
{
    /** @var string[] Temp directories created during the test, removed in tearDown(). */
    private array $tempDirs = [];

    private function makeModule(string $builtinPath, string $customPath): FrontendForms
    {
        $ref = new ReflectionClass(FrontendForms::class);
        /** @var FrontendForms $module */
        $module = $ref->newInstanceWithoutConstructor();

        $builtinProp = new ReflectionProperty(FrontendForms::class, 'emailTemplatesPath');
        $builtinProp->setAccessible(true);
        $builtinProp->setValue($module, $builtinPath);

        $customProp = new ReflectionProperty(FrontendForms::class, 'custom_emailTemplatesPath');
        $customProp->setAccessible(true);
        $customProp->setValue($module, $customPath);

        return $module;
    }

    private function callSetEmailTemplates(FrontendForms $module): void
    {
        $method = new ReflectionMethod(FrontendForms::class, 'setEmailTemplates');
        $method->setAccessible(true);
        $method->invoke($module);
    }

    private function getProp(FrontendForms $module, string $name)
    {
        $prop = new ReflectionProperty(FrontendForms::class, $name);
        $prop->setAccessible(true);
        return $prop->getValue($module);
    }

    private function makeTempDir(string $suffix): string
    {
        $dir = sys_get_temp_dir() . '/frontendforms-emailtemplates-' . uniqid() . $suffix . '/';
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
     * 1) Templates from both the built-in and custom folders are found
     * and listed, when they have distinct filenames.
     */
    public function testFindsTemplatesFromBothFolders(): void
    {
        $builtin = $this->makeTempDir('-builtin');
        $custom = $this->makeTempDir('-custom');
        file_put_contents($builtin . 'template_1.html', '<html></html>');
        file_put_contents($custom . 'my_custom.html', '<html></html>');

        $module = $this->makeModule($builtin, $custom);
        $this->callSetEmailTemplates($module);

        $templates = $this->getProp($module, 'emailTemplates');
        sort($templates);
        $this->assertSame(['my_custom.html', 'template_1.html'], $templates);
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: when a custom template shares
     * its filename with a built-in one, the custom template takes
     * priority - its path is used, not the built-in default's.
     */
    public function testCustomTemplateTakesPriorityOverBuiltinWithSameName(): void
    {
        $builtin = $this->makeTempDir('-builtin');
        $custom = $this->makeTempDir('-custom');
        file_put_contents($builtin . 'template_1.html', 'builtin version');
        file_put_contents($custom . 'template_1.html', 'custom version');

        $module = $this->makeModule($builtin, $custom);
        $this->callSetEmailTemplates($module);

        $files = $this->getProp($module, 'email_template_files');
        // normalize separators before comparing - wire('files')->find()
        // normalizes paths to forward slashes internally, while
        // sys_get_temp_dir() returns backslashes on Windows, so a raw
        // string comparison would fail there despite both pointing at the
        // same, correct (custom) file
        $normalize = fn ($path) => str_replace('\\', '/', $path);
        $this->assertSame($normalize($custom . 'template_1.html'), $normalize($files['template_1.html']));
    }

    /**
     * 3) REGRESSION TEST for the fixed bug: a template sharing its
     * filename between the built-in and custom folders appears only
     * once in the resulting template name list, not twice.
     */
    public function testDoesNotListSharedFilenameTwice(): void
    {
        $builtin = $this->makeTempDir('-builtin');
        $custom = $this->makeTempDir('-custom');
        file_put_contents($builtin . 'template_1.html', 'builtin version');
        file_put_contents($custom . 'template_1.html', 'custom version');

        $module = $this->makeModule($builtin, $custom);
        $this->callSetEmailTemplates($module);

        $templates = $this->getProp($module, 'emailTemplates');
        $this->assertCount(1, $templates);
        $this->assertSame(['template_1.html'], $templates);
    }

    /**
     * 4) With no templates in either folder, both properties end up
     * empty rather than erroring.
     */
    public function testEmptyFoldersResultInEmptyProperties(): void
    {
        $builtin = $this->makeTempDir('-builtin');
        $custom = $this->makeTempDir('-custom');

        $module = $this->makeModule($builtin, $custom);
        $this->callSetEmailTemplates($module);

        $this->assertSame([], $this->getProp($module, 'emailTemplates'));
        $this->assertSame([], $this->getProp($module, 'email_template_files'));
    }
}