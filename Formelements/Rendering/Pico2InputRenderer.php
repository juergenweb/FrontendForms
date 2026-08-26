<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Pico2InputRenderer
 *
 * Pico CSS 2 specific markup: checkboxes/radios wrap the input, label text,
 * and (optionally) a required-asterisk together inside the <label> itself,
 * with the asterisk's HTML tags stripped since Pico renders labels as plain
 * text in this position.
 *
 * @package FrontendForms\Rendering
 */
final class Pico2InputRenderer implements InputFrameworkRenderer
{
    public function render(Inputfields $field, string $className, string $input): string
    {
        $out = '';
        $errormsg = $field->getErrorMessage()->render() . PHP_EOL;
        $successmsg = $field->getSuccessMessage()->render() . PHP_EOL;
        $description = $field->getDescription();

        switch ($className) {
            case 'InputHidden':
                $field->removeAttribute('class');
                $input_markup = $input;
                break;

            case 'InputCheckbox':
            case 'InputRadio':
            case 'Privacy':
            case 'SendCopy':
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                $asterisk = '';
                if ($field->getRules() && array_key_exists('required', $field->getRules())) {
                    $asterisk = $field->getShowAsteriskConfig()
                        ? strip_tags($field->getLabel()->renderAsterisk())
                        : '';
                }
                $field->getLabel()->setContent($input . $field->getLabel()->getText() . $asterisk . $errormsg . $successmsg);
                $field->getLabel()->disableAsterisk();
                $out .= $field->getLabel()->render() . PHP_EOL;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup = '';
                break;

            default:
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $out .= $description->render();
                }
                if ($field->getLabel()->getText()) {
                    $out .= $field->getLabel()->render() . PHP_EOL;
                }
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup = $input . $errormsg . $successmsg;
        }

        if ($field->getUsageOfInputWrapper()) {
            $field->getInputWrapper()->setContent($input_markup);
            $out .= $field->getInputWrapper()->render() . PHP_EOL;
        } else {
            $out .= $input_markup;
        }

        $out .= $field->getNotes()->render();

        if ($description->getText() && $description->getPosition() === 'afterInput') {
            $out .= $description->render();
        }

        return $out;
    }
}