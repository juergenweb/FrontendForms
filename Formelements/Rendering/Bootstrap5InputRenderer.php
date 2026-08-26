<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Bootstrap5InputRenderer
 *
 * Bootstrap 5 specific markup: adds Bootstrap validation state classes
 * (input_errorClass/input_successClass) to the label depending on whether
 * the field currently has an error message and whether the form was posted.
 *
 * @package FrontendForms\Rendering
 */
final class Bootstrap5InputRenderer implements InputFrameworkRenderer
{
    public function render(Inputfields $field, string $className, string $input): string
    {
        $out = '';
        $content = '';
        $errormsg = $field->getErrorMessage()->render() . PHP_EOL;
        $successmsg = $field->getSuccessMessage()->render() . PHP_EOL;
        $description = $field->getDescription();

        if ($field->getErrorMessage()->getText()) {
            $field->getLabel()->setCSSClass('input_errorClass');
        } elseif ($_POST) {
            $field->getLabel()->setCSSClass('input_successClass');
        }

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
                    $content .= $description->render();
                }
                $field->getLabel()->removeAttributeValue('class', $field->getCSSClass('labelClass'));
                $field->getLabel()->setCSSClass('checklabelClass');
                $content .= $input . $field->getLabel()->render() . PHP_EOL;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $content .= $description->render();
                }
                $input_markup = $content . $errormsg . $successmsg;
                break;

            default:
                if ($description->getText() && $description->getPosition() === 'beforeLabel') {
                    $content .= $description->render();
                }
                if ($field->getLabel()->getText()) {
                    if (in_array($className, ['InputRadioMultiple', 'InputCheckboxMultiple'])) {
                        $field->getLabel()->setCSSClass(
                            $field->getErrorMessage()->getText() ? 'input_errorClass' : 'input_successClass'
                        );
                    }
                    $content .= $field->getLabel()->render() . PHP_EOL;
                }
                $content .= $input;
                if ($description->getText() && $description->getPosition() === 'afterLabel') {
                    $content .= $description->render();
                }
                $input_markup = $content . $errormsg . $successmsg;
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