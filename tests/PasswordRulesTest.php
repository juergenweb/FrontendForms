<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LogicFactory;
use FrontendForms\PasswordRules;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for PasswordRules.
 */
final class PasswordRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new PasswordRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "meetsPasswordConditions" rule is registered and reachable
     * through a real Validator (result depends on the live password
     * policy, so only that it runs without error is confirmed here).
     */
    public function testMeetsPasswordConditionsRuleIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['password' => 'Str0ng!Passw0rd#2025']);
        $v->rule('meetsPasswordConditions', 'password');

        $result = $v->validate();

        $this->assertIsBool($result);
    }

    /**
     * 2) The "safePassword" rule is registered and accepts a random,
     * clearly-not-blacklisted password.
     */
    public function testSafePasswordRuleAcceptsRandomPassword(): void
    {
        $this->registerRules();

        $v = new Validator(['password' => 'Xk9#mQ2vLp8$zR4nW7']);
        $v->rule('safePassword', 'password');

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The "differentPassword" rule is registered and returns false for
     * a guest (not logged in) user, per its documented behaviour.
     */
    public function testDifferentPasswordRuleReturnsFalseForGuest(): void
    {
        $this->registerRules();

        $v = new Validator(['password' => 'anything']);
        $v->rule('differentPassword', 'password');

        $this->assertFalse($v->validate());
    }

    /**
     * 4) The "checkPasswordOfUser" rule is registered and returns false
     * for a guest (not logged in) user.
     */
    public function testCheckPasswordOfUserRuleReturnsFalseForGuest(): void
    {
        $this->registerRules();

        $v = new Validator(['password' => 'anything']);
        $v->rule('checkPasswordOfUser', 'password');

        $this->assertFalse($v->validate());
    }
}
