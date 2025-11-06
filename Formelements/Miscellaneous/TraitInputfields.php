<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Trait for adding some options to inputfields on multi-step forms
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: TraitInputfields.php
 * Created: 06.11.2025
 */

trait TraitInputfields
{

    protected bool $removeFromLastStep = false;
    protected string $customListLabel = '';

    /**
     * Remove a form field from previous steps in the final step
     * This can be used fe to remove checkbox values from the final form values if the are only there for agreement
     * @return self
     */
    public function removeFromLastStep(): self
    {
        $this->removeFromLastStep = true;
        return $this;
    }

    public function getRemoveFromLastStep(): bool
    {
        return $this->removeFromLastStep;
    }


    /**
     * Overwrite the default label with a custom label in the final list table inside multi-step forms
     * @param string $label
     * @return $this
     */
    public function setCustomListLabel(string $label): self
    {
        $this->customListLabel = $label;
        return $this;
    }

    /**
     * Get the custom list label for the current field
     * @return string
     */
    public function getCustomListLabel(): string
    {
        return $this->customListLabel;
    }

}
