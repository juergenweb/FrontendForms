<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all custom spam validation rules.
 *
 * The actual validation logic is implemented
 * inside the SpamLogic class.
 */
class SpamRules extends BaseRules
{
    /**
     * Factory used to build the SpamLogic instance.
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
     * Register all spam validation rules with Valitron.
     *
     * @throws RuntimeException If SpamLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(SpamLogic::class);

        $this->registerRules(
            [
                'checkContentForSpam' => [
                    'validateContentForSpam',
                    $this->_(
                        'contains content that appears to be spam. Please avoid using more than 2 links, text shorter than 50 characters, excessive use of capital letters, common spam keywords, or multiple exclamation marks in a row (e.g. !!!!!).'
                    ),
                ],

                'checkCaptcha' => [
                    'validateCaptcha',
                    $this->_(
                        'is not correct.'
                    ),
                ],

                'checkSliderCaptcha' => [
                    'validateSliderCaptcha',
                    $this->_(
                        'was not solved correctly.'
                    ),
                ],
            ],
            $service
        );
    }
}