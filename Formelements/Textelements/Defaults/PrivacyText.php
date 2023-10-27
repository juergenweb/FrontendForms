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
        protected Link $policyLink;

        public function __construct(?string $id = null)
        {
            parent::__construct($id);

            // set default values
            $this->setText($this->_('By submitting you agree to our %s.'));
            $this->privacy = $this->_('Terms and Privacy Policy');
            $this->setCSSClass('policy-text');

            // create the link instance for the privacy link
            $this->policyLink = new Link();
            // set the privacy page id if it is configured inside the backend-configuration
            if ($this->frontendforms['input_privacy']) {
                $this->setPolicyPageId((int)$this->frontendforms['input_privacy']);
            }
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
         * @throws \ProcessWire\WireException
         * @throws \ProcessWire\WirePermissionException
         */
        public function renderPolicyLink(): string
        {
            $out = '';

            if (!is_null($this->policyPageId)) {

                // get the privacy page object
                $privacyPage = $this->wire('pages')->get($this->policyPageId);
                if ($privacyPage) {
                    $this->policyLink->setPageLink($privacyPage);
                    $this->policyLink->setLinkText($this->privacy);
                    $out = $this->policyLink->___render();
                }
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
         * @throws \ProcessWire\WireException
         * @throws \ProcessWire\WirePermissionException
         */
        public function ___render(): string
        {
            $privacy = $this->renderPolicyLink() != '' ? $this->renderPolicyLink() : $this->privacy;
            $this->setText(sprintf($this->getText(), $privacy));
            return parent::___render();
        }


    }