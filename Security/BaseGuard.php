<?php

declare(strict_types=1);

namespace FrontendForms;

use ProcessWire\Wire;
use ProcessWire\WireInputData;

/**
 * Base class for form security guards.
 *
 * Provides the dependencies shared by every guard (submitted input data,
 * the form instance being checked, and the alert used to display
 * security-related messages).
 */
abstract class BaseGuard extends Wire
{
    /**
     * @param WireInputData $input Submitted form input data.
     * @param Form          $form  The form instance this guard checks.
     * @param Alert         $alert Alert instance used to display security-related messages.
     */
    public function __construct(
        protected readonly WireInputData $input,
        protected readonly Form $form,
        protected readonly Alert $alert
    ) {
        parent::__construct();
    }
}