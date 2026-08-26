<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * InputFrameworkRenderer
 *
 * Strategy interface for rendering a single Inputfields element's markup
 * for a specific CSS framework (Bootstrap5, Pico2, the generic default
 * used as a fallback for every other framework, ...).
 *
 * Implementations are pure: they read state from the given $field (via its
 * public getters) and return a markup string - they do not hold any state
 * of their own between calls.
 *
 * @package FrontendForms\Rendering
 */
interface InputFrameworkRenderer
{
    /**
     * @param Inputfields $field The field being rendered.
     * @param string $className The field's class name (e.g. "InputCheckbox").
     * @param string $input The already-rendered raw <input>/<select>/... markup.
     * @return string
     */
    public function render(Inputfields $field, string $className, string $input): string;
}