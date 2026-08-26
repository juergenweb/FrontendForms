<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\CaptchaManager;
use FrontendForms\DefaultTextCaptcha;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for CaptchaManager.
 *
 * Covers the question-pool logic (addQuestion(), addQuestions(), hasQuestions(),
 * getQuestionPool(), pickRandomQuestion()) and config(), all of which work
 * without needing a real CAPTCHA implementation. setType() and buildField()
 * are intentionally NOT covered here: they depend on AbstractCaptchaFactory
 * building a concrete CAPTCHA object (e.g. SimpleQuestionCaptcha), which is
 * outside the scope of a plain unit test and would need its own (higher
 * level) test.
 *
 * All expected outputs were confirmed by running the actual addQuestion()
 * array-filtering logic standalone before writing the assertions.
 */
final class CaptchaManagerTest extends TestCase
{
    private function createManager(): CaptchaManager
    {
        return new CaptchaManager($this->createMock(Form::class));
    }

    // --- config() ---

    /**
     * 1) config() returns the same CaptchaConfig instance on every call.
     */
    public function testConfigReturnsSameInstance(): void
    {
        $manager = $this->createManager();

        $this->assertSame($manager->config(), $manager->config());
    }

    // --- addQuestion() ---

    /**
     * 2) A question without a trailing question mark gets one appended.
     */
    public function testAddQuestionAppendsQuestionMarkIfMissing(): void
    {
        $manager = $this->createManager();

        $manager->addQuestion('What color is the sky', ['blue']);

        $this->assertSame('What color is the sky?', $manager->getQuestionPool()[0]['question']);
    }

    /**
     * 3) A question that already ends with a question mark is not changed.
     */
    public function testAddQuestionDoesNotDuplicateQuestionMark(): void
    {
        $manager = $this->createManager();

        $manager->addQuestion('What color is the sky?', ['blue']);

        $this->assertSame('What color is the sky?', $manager->getQuestionPool()[0]['question']);
    }

    /**
     * 4) EDGE CASE: an empty $answers array is silently dropped from the
     * stored pool entry - array_filter() treats an empty array as falsy, so
     * the resulting entry has no "answers" key at all rather than an empty
     * one. Documented here so this isn't mistaken for a bug later.
     */
    public function testAddQuestionWithEmptyAnswersDropsAnswersKey(): void
    {
        $manager = $this->createManager();

        $manager->addQuestion('Test', []);

        $this->assertArrayNotHasKey('answers', $manager->getQuestionPool()[0]);
    }

    /**
     * 5) Additional per-question options are merged into the stored entry,
     * but options with a falsy value (e.g. an empty string) are dropped for
     * the same array_filter() reason as testAddQuestionWithEmptyAnswersDropsAnswersKey().
     */
    public function testAddQuestionMergesOptionsAndDropsFalsyOnes(): void
    {
        $manager = $this->createManager();

        $manager->addQuestion('Test', ['a'], ['notes' => 'Hint', 'placeholder' => '']);

        $entry = $manager->getQuestionPool()[0];
        $this->assertSame('Hint', $entry['notes']);
        $this->assertArrayNotHasKey('placeholder', $entry);
    }

    // --- addQuestions() ---

    /**
     * 6) Multiple questions are all added to the pool, in order.
     */
    public function testAddQuestionsAddsAllGivenQuestions(): void
    {
        $manager = $this->createManager();

        $manager->addQuestions([
            ['question' => 'First?', 'answers' => ['a']],
            ['question' => 'Second?', 'answers' => ['b']],
        ]);

        $pool = $manager->getQuestionPool();
        $this->assertCount(2, $pool);
        $this->assertSame('First?', $pool[0]['question']);
        $this->assertSame('Second?', $pool[1]['question']);
    }

    /**
     * 7) An entry without a "question" key is silently skipped rather than
     * added with a null/empty question.
     */
    public function testAddQuestionsSkipsEntriesWithoutQuestionKey(): void
    {
        $manager = $this->createManager();

        $manager->addQuestions([
            ['answers' => ['a']], // no "question" key
            ['question' => 'Valid?', 'answers' => ['b']],
        ]);

        $pool = $manager->getQuestionPool();
        $this->assertCount(1, $pool);
        $this->assertSame('Valid?', $pool[0]['question']);
    }

    // --- hasQuestions() / getQuestionPool() ---

