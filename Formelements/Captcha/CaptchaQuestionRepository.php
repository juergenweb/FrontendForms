<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Languages;
use ProcessWire\Pages;
use ProcessWire\User;

/**
 * CaptchaQuestionRepository
 *
 * Loads the pool of security questions for the SimpleQuestionCaptcha from
 * ProcessWire pages (template "ff_question"), including their optional
 * per-question overrides (success/error message, placeholder, notes,
 * description, description position) and multi-language fallback handling.
 *
 * Pure data access - no validation or CAPTCHA behaviour lives here, that
 * belongs to CaptchaManager.
 *
 * $pages, $languages, and $user are injected (rather than read via the
 * global wire() function). Note on testability: PageArray's add()/count
 * behaviour turned out to need a live ProcessWire database to behave
 * predictably even for fake/unsaved Page items, so getAll() itself (the
 * Pages/PageArray orchestration) is best covered by an integration test.
 * extractQuestionData() - the per-page field/language-fallback logic, which
 * is the part most worth testing - takes a single Page directly and has no
 * such dependency, so it can be unit tested reliably.
 *
 * @package FrontendForms\Captcha
 */
final class CaptchaQuestionRepository
{
    private const TEMPLATE = 'ff_question';
    private const MAX_QUESTIONS = 25; // upper limit to prevent a too large pool

    /**
     * @param Pages $pages
     * @param Languages|null $languages null if LanguageSupport is not installed
     * @param User|null $user the current visitor, used to resolve the active language
     */
    public function __construct(
        private readonly Pages $pages,
        private readonly Languages|null $languages = null,
        private readonly User|null $user = null
    ) {
    }

    /**
     * Get all published CAPTCHA questions as an array, ready to be handed
     * to CaptchaManager::addQuestions().
     * @return array
     */
    public function getAll(): array
    {
        // need to include all, otherwise pages under the admin tree will not be listed
        $questions = $this->pages->find(
            'template=' . self::TEMPLATE . ',include=all,status=published,status!=hidden'
        );

        $questionArray = [];

        $numberOfQuestions = $questions->count;
        if (!$numberOfQuestions) {
            return $questionArray;
        }

        // if there are more than the limit, pick a random subset to prevent a too large array
        if ($numberOfQuestions > self::MAX_QUESTIONS) {
            $questions = $questions->findRandom(self::MAX_QUESTIONS);
        }

        // check if multi-language site
        $multilang = false;
        $lang = null;
        if ($this->languages) {
            $multilang = true;
            $lang = $this->user->language;
        }

        // create the multidimensional array
        foreach ($questions as $key => $question) {
            $questionArray[$key] = $this->extractQuestionData($question, $multilang, $lang);
        }

        return array_filter($questionArray);
    }

    /**
     * Build a single question's data array from one "ff_question" page. Pure
     * per-page extraction, deliberately separated from getAll()'s
     * Pages/PageArray orchestration above so it can be unit tested directly
     * with a single Page object - no PageArray (and its ProcessWire-internal
     * add()/count behaviour, which needs a live database to test reliably)
     * is involved here.
     * @param object $question - the ff_question Page
     * @param bool $multilang - whether to read language-aware values via getLanguageValue()
     * @param mixed $lang - the active Language (only used when $multilang is true)
     * @return array
     */
    public function extractQuestionData(object $question, bool $multilang, mixed $lang = null): array
    {
        if ($multilang) {
            $title = $question->getLanguageValue($lang, 'title');
            // check if field "title" contains a value in this language, otherwise take the value from the default language
            if (!$title) {
                $title = $question->getLanguageValue('default', 'title');
            }
            $answersRaw = $question->getLanguageValue($lang, 'ff_answers');
            // check if field "answers" contains a value in this language, otherwise take the value from the default language
            if (!$answersRaw) {
                $answersRaw = $question->getLanguageValue('default', 'ff_answers');
            }
            $answers = self::splitAnswers($answersRaw);
            $successmsg = $question->getLanguageValue($lang, 'ff_successmsg');
            $errormsg = $question->getLanguageValue($lang, 'ff_errormsg');
            $placeholder = $question->getLanguageValue($lang, 'ff_placeholder');
            $notes = $question->getLanguageValue($lang, 'ff_notes');
            $description = $question->getLanguageValue($lang, 'ff_description');
            $descriptionPosition = $question->getLanguageValue($lang, 'ff_descposition')->value;
        } else {
            $title = $question->title;
            $answers = self::splitAnswers($question->ff_answers);
            $successmsg = $question->ff_successmsg;
            $errormsg = $question->ff_errormsg;
            $placeholder = $question->ff_placeholder;
            $notes = $question->ff_notes;
            $description = $question->ff_description;
            $descriptionPosition = $question->ff_descposition->value;
        }

        return [
            'question' => $title,
            'answers' => $answers,
            'successMsg' => $successmsg,
            'errorMsg' => $errormsg,
            'placeholder' => $placeholder,
            'notes' => $notes,
            'description' => $description,
            'descriptionPosition' => $descriptionPosition,
        ];
    }

    /**
     * Split a newline-separated "answers" field value into a clean list of
     * accepted answers, trimming whitespace and dropping empty lines. A
     * field that is empty or contains only blank lines correctly results
     * in an empty array (rather than an array containing a single empty
     * string, which explode() alone would produce) - so that an
     * unanswerable, misconfigured question can be detected and skipped by
     * CaptchaManager::pickRandomQuestion(), instead of silently making it
     * into the question pool with no possible correct answer.
     * @param string|null $answersRaw
     * @return array
     */
    private static function splitAnswers(string|null $answersRaw): array
    {
        if (!$answersRaw) {
            return [];
        }

        $answers = array_map('trim', explode("\n", $answersRaw));

        return array_values(array_filter($answers, static fn (string $answer): bool => $answer !== ''));
    }
}