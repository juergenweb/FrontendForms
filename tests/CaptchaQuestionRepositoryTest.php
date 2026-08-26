<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\CaptchaQuestionRepository;
use PHPUnit\Framework\TestCase;
use ProcessWire\Pages;
use stdClass;

/**
 * Unit tests for CaptchaQuestionRepository::extractQuestionData().
 *
 * HISTORY / WHY THIS IS SCOPED THE WAY IT IS: earlier versions of this test
 * tried to use real ProcessWire objects (PageArray, then a real `new Page()`)
 * as stand-ins for a question page. Both attempts behaved unpredictably
 * against a real ProcessWire installation - PageArray appeared empty even
 * after add(), and a template-less `new Page()`'s "title" property resolved
 * to an unrelated Template object instead of the string that was set on it.
 *
 * extractQuestionData(object $question, ...) is intentionally typed against
 * the plain `object` type, not the concrete Page class, precisely so it can
 * be exercised with a plain stdClass (for the single-language, direct
 * property-read path) or a minimal anonymous class with just a
 * getLanguageValue() method (for the multi-language path) - both fully
 * predictable, with no ProcessWire class internals involved at all.
 *
 * getAll() itself (the Pages::find()/PageArray orchestration) is NOT
 * covered here - it should be verified with an integration test against a
 * real ProcessWire database with actual "ff_question" pages, which only you
 * can run.
 */
final class CaptchaQuestionRepositoryTest extends TestCase
{
    private function createRepository(): CaptchaQuestionRepository
    {
        // extractQuestionData() doesn't touch $pages/$languages/$user at all,
        // so a bare mock Pages (never actually called) is enough here.
        return new CaptchaQuestionRepository($this->createMock(Pages::class));
    }

    /**
     * A plain stdClass with the given properties - stands in for a
     * single-language question page. No ProcessWire class involved, so
     * property reads behave exactly as assigned, with no surprises.
     */
    private function createPage(array $properties): stdClass
    {
        $page = new stdClass();
        foreach ($properties as $name => $value) {
            $page->$name = $value;
        }
        return $page;
    }

    /**
     * A minimal object with just a getLanguageValue() method delegating to
     * the given $resolver callback - stands in for a multi-language
     * question page. No ProcessWire class involved.
     */
    private function createLanguageAwarePage(callable $resolver): object
    {
        return new class($resolver) {
            public function __construct(private $resolver)
            {
            }

            public function getLanguageValue($language, $field)
            {
                return ($this->resolver)($language, $field);
            }
        };
    }

    // --- single-language path ---

    /**
     * 1) In single-language mode, all fields are read from the plain
     * (non-language-aware) page properties: title, ff_answers (split by
     * newline), and the optional per-question overrides.
     *
     * Also serves as a regression test for a field-name bug: the source
     * used to read $question->description (a field that doesn't exist on
     * the ff_question template - confirmed via config/questionFields.php,
     * where the real field is named "ff_description", matching the "ff_"
     * prefix every other field here uses) instead of
     * $question->ff_description. The fixture below deliberately only sets
     * "ff_description", not "description", so this test would have failed
     * against the buggy code (result would have been empty/null).
     */
    public function testExtractsFieldsInSingleLanguageMode(): void
    {
        $page = $this->createPage([
            'title' => 'What color is the sky?',
            'ff_answers' => "blue\nlight blue",
            'ff_successmsg' => 'Correct!',
            'ff_errormsg' => 'Wrong!',
            'ff_placeholder' => 'Your answer',
            'ff_notes' => 'Think about a clear day.',
            'ff_description' => 'Anti-spam question',
            'ff_descposition' => (object)['value' => 'afterLabel'],
        ]);

        $result = $this->createRepository()->extractQuestionData($page, false);

        $this->assertSame('What color is the sky?', $result['question']);
        $this->assertSame(['blue', 'light blue'], $result['answers']);
        $this->assertSame('Correct!', $result['successMsg']);
        $this->assertSame('Wrong!', $result['errorMsg']);
        $this->assertSame('Your answer', $result['placeholder']);
        $this->assertSame('Think about a clear day.', $result['notes']);
        $this->assertSame('Anti-spam question', $result['description']);
        $this->assertSame('afterLabel', $result['descriptionPosition']);
    }

    /**
     * 2) A single answer (no newline) still ends up as a one-element array,
     * not a plain string.
     */
    public function testSingleAnswerIsWrappedInArray(): void
    {
        $page = $this->createPage([
            'title' => 'Test?',
            'ff_answers' => 'onlyanswer',
            'ff_successmsg' => '',
            'ff_errormsg' => '',
            'ff_placeholder' => '',
            'ff_notes' => '',
            'ff_description' => '',
            'ff_descposition' => (object)['value' => ''],
        ]);

        $result = $this->createRepository()->extractQuestionData($page, false);

        $this->assertSame(['onlyanswer'], $result['answers']);
    }

    /**
     * 2b) REGRESSION TEST for the fixed bug: a question with an empty (or
     * whitespace/blank-lines-only) "answers" field correctly results in an
     * empty answers array - not an array containing a single empty
     * string, which explode("\n", "") alone would produce. An array like
     * ['' ] would make the question pass CaptchaManager's "has answers"
     * check yet be impossible for any real visitor to actually answer
     * correctly (required rule blocks a blank submission, and no non-empty
     * input equals an empty string).
     */
    public function testEmptyAnswersFieldResultsInEmptyArray(): void
    {
        $page = $this->createPage([
            'title' => 'Test?',
            'ff_answers' => '',
            'ff_successmsg' => '',
            'ff_errormsg' => '',
            'ff_placeholder' => '',
            'ff_notes' => '',
            'ff_description' => '',
            'ff_descposition' => (object)['value' => ''],
        ]);

        $result = $this->createRepository()->extractQuestionData($page, false);

        $this->assertSame([], $result['answers']);
    }

