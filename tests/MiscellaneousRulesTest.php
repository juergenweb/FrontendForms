<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LogicFactory;
use FrontendForms\MiscellaneousRules;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for MiscellaneousRules.
 *
 * Nine rules that used to live here (exactValue, differentValue,
 * compareTexts, cyrillicname/cyrillicName, noLetters, noNumbers,
 * uniqueStringValueOfPWField, firstAndLastname) were removed as exact
 * duplicates of the same rules already registered by TextRules - see
 * TextRulesTest for their coverage. MiscellaneousRules now only registers
 * the four rules genuinely unique to it.
 *
 * The underlying validation logic itself is already thoroughly covered by
 * the pre-existing MiscellaneousLogicTest - these tests focus on
 * confirming each rule name is genuinely wired to real, working
 * validation logic through Valitron, plus a dedicated regression test for
 * the self-reference fix applied to validateRequiredIfEqual().
 */
final class MiscellaneousRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new MiscellaneousRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "checkHex" rule is registered and works.
     */
    public function testCheckHexRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '#FF00AA']);
        $v->rule('checkHex', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "requiredIfEqual" rule is registered and works.
     */
    public function testRequiredIfEqualRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '', 'myform-status' => 'active']);
        $v->rule('requiredIfEqual', 'field', 'status', 'active');

        $this->assertFalse($v->validate());
    }

    /**
     * 3) REGRESSION TEST for the fixed self-reference bug: using the same
     * field as both the field being validated and the comparison field
     * now correctly throws, matching the behaviour of the sibling
     * "requiredIfEmpty"/"requiredIfNotEmpty" rules (which already threw
     * for this before the fix).
     */
    public function testRequiredIfEqualThrowsForSelfReference(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-field' => 'active']);
        $v->rule('requiredIfEqual', 'myform-field', 'field', 'active');

        $this->expectException(\Exception::class);

        $v->validate();
    }

    /**
     * 4) The "requiredIfEmpty" rule is registered and works.
     */
    public function testRequiredIfEmptyRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '', 'myform-other' => '']);
        $v->rule('requiredIfEmpty', 'field', 'other');

        $this->assertFalse($v->validate());
    }

    /**
     * 5) The "requiredIfNotEmpty" rule is registered and works.
     */
    public function testRequiredIfNotEmptyRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'somevalue', 'myform-other' => 'something']);
        $v->rule('requiredIfNotEmpty', 'field', 'other');

        $this->assertFalse($v->validate());
    }
}