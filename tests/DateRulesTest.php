<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DateRules;
use FrontendForms\Form;
use FrontendForms\LogicFactory;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Unit tests for DateRules.
 *
 * register() is exercised end-to-end through a real Valitron Validator,
 * confirming each of the six registered rules is both present and
 * genuinely wired to the correct DateLogic method (not just that
 * register() runs without error).
 */
final class DateRulesTest extends TestCase
{
    private function registerRules(): void
    {
        $form = new Form('myform');
        $rules = new DateRules();
        $rules->setLogicFactory(new LogicFactory($form, $form->wire()));
        $rules->register();
    }

    /**
     * 1) The "week" rule is registered and correctly validates a real ISO
     * week string.
     */
    public function testWeekRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '2023-W06']);
        $v->rule('week', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 2) The "week" rule correctly rejects an invalid week string.
     */
    public function testWeekRuleRejectsInvalidValue(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => 'not-a-week']);
        $v->rule('week', 'field');

        $this->assertFalse($v->validate());
    }

    /**
     * 3) The "month" rule is registered and correctly validates a real ISO
     * month string.
     */
    public function testMonthRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '2023-06']);
        $v->rule('month', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 4) The "month" rule correctly rejects an invalid month string.
     */
    public function testMonthRuleRejectsInvalidValue(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '2023-13']);
        $v->rule('month', 'field');

        $this->assertFalse($v->validate());
    }

    /**
     * 5) The "dateBeforeField" rule is registered and correctly compares
     * against a referenced field.
     */
    public function testDateBeforeFieldRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-start' => '2025-06-10', 'myform-end' => '2025-06-20']);
        $v->rule('dateBeforeField', 'myform-start', 'end');

        $this->assertTrue($v->validate());
    }

    /**
     * 6) The "dateAfterField" rule is registered and correctly compares
     * against a referenced field.
     */
    public function testDateAfterFieldRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-start' => '2025-06-10', 'myform-end' => '2025-06-20']);
        $v->rule('dateAfterField', 'myform-end', 'start');

        $this->assertTrue($v->validate());
    }

    /**
     * 7) The "dateWithinDaysRange" rule is registered and correctly
     * validates a date inside the allowed range.
     */
    public function testDateWithinDaysRangeRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-base' => '2025-06-01', 'myform-target' => '2025-06-05']);
        $v->rule('dateWithinDaysRange', 'myform-target', 'base', 10);

        $this->assertTrue($v->validate());
    }

    /**
     * 8) The "dateOutsideOfDaysRange" rule is registered and correctly
     * validates a date outside the restricted range.
     */
    public function testDateOutsideOfDaysRangeRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['myform-base' => '2025-06-01', 'myform-target' => '2025-06-20']);
        $v->rule('dateOutsideOfDaysRange', 'myform-target', 'base', 10);

        $this->assertTrue($v->validate());
    }

    /**
     * 9) REGRESSION TEST for the fixed bug: the "time" rule now exists and
     * is registered - previously, InputTime's default setRule('time')
     * referenced a rule that was never implemented anywhere (neither
     * natively in Valitron nor as a custom FrontendForms rule), causing
     * Valitron to throw an InvalidArgumentException for every form
     * containing a time input, as soon as validation ran.
     */
    public function testTimeRuleIsRegisteredAndWorks(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '14:30']);
        $v->rule('time', 'field');

        $this->assertTrue($v->validate());
    }

    /**
     * 10) The "time" rule rejects an invalid time string.
     */
    public function testTimeRuleRejectsInvalidValue(): void
    {
        $this->registerRules();

        $v = new Validator(['field' => '25:99']);
        $v->rule('time', 'field');

        $this->assertFalse($v->validate());
    }
}