<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom financial validation rules.
 *
 * The actual validation logic is implemented
 * inside the FinancialLogic class.
 */
class FinancialRules extends BaseRules
{
    /**
     * Factory used to build the FinancialLogic instance.
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
     * Register all financial validation rules with Valitron.
     *
     * @throws RuntimeException If FinancialLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(FinancialLogic::class);

        $this->registerRules(
            [
                'checkIban' => [
                    'validateIban',
                    $this->_('is not a valid IBAN.'),
                ],
                'checkBic' => [
                    'validateBic',
                    $this->_('is not a valid BIC.'),
                ],
            ],
            $service
        );
    }
}