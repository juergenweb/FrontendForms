<?php

/**
 * Class for linking Valitron library with Inputfield class and form class to set custom values at form validation
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: ValitronAPI.php
 * Created: 03.07.2022
 */

declare(strict_types=1);

namespace FrontendForms;

class ValitronAPI
{
    protected string $validator = ''; // name of the validator

    /**
     * Get the name of the field validator (fe required)
     * @return string
     */
    public function getValidator(): string
    {
        return $this->validator;
    }

    /**
     * Set the name of the field validator (fe required)
     * @param string $validator
     * @return void
     */
    public function setValidator(string $validator): void
    {
        $this->validator = $validator;
    }

    /**
     * Set a validator rule to validate the input value
     * @param string $validator - the name of the validator
     * @param array $options
     * @return array
     */
    public function setRule(string $validator, array $options = []): array
    {
        $validator = trim($validator);
        return ['name' => $validator, 'options' => $options];
    }

}