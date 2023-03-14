<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for linking Valitron library with Inputfield class and form class to set custom values at form validation
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: ValitronAPI.php
 * Created: 03.07.2022
 */

use Valitron\Validator;

class ValitronAPI
{

    protected Validator $valitron; // the valitron object
    protected string $validator = ''; // name of the validator
    protected string $customMessage = ''; // the custom error message
    protected string $customFieldname = ''; // the custom field name

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->valitron = new Validator(array());
    }

    /**
     * Get the name of the field validator (fe required)
     * @return string|null
     */
    public function getValidator(): ?string
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
     * Checks first if the validator method exists, otherwise does nothing
     * Check https://processwire.com/api/ref/sanitizer/ for all sanitizer methods
     * @param string $validator - the name of the validator
     * @param array $options
     * @return array
     */
    public function setRule(string $validator, array $options = []): array
    {
        $validator = trim($validator);
        return ['name' => $validator, 'options' => $options];
    }


    /**
     * Set the custom error message of the field validator
     * @param string $msg
     * @return string
     */
    public function setCustomMessage(string $msg): string
    {
        $this->customMessage = $msg;
        return $msg;
    }

    /**
     * Set the custom field name for the error message
     * @param string $fieldname
     * @return string
     */
    public function setCustomFieldName(string $fieldname): string
    {
        $this->customFieldname = $fieldname;
        return $fieldname;
    }

}
