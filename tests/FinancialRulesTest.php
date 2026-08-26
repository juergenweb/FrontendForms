<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FinancialRules;
use FrontendForms\Form;
use FrontendForms\LogicFactory;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for FinancialRules.
 */
final class FinancialRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new FinancialRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "checkIban" rule is registered and accepts a real, valid
     * IBAN.
     */
    public function testCheckIbanRuleAcceptsValidIban(): void
    {
        $this->registerRules();

        $v = new Validator(['iban' => 'DE89370400440532013000']);
        $v->rule('checkIban', 'iban');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "checkIban" rule rejects an invalid IBAN.
     */
    public function testCheckIbanRuleRejectsInvalidIban(): void
    {
        $this->registerRules();

        $v = new Validator(['iban' => 'not-an-iban']);
        $v->rule('checkIban', 'iban');

        $this->assertFalse($v->validate());
    }

    /**
     * 3) The "checkBic" rule is registered and accepts a real, valid
     * 8-character BIC.
     */
    public function testCheckBicRuleAcceptsValidBic(): void
    {
        $this->registerRules();

        $v = new Validator(['bic' => 'DEUTDEFF']);
        $v->rule('checkBic', 'bic');

        $this->assertTrue($v->validate());
    }

    /**
     * 4) The "checkBic" rule rejects an invalid BIC.
     */
    public function testCheckBicRuleRejectsInvalidBic(): void
    {
        $this->registerRules();

        $v = new Validator(['bic' => 'not-a-bic']);
        $v->rule('checkBic', 'bic');

        $this->assertFalse($v->validate());
    }
}
