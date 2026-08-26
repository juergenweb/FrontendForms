<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Unit tests for Form::getDate() / Form::getTime(), the source of the
 * [[CURRENTDATEVALUE]] / [[CURRENTDATETIMEVALUE]] mail placeholders.
 *
 * These tests use newInstanceWithoutConstructor() to avoid Form's full,
 * heavy constructor, then populate just the properties getDate()/getTime()
 * actually read (frontendforms, user) via reflection - matching the
 * pattern established throughout this session for FrontendForms.module's
 * own methods, applied here to Form.
 *
 * wire('languages')'s actual state (whether LanguageSupport is installed)
 * is outside this test suite's control, so each test checks it first and
 * adapts its expectations accordingly, rather than assuming either state -
 * same approach as LanguageTest.php earlier in this session.
 */
final class GetDateGetTimeTest extends TestCase
{
    private function makeForm(array $frontendforms): \FrontendForms\Form
    {
        // reflection is against \FrontendForms\Form, but there is no
        // FrontendForms\Form use-import needed since we only touch it via
        // ReflectionClass by string name below
        $ref = new ReflectionClass(\FrontendForms\Form::class);
        /** @var \FrontendForms\Form $form */
        $form = $ref->newInstanceWithoutConstructor();

        $frontendformsProp = new ReflectionProperty(\FrontendForms\Tag::class, 'frontendforms');
        $frontendformsProp->setAccessible(true);
        $frontendformsProp->setValue($form, $frontendforms);

        $userProp = new ReflectionProperty(\FrontendForms\Form::class, 'user');
        $userProp->setAccessible(true);
        $userProp->setValue($form, \ProcessWire\wire('user'));

        return $form;
    }

    private function hasMultiLanguage(): bool
    {
        return (bool) \ProcessWire\wire('languages');
    }

    private function currentLanguageId(): int
    {
        return (int) \ProcessWire\wire('user')->language->id;
    }

    /**
     * 1) With no multi-language support, the plain "input_dateformat"
     * config value is used directly.
     */
    public function testUsesPlainDateFormatWithoutMultiLanguage(): void
    {
        if ($this->hasMultiLanguage()) {
            $this->markTestSkipped('This installation has multi-language support installed.');
        }

        $form = $this->makeForm(['input_dateformat' => 'd.m.Y']);
        $result = $form->getDate('2026-03-15');

        $this->assertSame(date('d.m.Y', strtotime('2026-03-15')), $result);
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: with multi-language support
     * installed, but only the plain "input_dateformat" configured (no
     * per-language override for the current user's language), the plain
     * value is still used - not silently skipped in favor of the
     * hardcoded 'Y-m-d' default.
     */
    public function testFallsBackToPlainDateFormatWhenNoLanguageSpecificOverrideExists(): void
    {
        if (!$this->hasMultiLanguage()) {
            $this->markTestSkipped('This installation has no multi-language support installed.');
        }

        $form = $this->makeForm(['input_dateformat' => 'd.m.Y']);
        $result = $form->getDate('2026-03-15');

        $this->assertSame(date('d.m.Y', strtotime('2026-03-15')), $result);
        $this->assertNotSame(date('Y-m-d', strtotime('2026-03-15')), $result);
    }

    /**
     * 3) With multi-language support installed AND a language-specific
     * override for the current user's language, that override takes
     * priority over the plain value.
     */
    public function testUsesLanguageSpecificOverrideWhenPresent(): void
    {
        if (!$this->hasMultiLanguage()) {
            $this->markTestSkipped('This installation has no multi-language support installed.');
        }

        $langId = $this->currentLanguageId();
        $form = $this->makeForm([
            'input_dateformat' => 'd.m.Y',
            'input_dateformat__' . $langId => 'Y/m/d',
        ]);
        $result = $form->getDate('2026-03-15');

        $this->assertSame(date('Y/m/d', strtotime('2026-03-15')), $result);
    }

    /**
     * 4) If neither a language-specific nor a plain value is configured
     * at all, the hardcoded 'Y-m-d' default is used.
     */
    public function testFallsBackToHardcodedDefaultWhenNothingConfigured(): void
    {
        $form = $this->makeForm([]);
        $result = $form->getDate('2026-03-15');

        $this->assertSame(date('Y-m-d', strtotime('2026-03-15')), $result);
    }

    /**
     * 5) Same fallback-chain behavior applies to getTime() with
     * "input_timeformat".
     */
    public function testGetTimeUsesPlainTimeFormatWithoutMultiLanguage(): void
    {
        if ($this->hasMultiLanguage()) {
            $this->markTestSkipped('This installation has multi-language support installed.');
        }

        $form = $this->makeForm(['input_timeformat' => 'H:i']);
        $result = $form->getTime('2026-03-15 14:30:00');

        $this->assertSame(date('H:i', strtotime('2026-03-15 14:30:00')), $result);
    }

    /**
     * 6) REGRESSION TEST for the fixed bug, mirrored for getTime(): the
     * plain "input_timeformat" is used when multi-language is installed
     * but no per-language override exists.
     */
    public function testGetTimeFallsBackToPlainTimeFormatWhenNoLanguageSpecificOverrideExists(): void
    {
        if (!$this->hasMultiLanguage()) {
            $this->markTestSkipped('This installation has no multi-language support installed.');
        }

        $form = $this->makeForm(['input_timeformat' => 'H:i']);
        $result = $form->getTime('2026-03-15 14:30:00');

        $this->assertSame(date('H:i', strtotime('2026-03-15 14:30:00')), $result);
        $this->assertNotSame(date('H:i a', strtotime('2026-03-15 14:30:00')), $result);
    }

    /**
     * 7) getTime()'s hardcoded default ('H:i a') is used when nothing is
     * configured at all.
     */
    public function testGetTimeFallsBackToHardcodedDefaultWhenNothingConfigured(): void
    {
        $form = $this->makeForm([]);
        $result = $form->getTime('2026-03-15 14:30:00');

        $this->assertSame(date('H:i a', strtotime('2026-03-15 14:30:00')), $result);
    }
}
