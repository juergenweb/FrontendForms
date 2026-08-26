<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all miscellaneous validation rules.
 *
 * The actual validation logic is implemented
 * inside the MiscellaneousLogic class.
 */
class MiscellaneousRules extends BaseRules
{
    /**
     * Factory used to build the MiscellaneousLogic instance.
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
     * Register all miscellaneous validation rules with Valitron.
     *
     * @throws RuntimeException If MiscellaneousLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(MiscellaneousLogic::class);

        $this->registerRules(
            [
                'checkHex' => [
                    'validateHexValue',
                    $this->_('is not a valid hexadecimal color code.'),
                ],

                'requiredIfEqual' => [
                    'validateRequiredIfEqual',
                    $this->_('is required.'),
                ],

                'requiredIfEmpty' => [
                    'validateRequiredIfEmpty',
                    $this->_('is required.'),
                ],

                'requiredIfNotEmpty' => [
                    'validateRequiredIfNotEmpty',
                    $this->_('is required.'),
                ],
            ],
            $service
        );
    }
}