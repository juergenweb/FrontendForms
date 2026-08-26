<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom username validation rules.
 *
 * The actual validation logic is implemented inside
 * the UsernameLogic class.
 */
class UsernameRules extends BaseRules
{

    /**
     * Factory used to build the UsernameLogic instance.
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
     * Register all username validation rules with Valitron.
     *
     * @throws RuntimeException If UsernameLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(UsernameLogic::class);

        $this->registerRules(
            [
                'uniqueUsername' => [
                    'isUsernameUnique',
                    $this->_('is already in use. Please choose a different username.'),
                ],

                'usernameSyntax' => [
                    'isValidUsernameSyntax',
                    $this->_(
                        'does not meet the username requirements. A username must be between 3 and 30 characters long and may only contain lowercase letters (a-z), numbers, hyphens, underscores, and dots, with no spaces.'
                    ),
                ],
            ],
            $service
        );
    }
}