    /**
     * 8) A freshly created manager has no questions.
     */
    public function testHasQuestionsIsFalseByDefault(): void
    {
        $manager = $this->createManager();

        $this->assertFalse($manager->hasQuestions());
        $this->assertSame([], $manager->getQuestionPool());
    }

    /**
     * 9) After adding a question, hasQuestions() reports true.
     */
    public function testHasQuestionsIsTrueAfterAddingOne(): void
    {
        $manager = $this->createManager();
        $manager->addQuestion('Test?', ['a']);

        $this->assertTrue($manager->hasQuestions());
    }

    // --- pickRandomQuestion() ---

    /**
     * 10) With an empty pool, pickRandomQuestion() is a no-op: the current
     * question index stays null.
     */
    public function testPickRandomQuestionWithEmptyPoolIsNoOp(): void
    {
        $manager = $this->createManager();
        $manager->config()->type = 'SimpleQuestionCaptcha';

        $manager->pickRandomQuestion();

        $this->assertNull($manager->getCurrentQuestionIndex());
    }

    /**
     * 11) If the configured CAPTCHA type is not SimpleQuestionCaptcha,
     * pickRandomQuestion() does nothing even if questions are present.
     */
    public function testPickRandomQuestionDoesNothingForOtherCaptchaTypes(): void
    {
        $manager = $this->createManager();
        $manager->config()->type = 'SliderCaptcha';
        $manager->addQuestion('Test?', ['a']);

        $manager->pickRandomQuestion();

        $this->assertNull($manager->getCurrentQuestionIndex());
    }

    /**
     * 12) With exactly one question in the pool, array_rand() has only one
     * possible outcome (index 0), making the result fully deterministic:
     * the current question index is set to 0.
     */
    public function testPickRandomQuestionWithSingleQuestionSelectsIndexZero(): void
    {
        $manager = $this->createManager();
        $manager->config()->type = 'SimpleQuestionCaptcha';
        $manager->addQuestion('Only question?', ['a']);

        $manager->pickRandomQuestion();

        $this->assertSame(0, $manager->getCurrentQuestionIndex());
    }

    /**
     * 13) EDGE CASE: pickRandomQuestion() re-adds a stripped copy (question +
     * answers only) of the picked question to the pool via addQuestion(),
     * growing the pool by one entry - it does not just "read" the question.
     * The currentQuestionIndex still correctly points at the *original*
     * entry (set from $randomIndex, captured before the pool grew).
     */
    public function testPickRandomQuestionGrowsPoolByOneEntry(): void
    {
        $manager = $this->createManager();
        $manager->config()->type = 'SimpleQuestionCaptcha';
        $manager->addQuestion('Only question?', ['a']);

        $manager->pickRandomQuestion();

        $pool = $manager->getQuestionPool();
        $this->assertCount(2, $pool);
        $this->assertSame(0, $manager->getCurrentQuestionIndex());
        $this->assertSame('Only question?', $pool[$manager->getCurrentQuestionIndex()]['question']);
    }

    /**
     * 14) Per-question options (other than "successMsg"/"errorMsg") are
     * forwarded to the Form by dynamically calling the matching
     * setCaptcha{Name}() method - here: an option key "notes" results in a
     * call to Form::setCaptchaNotes().
     */
    public function testPickRandomQuestionForwardsPerQuestionOptionsToForm(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->once())
            ->method('setCaptchaNotes')
            ->with('A helpful hint');

        $manager = new CaptchaManager($form);
        $manager->config()->type = 'SimpleQuestionCaptcha';
        $manager->addQuestion('Only question?', ['a'], ['notes' => 'A helpful hint']);

