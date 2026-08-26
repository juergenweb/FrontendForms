<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom email validation rules.
 *
 * The actual validation logic is implemented inside
 * the EmailLogic class.
 */
class EmailRules extends BaseRules
{
    /**
     * Factory used to build the EmailLogic instance.
     */
    private LogicFactory $logicFactory;

    /**
     * Inject the LogicFactory used to create the validation service.
     *
     * @param LogicFactory $factory The logic factory instance.
     */
    public function setLogicFactory(LogicFactory $factory): void
    {
        $this->logicFactory = $factory;
    }

    /**
     * Register all email validation rules with Valitron.
     *
     * @throws RuntimeException If EmailLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(EmailLogic::class);

        $this->registerRules(
            [
                'uniqueEmail' => [
                    'isEmailUnique',
                    $this->_('must be unique.'),
                ],
            ],
            $service
        );
    }
}