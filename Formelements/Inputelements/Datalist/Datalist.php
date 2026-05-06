<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a datalist element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Datalist.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Datalist extends InputText
{

    use TraitOption, TraitPWOptions, TraitOptionElements, TraitInputfields;

    protected string $listID = '';

    /**
     * @param string $id
     * @throws Exception
     */
    public function __construct(string $id)
    {
        parent::__construct($id);
        $this->listID = $id;
        $this->setAttribute('list', 'datalist-' . $id);
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
     * Render the datalist input
     * @return string
     */
    public function ___renderDatalist(): string
    {
        if (empty($this->options)) {
            return '';
        }

        $options = '';

        foreach ($this->options as $option) {
            if ($option->hasAttribute('selected') && !$this->hasAttribute('value')) {
                $this->setAttribute('value', $option->getAttribute('value'));
                $option->removeAttribute('selected');
            }
            $options .= $option->render();
        }

        $this->append('<datalist id="datalist-' . $this->listID . '">' . $options . '</datalist>');

        return $this->renderInput();
    }

}
