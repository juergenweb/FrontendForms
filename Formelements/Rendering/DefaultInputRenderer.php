<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * DefaultInputRenderer
 *
 * The generic, framework-agnostic markup used both as its own thing (no
 * framework selected) and as the fallback for every framework that has no
 * dedicated renderer of its own - currently that includes UIkit3 (its
 * renderer explicitly delegates here) and Bulma (which has no dedicated
 * ___renderBulma() method on Inputfields at all, so Inputfields::___render()
 * falls through to this one automatically via its method_exists() check).
 *
 * @package FrontendForms\Rendering
 */
final class DefaultInputRenderer implements InputFrameworkRenderer
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
                $field->getLabel()->removeAttributeValue('class', $field->getCSSClass('checklabel'));
                if ($field->getAppendLabel()) {
                    $field->getLabel()->setContent($field->getLabel()->getText());
                    $input_markup = $input . PHP_EOL . $field->getLabel()->render() . PHP_EOL;
                } else {
                    $field->getLabel()->setContent($input . $field->getLabel()->getText());
                    $input_markup = $field->getLabel()->render() . PHP_EOL;
                }
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $out .= $description->render();
                }
                $input_markup .= $errormsg . $successmsg;
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