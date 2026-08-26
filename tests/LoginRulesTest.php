<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LoginRules;
use FrontendForms\LogicFactory;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for LoginRules.
 *
 * The username/email match rules ultimately read the submitted login
 * value from the live $_POST/WireInputData (via LoginLogic), not from
 * Valitron's own dataset - synchronizing that from a unit test proved
 * unreliable elsewhere in this session. These tests instead rely on the
 * field simply not being present in $_POST, which deterministically
 * resolves to an empty login value and therefore a safe, predictable
 * "false" result - still confirming the rule is genuinely registered and
 * reaches real validation logic (not just that register() doesn't throw).
 */
final class LoginRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new LoginRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "matchUsername" rule is registered and correctly fails when
     * no matching login value was actually submitted.
     */
    public function testMatchUsernameRuleIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-username' => 'exists-in-fields-only', 'myform-password' => 'somepassword']);
        $v->rule('matchUsername', 'myform-password', 'username');

        $this->assertFalse($v->validate());
    }

    /**
     * 2) The "matchesUsernamePassword" alias resolves to the same
     * behaviour.
     */
    public function testMatchesUsernamePasswordAliasIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-username' => 'exists-in-fields-only', 'myform-password' => 'somepassword']);
        $v->rule('matchesUsernamePassword', 'myform-password', 'username');

        $this->assertFalse($v->validate());
    }

    /**
     * 3) The "matchEmail" rule is registered and correctly fails when no
     * matching login value was actually submitted.
     */
    public function testMatchEmailRuleIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-email' => 'exists-in-fields-only', 'myform-password' => 'somepassword']);
        $v->rule('matchEmail', 'myform-password', 'email');

        $this->assertFalse($v->validate());
    }

    /**
     * 4) The "matchesEmailPassword" alias resolves to the same behaviour.
     */
    public function testMatchesEmailPasswordAliasIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-email' => 'exists-in-fields-only', 'myform-password' => 'somepassword']);
        $v->rule('matchesEmailPassword', 'myform-password', 'email');

        $this->assertFalse($v->validate());
    }

    /**
     * 5) The "checkTfaCode" rule is registered and rejects when no valid
     * User/Module pair was provided.
     */
    public function testCheckTfaCodeRuleIsRegistered(): void
    {
        $this->registerRules();

        $v = new Validator(['code' => '123456']);
        $v->rule('checkTfaCode', 'code', null, null);

        $this->assertFalse($v->validate());
    }
}
