<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class containing all custom validators
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: CustomRules.php
 * Created: 10.05.2026
 */

use DirectoryIterator;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use Valitron\Validator as V;

/**
 * Registers all custom Valitron validation rules for FrontendForms.
 *
 * Boots Valitron's language configuration and autoloads all rule classes
 * from the ValidationRules directory via RuleFactory.
 */
class CustomRules
{
    private RuleFactory $factory;

    /**
     * Create a new CustomRules instance, boot Valitron, and register all rules.
     *
     * @param Form $form Current FrontendForms form instance.
     *
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(private Form $form)
    {
        $this->factory = new RuleFactory(
            $form,
            $form->wire(),
            new LogicFactory(
                $form,
                $form->wire()->wire()
            )
        );

        $this->bootValitron();
        $this->rulesAutoloader();
    }

    /**
     * Configure Valitron's language directory and active language file.
     */
    private function bootValitron(): void
    {
        $config = $this->form->wire('config');

        V::langDir(
            $config->paths->siteModules
            . 'FrontendForms/valitron/'
        );

        V::lang('errormessages');
    }

    /**
     * Scan the ValidationRules directory and register every valid rule class.
     *
     * A file is loaded if it ends with "Rules.php", is not "BaseRules.php",
     * and its class implements RulesInterface.
     */
    private function rulesAutoloader(): void
    {
        $config = $this->form->wire('config');

        $path = $config->paths->siteModules
            . 'FrontendForms/Validation/ValidationRules';

        if (!is_dir($path)) {
            return;
        }

        foreach (new DirectoryIterator($path) as $file) {
            if (
                !$file->isFile()
                || $file->getExtension() !== 'php'
                || !str_ends_with($file->getFilename(), 'Rules.php')
                || $file->getBasename('.php') === 'BaseRules'
            ) {
                continue;
            }

            $className = __NAMESPACE__ . '\\' . $file->getBasename('.php');

            if (
                !class_exists($className)
                || !is_subclass_of($className, RulesInterface::class)
            ) {
                continue;
            }

            $this->factory
                ->create($className)
                ->register();
        }
    }
}