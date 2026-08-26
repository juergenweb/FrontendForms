<?php

declare(strict_types=1);

namespace FrontendForms;

use RuntimeException;
use ProcessWire\WireException;

/**
 * Registers all custom date validation rules.
 *
 * The actual validation logic is implemented
 * inside the DateLogic class.
 */
class DateRules extends BaseRules
{
    /**
     * Factory used to build the DateLogic instance.
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
     * Register all date validation rules with Valitron.
     *
     * @throws RuntimeException If DateLogic cannot be instantiated.
     */
    public function register(): void
    {
        $service = $this->logicFactory->create(DateLogic::class);

        $this->registerRules(
            [
                'week' => [
                    'isWeek',
                    $this->_(
                        'is not a valid week. Use the format YYYY-Www (e.g. 2023-W06).'
                    ),
                ],
                'month' => [
                    'isMonth',
                    $this->_(
                        'is not a valid month. Use the format YYYY-MM (e.g. 2023-06).'
                    ),
                ],
                'time' => [
                    'isTime',
                    $this->_(
                        'is not a valid time. Use the format HH:MM or HH:MM:SS (e.g. 14:30).'
                    ),
                ],
                'dateBeforeField' => [
                    'isDateBeforeField',
                    $this->_('must be before %s.'),
                ],
                'dateAfterField' => [
                    'isDateAfterField',
                    $this->_('must be after %s.'),
                ],
                'dateWithinDaysRange' => [
                    'validateDateInsideOfDaysRange',
                    $this->_('must be within the allowed time range.'),
                ],
                'dateOutsideOfDaysRange' => [
                    'validateDateOutsideOfDaysRange',
                    $this->_('must be outside the restricted time range.'),
                ],
            ],
            $service
        );
    }
}