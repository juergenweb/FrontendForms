<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Trait for adding tag replacement functionality
     * Will be used on Label, Description, Notes, SuccessMessage and ErrorMessage
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: TraitTags.php
     * Created: 12.07.2024
     */


    trait TraitTags
    {
        protected null|string $customTag = null;

        /**
         * Overwrite the default setTag() method to set a new property customTag
         * @param string $tag
         * @return \FrontendForms\TraitTags
         */
        public function setTag(string $tag): self
        {
            $this->customTag = $tag;
            return parent::setTag($tag);
        }

        /**
         * Get the custom tag if set or null
         * @return string|null
         */
        public function getCustomTag(): null|string
        {
            return $this->customTag;
        }

    }
