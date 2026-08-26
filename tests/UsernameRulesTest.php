<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LogicFactory;
use FrontendForms\UsernameRules;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for UsernameRules.
 */
final class UsernameRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new UsernameRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "uniqueUsername" rule is registered and passes for a clearly
     * non-existent username.
     */
    public function testUniqueUsernameRulePassesForNonExistentUsername(): void
    {
        $this->registerRules();

        $v = new Validator(['username' => 'definitely-not-a-real-username-xyz123']);
        $v->rule('uniqueUsername', 'username');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "usernameSyntax" rule is registered and accepts a valid
     * username.
     */
    public function testUsernameSyntaxRuleAcceptsValidUsername(): void
    {
        $this->registerRules();

        $v = new Validator(['username' => 'john.doe-99']);
        $v->rule('usernameSyntax', 'username');

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The "usernameSyntax" rule rejects a username that's too short.
     */
    public function testUsernameSyntaxRuleRejectsTooShortUsername(): void
    {
        $this->registerRules();

        $v = new Validator(['username' => 'ab']);
        $v->rule('usernameSyntax', 'username');

        $this->assertFalse($v->validate());
    }

    /**
     * 4) The "usernameSyntax" rule rejects a username with disallowed
     * characters.
     */
    public function testUsernameSyntaxRuleRejectsInvalidCharacters(): void
    {
        $this->registerRules();

        $v = new Validator(['username' => 'john doe!']);
        $v->rule('usernameSyntax', 'username');

        $this->assertFalse($v->validate());
    }
}
