<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\LogicFactory;
use FrontendForms\TextRules;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for TextRules.
 *
 * These nine rules used to be duplicated in MiscellaneousRules (removed as
 * exact duplicates) - TextRules is now their sole owner. The underlying
 * TextLogic validation itself is already covered by the pre-existing
 * TextLogicTest - these tests focus on confirming each rule name is
 * genuinely wired to real, working validation logic through Valitron.
 */
final class TextRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new TextRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "exactValue" rule is registered and works.
     */
    public function testExactValueRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'expected']);
        $v->rule('exactValue', 'field', 'expected');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "differentValue" rule is registered and works.
     */
    public function testDifferentValueRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'something-else']);
        $v->rule('differentValue', 'field', 'forbidden');

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The "compareTexts" rule is registered and works.
     */
    public function testCompareTextsRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'blue']);
        $v->rule('compareTexts', 'field', 'blue');

        $this->assertTrue($v->validate());
    }

    /**
     * 4) The "cyrillicname" rule is registered and works.
     */
    public function testCyrillicnameRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'иван']);
        $v->rule('cyrillicname', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 5) The "cyrillicName" camelCase alias also works.
     */
    public function testCyrillicNameAliasWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'иван']);
        $v->rule('cyrillicName', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 6) The "noLetters" rule is registered and works.
     */
    public function testNoLettersRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '12345']);
        $v->rule('noLetters', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 7) The "noNumbers" rule is registered and works.
     */
    public function testNoNumbersRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'abcde']);
        $v->rule('noNumbers', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 8) The "uniqueStringValueOfPWField" rule is registered and passes
     * for a clearly non-existent field value.
     */
    public function testUniqueStringValueOfPWFieldRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'definitely-not-a-real-value-xyz123']);
        $v->rule('uniqueStringValueOfPWField', 'field', 'title');

        $this->assertTrue($v->validate());
    }

    /**
     * 9) The "firstAndLastname" rule is registered and works.
     */
    public function testFirstAndLastnameRuleWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => "Anne-Marie O'Brien"]);
        $v->rule('firstAndLastname', 'field');

        $this->assertTrue($v->validate());
    }
}
