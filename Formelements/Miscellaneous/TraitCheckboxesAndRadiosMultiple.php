<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: TraitCheckboxesAndRadiosMultiple.php
 * Created: 21.09.2022 
 */


trait TraitCheckboxesAndRadiosMultiple
{

    protected Wrapper $multipleWrapper; // the wrapper object over all boxes/radios inputs
    protected Wrapper $topLabelWrapper; // the wrapper object for the top label
    protected string $topLabelText = ''; // set a top label for radios and checkboxes


    /**
     * Get the outer checkbox wrapper for further manipulations
     * @return Wrapper
     */
    public function getMultipleWrapper(): Wrapper
    {
        return $this->multipleWrapper;
    }

    /**
     * Get the label wrapper for the top label for further manipulations
     * @return Wrapper
     */
    public function getTopLabelWrapper(): Wrapper
    {
        return $this->topLabelWrapper;
    }

    /**
     * Set the value of the top label over all checkboxes
     * @param string $topLabelText
     * @return InputRadioMultiple|InputCheckboxMultiple|TraitCheckboxesAndRadiosMultiple
     */
    public function setTopLabelText(string $topLabelText): self
    {
        $this->topLabelText = $topLabelText;
        return $this;
    }

    /**
     * Get the value of the top label
     * @return string
     */
    public function getTopLabelText(): string
    {
        return $this->topLabelText;
    }

    /**
     * Change the markup and add the CSS class depending on if the alignment of radios or checkboxes (multiple)
     * is horizontal or vertical
     * @param string $markupType
     * @param InputCheckboxMultiple|InputRadioMultiple $input
     * @return string
     */
    protected function setCheckBoxRadioAlignmentClass(string $markupType, InputCheckboxMultiple|InputRadioMultiple $input, string $out): string
    {

                $wrapper = $input->getMultipleWrapper();
                if ($input->directionHorizontal) {
                    $class = 'horizontalWrapperClass';
                } else {
                    $class = 'verticalWrapperClass';
                }

                $wrapper->setCSSClass($class);
                $wrapper->setContent($out);

                return $wrapper->render();

    }

}
