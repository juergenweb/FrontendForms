<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Wire;
use Valitron\Validator;
use InvalidArgumentException;

/**
 * Base class for registering custom Valitron rules.
 */
abstract class BaseRules extends Wire implements RulesInterface
{
    /**
     * Register all validation rules provided by the concrete rule class.
     */
    abstract public function register(): void;

    /**
     * Register a custom validation rule using the Valitron syntax.
     *
     * @param string   $ruleName The unique rule identifier used in Valitron.
     * @param callable $callback The validation callback. Must return a value
     *                            that can be safely interpreted as boolean.
     * @param string   $message  The error message shown on validation failure.
     */
    protected function addRule(string $ruleName, callable $callback, string $message): void
    {

        if ($ruleName === '') {
            throw new InvalidArgumentException('Rule name must not be empty.');
        }

        Validator::addRule(
            $ruleName,
            static function (
                string $field,
                mixed $value,
                array $params,
                array $fields
            ) use ($callback): bool {
                return (bool) $callback($field, $value, $params, $fields);
            },
            $message
        );
    }

    /**
     * Register a single validation rule.
     *
     * @param string $name    The unique rule identifier used in Valitron.
     * @param string $method  The method name on $service to call for validation.
     * @param string $message The error message shown on validation failure.
     * @param object $service The service object providing the validation method.
     */
    final protected function registerRule(
        string $name,
        string $method,
        string $message,
        object $service
    ): void {
        $this->addRule($name, [$service, $method], $message);
    }

    /**
     * Register multiple validation rules.
     *
     * Format:
     * [
     *   'ruleName' => ['methodName', 'message'],
     * ]
     *
     * @param array  $rules   Map of rule name => [method, message].
     * @param object $service The service object providing the validation methods.
     */
    final protected function registerRules(array $rules, object $service): void
    {
        foreach ($rules as $name => $rule) {
            if (!is_array($rule) || count($rule) !== 2) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Rule definition for "%s" must be an array with exactly 2 elements [method, message], got %s.',
                        $name,
                        is_array($rule) ? sprintf('array with %d elements', count($rule)) : gettype($rule)
                    )
                );
            }

            [$method, $message] = $rule;

            if (!is_string($method) || !is_string($message)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Rule definition for "%s" must contain [string $method, string $message].',
                        $name
                    )
                );
            }

            $this->registerRule($name, $method, $message, $service);
        }
    }

}