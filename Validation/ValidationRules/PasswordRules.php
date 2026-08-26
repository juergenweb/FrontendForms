<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom password validation rules.
 *
 * The actual validation logic is implemented inside
 * the PasswordLogic class.
 */
class PasswordRules extends BaseRules
{
    /**
     * Factory used to build the PasswordLogic instance.
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
     * Register all password validation rules with Valitron.
     *
     * @throws RuntimeException If PasswordLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(PasswordLogic::class);

        $this->registerRules(
            [
                'meetsPasswordConditions' => [
                    'validateMeetsPasswordConditions',
                    $this->_('does not meet the conditions.'),
                ],

                'safePassword' => [
                    'validateSafePassword',
                    $this->_(
                        'is listed among the most common passwords and is therefore not secure. Please choose a different one.'
                    ),
                ],

                'differentPassword' => [
                    'isDifferentPassword',
                    $this->_('must be different from the current password.'),
                ],

                'checkPasswordOfUser' => [
                    'isCurrentPassword',
                    $this->_('does not match the currently stored password.'),
                ],
            ],
            $service
        );
    }
}