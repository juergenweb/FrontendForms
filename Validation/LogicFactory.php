<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Wire;
use RuntimeException;
use Throwable;

/**
 * Builds Logic and Helper instances with all dependencies recursively resolved.
 *
 * After construction, the ProcessWire instance and current Form instance are injected
 * into every instance that implements the corresponding interfaces/base classes.
 */
class LogicFactory
{
    /**
     * Create a new LogicFactory instance.
     *
     * @param Form $form Current FrontendForms form instance.
     * @param Wire $wire ProcessWire root instance.
     */
    public function __construct(
        private readonly Form $form,
        private readonly Wire $wire
    ) {
    }

    /**
     * Dependency map: Logic/Helper class => ordered list of constructor dependencies.
     *
     * The order of each dependency list must match the constructor parameter
     * order of the corresponding class.
     *
     * @var array<class-string, list<class-string>>
     */
    private array $map = [
        DateLogic::class => [
            DateHelper::class,
            FieldNameResolverHelper::class,
        ],
        EmailLogic::class => [
            EmailHelper::class,
        ],
        FileLogic::class => [
            FileHelper::class,
            MimeHelper::class,
        ],
        FinancialLogic::class => [
            FinancialHelper::class,
        ],
        LoginHelper::class => [
            UsernameHelper::class,
            EmailHelper::class,
        ],
        LoginLogic::class => [
            LoginHelper::class,
            FieldNameResolverHelper::class,
        ],
        MiscellaneousLogic::class => [
            MiscellaneousHelper::class,
        ],
        TextLogic::class => [
            TextHelper::class,
        ],
        PasswordLogic::class => [
            PasswordHelper::class,
            TextHelper::class,
        ],
        SpamHelper::class => [
            TextHelper::class,
        ],
        SpamLogic::class => [
            SpamHelper::class,
        ],
        UsernameLogic::class => [
            UsernameHelper::class,
        ],
        ZipLogic::class => [
            FileHelper::class,
            MimeHelper::class,
            ZipHelper::class,
        ],
    ];

    /**
     * Instance cache for the currently running create() call.
     *
     * Ensures that shared dependencies are only instantiated once per
     * top-level create() invocation, instead of once per reference.
     *
     * @var array<class-string, object>
     */
    private array $resolved = [];

    /**
     * Create an instance of the given class with all dependencies resolved.
     *
     * @param string $className Fully-qualified class name to instantiate.
     *
     * @return object The fully constructed instance.
     *
     * @throws RuntimeException If the class does not exist, a circular dependency
     *                          is detected, or instantiation fails.
     */
    public function create(string $className): object
    {
        // reset the instance cache for every top-level create() call
        $this->resolved = [];

        return $this->build($className, []);
    }

    /**
     * Recursively resolve and instantiate a class and all its dependencies.
     *
     * @param string              $className Fully-qualified class name to build.
     * @param list<class-string>  $stack     Chain of classes currently being resolved,
     *                                       used to detect circular dependencies.
     *
     * @return object The fully constructed instance with injected dependencies.
     *
     * @throws RuntimeException If the class does not exist, a circular dependency
     *                          is detected, or instantiation fails.
     */
    private function build(string $className, array $stack): object
    {
        if (!class_exists($className)) {
            throw new RuntimeException("Class not found: $className");
        }

        // return the cached instance if this class was already built in this run
        if (isset($this->resolved[$className])) {
            return $this->resolved[$className];
        }

        if (in_array($className, $stack, true)) {
            $chain = implode(' -> ', [...$stack, $className]);
            throw new RuntimeException("Circular dependency detected: $chain");
        }

        $stack[] = $className;

        $dependencies = $this->map[$className] ?? [];

        $dependencyInstances = [];

        foreach ($dependencies as $dependencyClass) {
            $dependencyInstances[] = $this->build($dependencyClass, $stack);
        }

        try {
            $instance = new $className(...$dependencyInstances);
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Class could not be instantiated: $className. Reason: " . $e->getMessage(),
                0,
                $e
            );
        }

        if ($instance instanceof Wire) {
            $instance->setWire($this->wire);
        }

        if ($instance instanceof BaseLogic || $instance instanceof BaseHelper) {
            $instance->setForm($this->form);
        }

        $this->resolved[$className] = $instance;

        return $instance;
    }
}