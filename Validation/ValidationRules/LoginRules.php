<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom login validation rules.
 *
 * Delegates the actual validation logic to LoginLogic.
 * Rules are registered as Valitron custom rules via BaseRules.
 */
class LoginRules extends BaseRules
{
    /**
     * Factory used to build the LoginLogic instance.
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
     * Register all login validation rules with Valitron.
     *
     * Registers rules for password/username matching,
     * password/email matching, and TFA code verification.
     * Aliases are provided for backwards compatibility.
     *
     * @throws RuntimeException If LoginLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(LoginLogic::class);

        $this->registerRules(
            [
                // Validate password against a username field.
                'matchUsername' => [
                    'isValidPasswordUsernameMatch',
                    $this->_('and username do not match.'),
                ],

                // Alias for matchUsername.
                'matchesUsernamePassword' => [
                    'isValidPasswordUsernameMatch',
                    $this->_('and username do not match.'),
                ],

                // Validate password against an email field.
                'matchEmail' => [
                    'isValidPasswordEmailMatch',
                    $this->_('and email do not match.'),
                ],

                // Alias for matchEmail.
                'matchesEmailPassword' => [
                    'isValidPasswordEmailMatch',
                    $this->_('and email do not match.'),
                ],

                // Validate a two-factor authentication code.
                'checkTfaCode' => [
                    'isValidTfaCode',
                    $this->_('is not correct.'),
                ],
            ],
            $service
        );
    }
}