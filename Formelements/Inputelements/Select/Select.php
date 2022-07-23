<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a select element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Select.php
 * Created: 03.07.2022
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Select extends Inputfields
{

    use TraitOption, TraitPWOptions;

    /**
     * @param string $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->setTag('select');
        $this->setCSSClass('selectClass');
    }

    /**
     * Use a PW field of the type SelectOptions to create the options;
     * @param string $fieldName
     * @return void
     * @throws WireException
     * @throws WirePermissionException
     */
    public function setOptionsFromField(string $fieldName): void
    {
        $this->setOptionsFromFieldType($fieldName, 'addOption');
    }

    /**
     * Render the select input
     * @return string
     */
    public function ___renderSelect(): string
    {
        $out = '';
        if ($this->options) {
            $options = '';
            foreach ($this->options as $option) {
                if (in_array($option->getAttribute('value'), $this->getDefaultValue())) {
                    $option->setAttribute('selected');
                }
                if (in_array($option->getAttribute('value'), (array)$this->getPostValue())) {
                    $option->setAttribute('selected');
                }
                $options .= $option->render();
            }
            $this->setContent($options);
            $out = $this->renderNonSelfclosingTag($this->getTag());
        }
        return $out;
    }

}
