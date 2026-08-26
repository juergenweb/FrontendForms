<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding methods shared by checkbox-multiple and radio-multiple elements
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TraitCheckboxesAndRadiosMultiple.php
 * Created: 21.09.2022
 * Optimized via Claude AI 05.05.26
 */


trait TraitCheckboxesAndRadiosMultiple
{
    protected Wrapper $multipleWrapper; // the wrapper object over all boxes/radios inputs

    /**
     * Get the outer checkbox wrapper for further manipulations
     * @return Wrapper
     */
    public function getMultipleWrapper(): Wrapper
    {
        return $this->multipleWrapper;
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
        $wrapper->setCSSClass($input->directionHorizontal ? 'horizontalWrapperClass' : 'verticalWrapperClass');
        $wrapper->setContent($out);
        return $wrapper->render();

    }


}