        $manager->pickRandomQuestion();
    }

    /**
     * 15) "successMsg" and "errorMsg" option keys are explicitly excluded
     * from the per-question forwarding (they are meant to be shown only
     * after the form was posted, not while building the field) - so
     * Form::setCaptchaSuccessMsg() must NOT be called here.
     */
    public function testPickRandomQuestionDoesNotForwardSuccessOrErrorMsg(): void
    {
        $form = $this->createMock(Form::class);
        $form->expects($this->never())->method('setCaptchaSuccessMsg');
        $form->expects($this->never())->method('setCaptchaErrorMsg');

        $manager = new CaptchaManager($form);
        $manager->config()->type = 'SimpleQuestionCaptcha';
        $manager->addQuestion('Only question?', ['a'], ['successMsg' => 'Correct!', 'errorMsg' => 'Wrong!']);

        $manager->pickRandomQuestion();
    }

    // --- isActive() / disable() / getCaptchaObject() defaults ---

    /**
     * 16) A freshly created manager has no active CAPTCHA and no CAPTCHA
     * object/field yet.
     */
    public function testIsActiveAndObjectsAreEmptyByDefault(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));

        $this->assertFalse($manager->isActive());
        $this->assertNull($manager->getCaptchaObject());
        $this->assertNull($manager->getField());
        $this->assertNull($manager->getCurrentQuestionIndex());
        $this->assertSame([], $manager->getQuestionPool());
    }

    /**
     * 17) disable() sets the type to "none", matching setType('none').
     */
    public function testDisableTurnsCaptchaInactive(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));
        $manager->setType('DefaultTextCaptcha');

        $manager->disable();

        $this->assertFalse($manager->isActive());
    }

    // --- setType() ---

    /**
     * 18) Setting a real CAPTCHA type instantiates the matching concrete
     * CAPTCHA class (via AbstractCaptchaFactory) and marks the manager as
     * active.
     */
    public function testSetTypeInstantiatesMatchingCaptchaObject(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));

        $manager->setType('DefaultTextCaptcha');

        $this->assertTrue($manager->isActive());
        $this->assertInstanceOf(DefaultTextCaptcha::class, $manager->getCaptchaObject());
    }

    /**
     * 19) Setting the type to "none" does not instantiate any CAPTCHA
     * object.
     */
    public function testSetTypeWithNoneDoesNotInstantiateCaptchaObject(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));

        $manager->setType('none');

        $this->assertFalse($manager->isActive());
        $this->assertNull($manager->getCaptchaObject());
    }

    // --- buildField() ---

    /**
     * 20) With no active CAPTCHA, buildField() returns null without
     * touching the given $input at all.
     */
    public function testBuildFieldReturnsNullWhenNotActive(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));

        $this->assertNull($manager->buildField('myform', new \stdClass()));
        $this->assertNull($manager->getField());
    }

    /**
     * 21) With an active CAPTCHA, buildField() builds and returns the input
     * field, which is also retrievable afterward via getField(), and
     * carries a "required" validation rule (since no custom
     * requiredErrorMsg was configured, it falls back to the default
     * "please fill out" message).
     */
    public function testBuildFieldBuildsAndStoresRequiredField(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('_')->willReturnArgument(0);
        $manager = new CaptchaManager($form);
        $manager->setType('DefaultTextCaptcha');

        $field = $manager->buildField('myform', new \stdClass());

        $this->assertNotNull($field);
        $this->assertSame($field, $manager->getField());
        $this->assertArrayHasKey('required', $field->getRules());
    }

    /**
     * 22) Notes and description configured on CaptchaConfig are applied to
     * the built field.
     */
    public function testBuildFieldAppliesNotesAndDescriptionFromConfig(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('_')->willReturnArgument(0);
        $manager = new CaptchaManager($form);
        $manager->setType('DefaultTextCaptcha');
        $manager->config()->notes = 'Enter the characters shown above.';
        $manager->config()->description = 'Anti-spam check';
        $manager->config()->descriptionPosition = 'afterLabel';

        $field = $manager->buildField('myform', new \stdClass());

        $this->assertSame('Enter the characters shown above.', $field->getNotes()->getText());
        $this->assertSame('Anti-spam check', $field->getDescription()->getText());
    }

    /**
     * 23) REGRESSION TEST for the defensive null-check fix: if the config's
     * type is somehow set to a non-"none" value WITHOUT going through
     * setType() (bypassing the object instantiation it does), isActive()
     * reports true, but getCaptchaObject() is still null. Before the fix,
     * buildField() would have crashed calling createCaptchaInputField() on
     * null; now it returns null instead.
     */
    public function testBuildFieldReturnsNullWhenTypeSetWithoutCaptchaObject(): void
    {
        $manager = new CaptchaManager($this->createMock(Form::class));
        // bypass setType() on purpose, simulating direct config mutation
        $manager->config()->type = 'DefaultTextCaptcha';

        $this->assertTrue($manager->isActive());
        $this->assertNull($manager->getCaptchaObject());
        $this->assertNull($manager->buildField('myform', new \stdClass()));
    }
}