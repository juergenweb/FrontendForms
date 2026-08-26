<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LogicFactory;
use FrontendForms\SpamRules;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for SpamRules.
 */
final class SpamRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new SpamRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "checkContentForSpam" rule is registered and accepts a normal
     * message with the default threshold.
     */
    public function testCheckContentForSpamRuleAcceptsNormalText(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'This is a perfectly normal message with plenty of length to it.']);
        $v->rule('checkContentForSpam', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "checkCaptcha" rule is registered and accepts a matching
     * (case-insensitive) answer.
     */
    public function testCheckCaptchaRuleAcceptsCorrectAnswer(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'BLUE']);
        $v->rule('checkCaptcha', 'field', 'blue');

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The "checkCaptcha" rule rejects a wrong answer.
     */
    public function testCheckCaptchaRuleRejectsWrongAnswer(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'green']);
        $v->rule('checkCaptcha', 'field', 'blue');

        $this->assertFalse($v->validate());
    }

    /**
     * 4) The "checkSliderCaptcha" rule is registered and rejects when no
     * session position was ever stored (nothing to match against).
     */
    public function testCheckSliderCaptchaRuleRejectsWithoutSessionData(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'unused']);
        $v->rule('checkSliderCaptcha', 'field', '10', '20', 'nonexistent-id-' . uniqid());

        $this->assertFalse($v->validate());
    }
}
