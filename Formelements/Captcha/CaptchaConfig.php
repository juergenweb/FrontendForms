<?php
declare(strict_types=1);

namespace FrontendForms;

/**
 * CaptchaConfig
 *
 * Plain value object holding the configurable options for a Form's
 * CAPTCHA: type, category, messages, placeholder, notes, description and its
 * position, label behaviour, and position relative to a reference field.
 * Carries no behaviour; used by CaptchaManager to build and render
 * the CAPTCHA input field.
 *
 * @package FrontendForms\Captcha
 */
final class CaptchaConfig
{
    public string $type = 'none'; // e.g. 'SimpleQuestionCaptcha', 'SliderCaptcha', 'none'
    public string $category = ''; // 'text' or 'image', derived from the type
    public array|null $position = null; // ['ref_field_name' => 'before'|'after']
    public string|null $successMsg = ''; // success message shown after valid CAPTCHA input
    public string|null $errorMsg = ''; // overwrites the default CAPTCHA validation error message
    public string|null $requiredErrorMsg = ''; // overwrites the default "required" error message
    public string|null $notes = ''; // notes text for the CAPTCHA input field
    public string|null $description = ''; // description text for the CAPTCHA input field
    public string|null $descriptionPosition = ''; // beforeLabel, afterLabel or afterInput
    public string|null $placeholder = ''; // placeholder text for the CAPTCHA input field
    public bool $removeLabel = false; // remove the label tag from the CAPTCHA field
    public bool $useLabelAsPlaceholder = false; // display the label text as placeholder instead
    public bool $showValueOnSameQuestionAgain = false; // keep entered value if the same random question repeats
}