    /**
     * 2c) An answers field containing only blank lines also results in an
     * empty array.
     */
    public function testBlankLinesOnlyAnswersFieldResultsInEmptyArray(): void
    {
        $page = $this->createPage([
            'title' => 'Test?',
            'ff_answers' => "  \n\n   ",
            'ff_successmsg' => '',
            'ff_errormsg' => '',
            'ff_placeholder' => '',
            'ff_notes' => '',
            'ff_description' => '',
            'ff_descposition' => (object)['value' => ''],
        ]);

        $result = $this->createRepository()->extractQuestionData($page, false);

        $this->assertSame([], $result['answers']);
    }

    /**
     * 2d) Whitespace around individual answers is trimmed away.
     */
    public function testAnswersAreTrimmed(): void
    {
        $page = $this->createPage([
            'title' => 'Test?',
            'ff_answers' => "  blue  \n light blue ",
            'ff_successmsg' => '',
            'ff_errormsg' => '',
            'ff_placeholder' => '',
            'ff_notes' => '',
            'ff_description' => '',
            'ff_descposition' => (object)['value' => ''],
        ]);

        $result = $this->createRepository()->extractQuestionData($page, false);

        $this->assertSame(['blue', 'light blue'], $result['answers']);
    }

    // --- multi-language path ---

    /**
     * 3) In multi-language mode, when a translation exists for the active
     * language, both the TITLE and the ANSWERS use that active-language
     * value (not the default-language one).
     *
     * This also verifies the fix for a bug found while writing these tests:
     * the source used to do
     *   $answers = explode("\n", $question->getLanguageValue($lang, 'ff_answers'));
     *   if ($answers) { $answers = explode("\n", $question->getLanguageValue('default', 'ff_answers')); }
     * but explode() on an empty string returns [''] - a non-empty (truthy)
     * array - so "if ($answers)" was always true, and the default-language
     * value always silently overwrote the active-language one, even when a
     * perfectly valid active-language translation existed. Fixed by
     * checking the raw (pre-explode) string for truthiness first, exactly
     * like "title" already did.
     */
    public function testUsesActiveLanguageValuesWhenTranslationsExist(): void
    {
        $page = $this->createLanguageAwarePage(fn($lang, $field) => $this->translationFor($lang, $field));

        $result = $this->createRepository()->extractQuestionData($page, true, 'de');

        $this->assertSame('Welche Farbe hat der Himmel?', $result['question']);
        $this->assertSame(['blau'], $result['answers']);
    }

    /**
     * 4) In multi-language mode, when the title has no translation for the
     * active language (empty string), the default-language title is used
     * as a fallback instead.
     */
    public function testFallsBackToDefaultLanguageTitleWhenTranslationMissing(): void
    {
        $page = $this->createLanguageAwarePage(function ($lang, $field) {
            if ($field === 'title') {
                return $lang === 'default' ? 'Default language title?' : '';
            }
            if ($field === 'ff_answers') {
                return 'answer';
            }
            if ($field === 'ff_descposition') {
                return (object)['value' => ''];
            }
            return '';
        });

        $result = $this->createRepository()->extractQuestionData($page, true, 'de');

        $this->assertSame('Default language title?', $result['question']);
    }

    /**
     * 5) In multi-language mode, when the answers have no translation for
     * the active language (empty string), the default-language answers are
     * used as a fallback instead.
     */
    public function testFallsBackToDefaultLanguageAnswersWhenTranslationMissing(): void
    {
        $page = $this->createLanguageAwarePage(function ($lang, $field) {
            if ($field === 'ff_answers') {
                return $lang === 'default' ? 'blue' : '';
            }
            if ($field === 'title') {
                return 'Some question?';
            }
            if ($field === 'ff_descposition') {
                return (object)['value' => ''];
            }
            return '';
        });

        $result = $this->createRepository()->extractQuestionData($page, true, 'de');

        $this->assertSame(['blue'], $result['answers']);
    }

    /**
     * Shared fixture: returns a different value depending on whether $lang
     * is the literal string "default" or "the active language" (anything else).
     */
    private function translationFor(mixed $lang, string $field): mixed
    {
        $default = [
            'title' => 'What color is the sky?',
            'ff_answers' => 'blue',
            'ff_successmsg' => 'Correct!',
            'ff_errormsg' => 'Wrong!',
            'ff_placeholder' => 'Your answer',
            'ff_notes' => 'Note',
            'ff_description' => 'Anti-spam question',
            'ff_descposition' => (object)['value' => 'afterLabel'],
        ];
        $active = [
            'title' => 'Welche Farbe hat der Himmel?',
            'ff_answers' => 'blau',
            'ff_successmsg' => 'Richtig!',
            'ff_errormsg' => 'Falsch!',
            'ff_placeholder' => 'Deine Antwort',
            'ff_notes' => 'Hinweis',
            'ff_description' => 'Anti-Spam-Frage',
            'ff_descposition' => (object)['value' => 'afterLabel'],
        ];
        $source = ($lang === 'default') ? $default : $active;
        return $source[$field] ?? '';
    }
}