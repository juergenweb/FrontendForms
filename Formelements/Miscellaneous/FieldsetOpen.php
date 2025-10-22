<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating field set opener
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: FieldsetOpen.php
 * Created: 03.07.2022
 */

class FieldsetOpen extends Element
{
    protected Legend $legend;
    protected bool $useID = false;

    public function __construct(?string $id = null)
    {
       if(!is_null($id)){
            $this->useID = true;
       }
        parent::__construct($id);
       $this->removeAttribute('id');
        $this->legend = new Legend(); // instantiate legend object
        $this->setTag('fieldset');
        $this->setCSSClass('fieldsetClass');
    }

    /**
     * Set the text for the legend
     * @param string $legendText
     * @return Legend
     */
    public function setLegend(string $legendText): Legend
    {
        $this->legend->setText($legendText);
        return $this->legend;
    }

    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * Render the fieldset open tag
     * @return string
     */
    public function ___render(): string
    {
        $this->append($this->legend->render());
        $this->removeAttribute('name');
        if(!$this->useID){
            $this->removeAttribute('id');
        }
        return $this->renderSelfclosingTag($this->getTag());
    }
}
