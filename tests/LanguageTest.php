<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Language;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Language.
 *
 * renderLanguage() only produces actual <select> output on a genuinely
 * multi-language ProcessWire installation (count(wire('languages')) > 1) -
 * a condition this test environment cannot reliably control. The tests
 * below cover what's safely testable regardless of the installation's
 * language configuration: construction, and the documented "single
 * language" short-circuit (which is exactly what a typical single-
 * language test/dev install already exercises).
 */
final class LanguageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    /**
     * 1) The constructor sets a non-empty label.
     */
    public function testConstructorSetsLabel(): void
    {
        $field = new Language('language');

        $this->assertNotSame('', $field->getLabel()->getText());
    }

    /**
     * 2) The "required" and "integer" validation rules are automatically
     * applied.
     */
    public function testConstructorAppliesExpectedRules(): void
    {
        $field = new Language('language');

        $rules = $field->getRules();
        $this->assertArrayHasKey('required', $rules);
        $this->assertArrayHasKey('integer', $rules);
    }

    /**
     * 3) setFixedLanguageID() is chainable (returns $this).
     */
    public function testSetFixedLanguageIDIsChainable(): void
    {
        $field = new Language('language');

        $this->assertSame($field, $field->setFixedLanguageID(1234));
    }

    /**
     * 4) REGRESSION-STYLE TEST: rendering works through the hookable
     * ___renderLanguage() method (aligned with every other default field
     * class in this project, e.g. Phone, Subject, Message, Gender - it
     * was previously a plain, non-hookable renderLanguage()). On a
     * single-language installation (the typical case for an isolated
     * test environment), it returns an empty string, per its own
     * documented behaviour.
     */
    public function testRenderLanguageReturnsEmptyStringOnSingleLanguageInstall(): void
    {
        $field = new Language('language');

        if (count($field->wire('languages')) <= 1) {
            $this->assertSame('', $field->renderLanguage());
        } else {
            $this->markTestSkipped('This installation has more than one language configured.');
        }
    }

    /**
     * 5) On a genuinely multi-language installation, renderLanguage()
     * produces a real <select> with one <option> per installed language.
     */
    public function testRenderLanguageRendersOneOptionPerLanguageOnMultiLanguageInstall(): void
    {
        $field = new Language('language');
        $languages = $field->wire('languages');

        if (count($languages) <= 1) {
            $this->markTestSkipped('This installation has only one language configured.');
        }

        $out = $field->renderLanguage();

        $this->assertStringStartsWith('<select', $out);
        foreach ($languages as $lang) {
            $this->assertStringContainsString('value="' . $lang->id . '"', $out);
        }
    }

    /**
     * 6) On a multi-language installation, the current user's language is
     * marked selected by default (before any submission).
     */
    public function testRenderLanguageMarksUserLanguageAsSelectedByDefault(): void
    {
        $field = new Language('language');
        $languages = $field->wire('languages');

        if (count($languages) <= 1) {
            $this->markTestSkipped('This installation has only one language configured.');
        }

        $userLangId = $field->wire('user')->language->id;

        $out = $field->renderLanguage();

        $this->assertMatchesRegularExpression(
            '/value="' . $userLangId . '"[^>]*selected/',
            $out
        );
    }

    /**
     * 7) setFixedLanguageID() overrides the default selection with the
     * given language, instead of the current user's own language.
     */
    public function testRenderLanguageMarksFixedLanguageAsSelected(): void
    {
        $field = new Language('language');
        $languages = $field->wire('languages');

        if (count($languages) <= 1) {
            $this->markTestSkipped('This installation has only one language configured.');
        }

        // Pick a language that is NOT the current user's own language, to
        // make sure the fixed override - not the user default - is what
        // ends up selected.
        $userLangId = $field->wire('user')->language->id;
        $otherLangId = null;
        foreach ($languages as $lang) {
            if ($lang->id != $userLangId) {
                $otherLangId = $lang->id;
                break;
            }
        }

        $field->setFixedLanguageID($otherLangId);
        $out = $field->renderLanguage();

        $this->assertMatchesRegularExpression(
            '/value="' . $otherLangId . '"[^>]*selected/',
            $out
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="' . $userLangId . '"[^>]*selected/',
            $out
        );
    }

    /**
     * 8) REGRESSION TEST for the fixed bug (shared with Select): after the
     * form has been submitted with a different language explicitly
     * chosen, that submitted value - not the user's own language or a
     * fixed override - determines the selection.
     */
    public function testRenderLanguageUsesPostValueOverDefaultWhenSubmitted(): void
    {
        $field = new Language('language');
        $languages = $field->wire('languages');

        if (count($languages) <= 1) {
            $this->markTestSkipped('This installation has only one language configured.');
        }

        $userLangId = $field->wire('user')->language->id;
        $otherLangId = null;
        foreach ($languages as $lang) {
            if ($lang->id != $userLangId) {
                $otherLangId = $lang->id;
                break;
            }
        }

        $field->setAttribute('name', 'language');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['language'] = (string) $otherLangId;

        $out = $field->renderLanguage();

        $this->assertMatchesRegularExpression(
            '/value="' . $otherLangId . '"[^>]*selected/',
            $out
        );
        $this->assertDoesNotMatchRegularExpression(
            '/value="' . $userLangId . '"[^>]*selected/',
            $out
        );
    }
}