<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class for creating an input password element
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: InputPassword.php
     * Created: 03.07.2022
     * Optimized via Claude AI 05.05.26
     */

    use Exception;
    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class InputPassword extends InputText
    {
        protected $passwordField; // the password field object
        protected $passwordModule; // the password fieldtype module
        protected string $passwordFieldName = 'pass';
        protected bool $showPasswordRequirements = true;
        protected int|string $minLength = '6'; // the min length of the password as set inside the input field configuration
        protected InputCheckbox $createPasswordToggle; // The password toggle checkbox object
        protected bool $showPasswordToggle = true; // show or hide the password toggle checkbox

        /**
         * @param string $id
         * @throws WireException
         * @throws WirePermissionException
         * @throws Exception
         */
        public function __construct(string $id)
        {
            parent::__construct($id);
            $this->setAttribute('type', 'password');
            $this->passwordField = $this->wire('fields')->get($this->passwordFieldName);
            $this->passwordModule = $this->wire('modules')->get('InputfieldPassword');
            $this->minLength = $this->getMinLength();
            $this->setRule('meetsPasswordConditions');// add meetsPasswordConditions Validator by default
        }

        /**
         * Get the minimum password length from the password field configuration in the backend
         * @return string
         */
        protected function getMinLength(): string
        {
            return (string)($this->passwordField->minlength ?? $this->passwordModule->minlength);
        }

        /**
         * Show the password requirements at the password field
         * @param bool $show
         * @return \FrontendForms\InputPassword
         */
        public function showPasswordRequirements(bool $show = true): self
        {
            $this->showPasswordRequirements = $show;
            return $this;
        }

        /**
         * Show (default) or hide the toggle checkbox for the password field
         * @param bool $show
         * @return $this
         */
        public function showPasswordToggle(bool $show = true): self
        {
            $this->showPasswordToggle = $show;
            return $this;
        }

        /**
         * Create a password toggle checkbox to the input element
         * @return InputCheckbox
         */
        protected function createPasswordToggle(): InputCheckbox
        {
            $toggle = new InputCheckbox('pwtoggle');
            $toggle->setLabel($this->_('Show password'))->setAttribute('class', 'pwtoggleLabel');
            $toggle->setAttribute('class', 'pwtoggle');
            $toggle->setAttribute('data-toggle', $this->getAttribute('id'));
            $toggle->useInputWrapper(false);
            $toggle->useFieldWrapper(false);
            $toggle->removeAttribute('id');
            $this->createPasswordToggle = $toggle;
            return $toggle;
        }

        /**
         * Render the password input element
         * @return string
         * @throws WireException
         * @throws WirePermissionException
         */
        public function ___renderInputPassword(): string
        {

            // add the description to the password requirement text
            if ($this->showPasswordRequirements) {

                $descriptionText = $this->getDescription()->getText();
                $requirements = $this->renderPasswordRequirements();

                $description = ($descriptionText && $descriptionText !== $requirements)
                    ? $requirements . '<br>' . $descriptionText
                    : $requirements;

                $this->setDescription($description);
            }

            if ($this->showPasswordToggle) {
                $this->append($this->createPasswordToggle()->render());
            }

            return parent::___renderInputText();
        }

        /**
         * Render a text which informs about the requirements of a password as set in the backend
         * @return string|null
         * @throws WireException
         * @throws WirePermissionException
         */
        public function renderPasswordRequirements(): ?string
        {
            $conditions = $this->getPasswordConditions();

            if ($conditions) {
                return sprintf(
                    $this->_('The password must be at least %s characters and must contain characters of the following categories: %s.'),
                    $this->minLength,
                    $conditions
                );
            }

            return $this->minLength ? sprintf(
                $this->_('The password must be at least %s characters.'),
                $this->minLength
            ) : null;
        }

        /**
         * Show a hint for requirements for the password as set in the backend
         * @return string|null - returns a comma-separated list of all conditions or null if no conditions were set
         * @throws WireException
         * @throws WirePermissionException
         */
        protected function getPasswordConditions(): ?string
        {
            $requirements = array_unique(array_merge(
                (array)$this->passwordModule->requirements,
                (array)$this->passwordField->requirements
            ));

            if (in_array('none', $requirements)) {
                return null;
            }

            $conditions = array_map(
                fn($name) => $this->passwordModule->requirementsLabels[$name],
                $requirements
            );

            return implode(', ', $conditions);
        }
    }
