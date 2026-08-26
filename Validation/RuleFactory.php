<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Wire;
use RuntimeException;
use Throwable;

/**
 * Instantiates ValidationRule classes and injects ProcessWire, LogicFactory,
 * and the current Form dependencies.
 */
class RuleFactory
{
    /**
     * @param Form         $form         The form instance this factory belongs to.
     * @param Wire         $wire         ProcessWire's Wire instance, injected into rule instances if applicable.
     * @param LogicFactory $logicFactory Factory used to build logic instances, injected into rules if applicable.
     */
    public function __construct(
        private readonly Form $form,
        private readonly Wire $wire,
        private readonly LogicFactory $logicFactory
    ) {
    }

    /**
     * Create a new validation rule instance by class name.
     *
     * Instantiates the given class and injects ProcessWire (if the instance
     * extends Wire), the current Form (if the instance exposes a setForm()
     * method), and the LogicFactory (if the instance exposes a
     * setLogicFactory() method).
     *
     * @param string $className Fully qualified class name of the rule to instantiate.
     * @return object The instantiated (and dependency-injected) rule object.
     * @throws RuntimeException If the class does not exist or cannot be instantiated.
     */
    public function create(string $className): object
    {
        if (!class_exists($className)) {
            throw new RuntimeException("Rule class not found: $className");
        }

        try {
            $instance = new $className();
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Rule class could not be instantiated: $className. Reason: " . $e->getMessage(),
                0,
                $e
            );
        }

        if ($instance instanceof Wire) {
            $instance->setWire($this->wire);
        }

        if (method_exists($instance, 'setForm')) {
            $instance->setForm($this->form);
        }

        if (method_exists($instance, 'setLogicFactory')) {
            $instance->setLogicFactory($this->logicFactory);
        }

        return $instance;
    }
}