<?php

declare(strict_types=1);

namespace FrontendForms;

interface RulesInterface
{
    /**
     * Register validation rules.
     */
    public function register(): void;
}