<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * General abstract class for each HTML element that can be created via the Tag class.
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Element.php
     * Created: 03.07.2022
     */

    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    abstract class Element extends Tag
    {
        protected ?Wrapper $wrapper = null; // wrapper object
        protected array|null $conditions = null; // string containing the conditions as json string
        protected bool $contains_conditions = false; // bool value if conditions are used on this input field

        /**
         * @param string|null $id
         * @throws WireException
         * @throws WirePermissionException
         */
        public function __construct(?string $id = null)
        {
            parent::__construct();
            if (is_string($id)) {
                $this->setAttribute('id', $id);// set id if it was set inside the constructor
            }
        }

        /**
         * Get the inputfield dependencies (conditions)
         * @return array|null
         */
        public function getConditions(): array|null
        {
            return $this->conditions;
        }

        /**
         * Get the value, if this element contains field conditions (true) or not(false)
         * @return bool
         */
        public function containsConditions(): bool
        {
            return $this->contains_conditions;
        }

        /**
         * Base function for creation of the conditions array
         * @param string $action
         * @param array $rules
         * @param string $logic
         * @param string $container
         * @return void
         */
        protected function get_mf_conditional_rules(string $action, array $rules, string $logic = 'or', string $container = '.fieldwrapper'): void
        {
            $rule = array(
                'container' => $container,
                'action' => $action,
                'rules' => $rules,
                'logic' => $logic,
            );
            // a fieldwrapper is always necessary in this case to show or hide an element if it is a child of the Inputfield class
            if (is_subclass_of($this, 'FrontendForms\Inputfields')) {
                $this->useFieldWrapper(true);
                $this->getFieldWrapper()->setAttribute('class', 'fieldwrapper'); // important 
            } else {
                // add a wrapper element for showing and hiding
                if(str_starts_with($container, '.')){
                   $attribute = 'class';
                   $value = ltrim($container, '.');
                } else if (str_starts_with($container, '#')){
                    $attribute = 'id';
                    $value = ltrim($container, '#');
                } else {
                    $attribute = 'class';
                    $value = $container;
                }

                $this->wrap()->setAttribute($attribute, $value);
            }
            $this->conditions = $rule;
            $this->contains_conditions = true;
        }



        /**
         * Method to set a hideIf condition
         * @param array $rules
         * @param string $logic
         * @param string $container
         * @return void
         */
        public function hideIf(array $rules, string $logic = 'or', string $container = '.fieldwrapper'): void
        {
            $this->get_mf_conditional_rules('hide', $rules, $logic, $container);
        }

        /**
         * Method to set a showIf condition
         * @param array $rules
         * @param string $logic
         * @param string $container
         * @return void
         */
        public function showIf(array $rules, string $logic = 'or', string $container = '.fieldwrapper'): void
        {
            $this->get_mf_conditional_rules('show', $rules, $logic, $container);
            // add hidden attribute to field wrapper
            if (is_subclass_of($this, 'FrontendForms\Inputfields')) {
                $this->getFieldWrapper()->setAttribute('hidden');
            }
        }

        /**
         * Method to set a disableIf condition
         * @param array $rules
         * @param string $logic
         * @param string $container
         * @return void
         */
        public function disableIf(array $rules, string $logic = 'or', string $container = '.fieldwrapper'): void
        {
            $this->get_mf_conditional_rules('disable', $rules, $logic, $container);
        }

        /**
         * Method to set a enableIf condition
         * @param array $rules
         * @param string $logic
         * @param string $container
         * @return void
         */
        public function enableIf(array$rules, string $logic = 'or', string $container = '.fieldwrapper'): void
        {
            $this->get_mf_conditional_rules('enable', $rules, $logic, $container);
            // add disabled attribute to input field
            $this->setAttribute('disabled');
        }


        /**
         * Add a wrapper around an element (tag)
         * By default it is a div container, but you can change it to whatever you want
         * @return Wrapper - returns a wrapper object
         */
        public function wrap(): Wrapper
        {
            $this->wrapper = new Wrapper();
            return $this->wrapper;
        }

        /**
         * Remove a wrapper if it is present
         * @return void
         */
        public function removeWrap(): void
        {
            unset($this->wrapper);
        }

        /**
         * Returns the wrapper object if present
         * @return Wrapper|null
         */
        public function getWrap(): ?Wrapper
        {
            return $this->wrapper;
        }
    }
