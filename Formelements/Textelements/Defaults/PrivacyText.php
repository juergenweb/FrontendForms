<?php

    namespace FrontendForms;

    /**
     * Class to create a "By submitting this form you accept our Terms and Privacy Policy" text for a form
     * This could be used instead of the Privacy checkbox
     * This text includes a link to the Privacy Policy page if set
     */
    class PrivacyText extends TextElements
    {

        protected int|null $policyPageId = null;
        protected string $privacy = '';
        protected bool $linkExists = false;
        protected Link $policyLink;

        public function __construct(?string $id = 'privacy-text')
        {
            parent::__construct($id);

            // set default values
            $this->setText($this->_('By submitting this form you agree to our %s.'));
            $this->privacy = $this->_('Terms of use and Privacy Policy');
            $this->setAttribute('class', 'privacy-text');

            // create the link instance for the privacy link
            if (!array_key_exists('input_privacypageselect', $this->frontendforms))
                $this->frontendforms['input_privacypageselect'] = 'int';

            $this->policyLink = new Link();
            $this->linkExists = Privacy::setPrivacyPageUrl($this->frontendforms, $this->policyLink);
            $this->policyLink->setLinkText($this->privacy);

        }

        /**
         * Get the privacy link object for further manipulations if needed
         * @return \FrontendForms\Link
         */
        public function getPolicyLink(): Link
        {
            return $this->policyLink;
        }

        /**
         * Render the privacy policy link
         * @return string
         */
        public function renderPolicyLink(): string
        {
            $out = '';

            if ($this->linkExists) {
                $out = $this->policyLink->render();
            }
            return $out;
        }

        /**
         * Set the page id of the privacy policy page
         * @param int $id
         * @return $this
         */
        public function setPolicyPageId(int $id): self
        {
            $this->policyPageId = $id;
            return $this;
        }

        /**
         * Render the text string
         * @return string
         */
        public function ___render(): string
        {
            $privacy = $this->renderPolicyLink() != '' ? $this->renderPolicyLink() : $this->privacy;
            $this->setText(sprintf($this->getText(), $privacy));
            return parent::___render();
        }

    }
