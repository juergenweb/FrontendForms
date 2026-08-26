<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;

/**
 * Registers all text validation rules.
 *
 * The actual validation logic is implemented
 * inside the TextLogic class.
 */
class TextRules extends BaseRules
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
     * Register all text validation rules with Valitron.
     *
     * @throws RuntimeException If TextLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(TextLogic::class);

        $this->registerRules(
            [
                'exactValue' => [
                    'validateExactValue',
                    $this->_('does not have the expected value.'),
                ],

                'differentValue' => [
                    'validateNoneExactValue',
                    $this->_('is not different from the given value.'),
                ],

                'compareTexts' => [
                    'validateTextComparison',
                    $this->_('does not contain the correct answer.'),
                ],

                'cyrillicname' => [
                    'validateCyrillicName',
                    $this->_(
                        'contains characters that are not allowed. A Cyrillic name may only contain lowercase and uppercase letters (а-я) and hyphens, but no whitespace.'
                    ),
                ],

                // Alias of cyrillicname with camelcase
                'cyrillicName' => [
                    'validateCyrillicName',
                    $this->_(
                        'contains characters that are not allowed. A Cyrillic name may only contain lowercase and uppercase letters (а-я) and hyphens, but no whitespace.'
                    ),
                ],

                'noLetters' => [
                    'validateNoLetters',
                    $this->_('contains letters, but this is not allowed.'),
                ],

                'noNumbers' => [
                    'validateNoNumbers',
                    $this->_('contains at least one number, but this is not allowed.'),
                ],

                'uniqueStringValueOfPWField' => [
                    'validateUniqueStringValueOfPWField',
                    $this->_('is already in use. Please enter a different value.'),
                ],

                'firstAndLastname' => [
                    'validateNames',
                    $this->_('contains characters that are not allowed in names.'),
                ],
            ],
            $service
        );
    }
}