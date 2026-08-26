<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\EmailRules;
use FrontendForms\Form;
use FrontendForms\LogicFactory;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for EmailRules.
 */
final class EmailRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new EmailRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "uniqueEmail" rule is registered and passes for a clearly
     * non-existent email address.
     */
    public function testUniqueEmailRuleIsRegisteredAndPassesForNonExistentEmail(): void
    {
        $this->registerRules();

        $v = new Validator(['email' => 'definitely-not-a-real-user-xyz123@nonexistent-domain-xyz.test']);
        $v->rule('uniqueEmail', 'email');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "uniqueEmail" rule passes for an empty value (required-field
     * validation is handled separately).
     */
    public function testUniqueEmailRulePassesForEmptyValue(): void
    {
        $this->registerRules();

        $v = new Validator(['email' => '']);
        $v->rule('uniqueEmail', 'email');

        $this->assertTrue($v->validate());
    }
}
