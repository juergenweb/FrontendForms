<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\BaseRules;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Valitron\Validator;

/**
 * Minimal concrete subclass, since BaseRules is abstract, exposing its
 * protected methods via public wrappers.
 */
final class ConcreteBaseRules extends BaseRules
{
    public function register(): void
    {
        // not exercised directly in these tests
    }

    public function exposeAddRule(string $ruleName, callable $callback, string $message): void
    {
        $this->addRule($ruleName, $callback, $message);
    }

    public function exposeRegisterRule(string $name, string $method, string $message, object $service): void
    {
        $this->registerRule($name, $method, $message, $service);
    }

    public function exposeRegisterRules(array $rules, object $service): void
    {
        $this->registerRules($rules, $service);
    }
}

/**
 * A simple service object providing validation methods, used to test
 * registerRule()/registerRules() wiring a rule name to an object method.
 */
final class DummyValidationService
{
    public function isEven(string $field, mixed $value): bool
    {
        return is_numeric($value) && ((int) $value) % 2 === 0;
    }

    public function isOdd(string $field, mixed $value): bool
    {
        return is_numeric($value) && ((int) $value) % 2 !== 0;
    }
}

/**
 * Unit tests for BaseRules.
 *
 * Since Validator::addRule() registers rules in Valitron's shared, global
 * registry (not scoped per-instance), every test uses a rule name unique
 * to that test (via uniqid()) to avoid collisions between test runs.
 */
final class BaseRulesTest extends TestCase
{
    // --- addRule() ---

    /**
     * 1) An empty rule name throws an exception rather than being
     * registered.
     */
    public function testAddRuleThrowsForEmptyRuleName(): void
    {
        $rules = new ConcreteBaseRules();

        $this->expectException(InvalidArgumentException::class);

        $rules->exposeAddRule('', fn() => true, 'error message');
    }

    /**
     * 2) A rule registered via addRule() can actually be used by a
     * Valitron Validator afterward, and passes for valid data.
     */
    public function testAddRuleRegistersUsableRuleThatPasses(): void
    {
        $rules = new ConcreteBaseRules();
        $ruleName = 'testRule_' . uniqid();

        $rules->exposeAddRule($ruleName, fn($field, $value) => $value === 'expected', 'Value must be "expected".');

        $v = new Validator(['myfield' => 'expected']);
        $v->rule($ruleName, 'myfield');

        $this->assertTrue($v->validate());
    }

    /**
     * 3) The same rule fails validation for invalid data, with the
     * registered message.
     */
    public function testAddRuleRegistersUsableRuleThatFails(): void
    {
        $rules = new ConcreteBaseRules();
        $ruleName = 'testRule_' . uniqid();

        $rules->exposeAddRule($ruleName, fn($field, $value) => $value === 'expected', 'Value must be "expected".');

        $v = new Validator(['myfield' => 'wrong']);
        $v->rule($ruleName, 'myfield');

        $this->assertFalse($v->validate());
        $this->assertStringContainsString('Value must be "expected".', $v->errors()['myfield'][0]);
    }

    /**
     * 4) The callback's return value is cast to a genuine boolean - a
     * truthy non-bool return value (e.g. a non-empty string) still counts
     * as a pass.
     */
    public function testAddRuleCastsCallbackReturnValueToBool(): void
    {
        $rules = new ConcreteBaseRules();
        $ruleName = 'testRule_' . uniqid();

        // deliberately returns a non-bool truthy value
        $rules->exposeAddRule($ruleName, fn($field, $value) => 'truthy-string', 'should not appear');

        $v = new Validator(['myfield' => 'anything']);
        $v->rule($ruleName, 'myfield');

        $this->assertTrue($v->validate());
    }

    // --- registerRule() ---

    /**
     * 5) registerRule() wires a rule name to a method on a service object,
     * and the resulting rule works correctly through the Validator.
     */
    public function testRegisterRuleWiresServiceMethodCorrectly(): void
    {
        $rules = new ConcreteBaseRules();
        $service = new DummyValidationService();
        $ruleName = 'testIsEven_' . uniqid();

        $rules->exposeRegisterRule($ruleName, 'isEven', 'Value must be even.', $service);

        $vEven = new Validator(['n' => '4']);
        $vEven->rule($ruleName, 'n');
        $this->assertTrue($vEven->validate());

        $vOdd = new Validator(['n' => '3']);
        $vOdd->rule($ruleName, 'n');
        $this->assertFalse($vOdd->validate());
    }

    // --- registerRules() ---

    /**
     * 6) Multiple rules can be registered at once, and each works
     * correctly afterward.
     */
    public function testRegisterRulesRegistersMultipleRulesCorrectly(): void
    {
        $rules = new ConcreteBaseRules();
        $service = new DummyValidationService();
        $evenRule = 'testEven_' . uniqid();
        $oddRule = 'testOdd_' . uniqid();

        $rules->exposeRegisterRules([
            $evenRule => ['isEven', 'Must be even.'],
            $oddRule => ['isOdd', 'Must be odd.'],
        ], $service);

        $v = new Validator(['a' => '4', 'b' => '3']);
        $v->rule($evenRule, 'a');
        $v->rule($oddRule, 'b');

        $this->assertTrue($v->validate());
    }

    /**
     * 7) A rule definition that isn't an array of exactly 2 elements
     * throws, mentioning the offending rule name.
     */
    public function testRegisterRulesThrowsForWrongElementCount(): void
    {
        $rules = new ConcreteBaseRules();
        $service = new DummyValidationService();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('badRule');

        $rules->exposeRegisterRules(['badRule' => ['onlyOneElement']], $service);
    }

    /**
     * 8) A rule definition that isn't an array at all throws.
     */
    public function testRegisterRulesThrowsWhenDefinitionIsNotArray(): void
    {
        $rules = new ConcreteBaseRules();
        $service = new DummyValidationService();

        $this->expectException(InvalidArgumentException::class);

        $rules->exposeRegisterRules(['badRule' => 'not-an-array'], $service);
    }

    /**
     * 9) A rule definition with a non-string method or message throws.
     */
    public function testRegisterRulesThrowsForNonStringElements(): void
    {
        $rules = new ConcreteBaseRules();
        $service = new DummyValidationService();

        $this->expectException(InvalidArgumentException::class);

        $rules->exposeRegisterRules(['badRule' => [123, 'a message']], $service);
    }
}
