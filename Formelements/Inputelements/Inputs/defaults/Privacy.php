<?php
    declare(strict_types=1);

    namespace FrontendForms;

    /*
     * Class with pre-defined values for creating an "Accept our data privacy" input field
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: Privacy.php
     * Created: 03.07.2022
     */

    use Exception;
    use ProcessWire\WireException;
    use ProcessWire\WirePermissionException;

    class Privacy extends InputCheckbox
    {

        protected Link $privacyLink;

        /**
         * @param string $id
         * @throws WireException
         * @throws WirePermissionException
         * @throws Exception
         */
        public function __construct(string $id)
        {
            parent::__construct($id);
            $this->setLabel($this->_('I accept the privacy policy'));
            $this->setRule('required')->setCustomMessage($this->_('You have to accept our privacy policy'));

            if (!array_key_exists('input_privacypageselect', $this->frontendforms))
                $this->frontendforms['input_privacypageselect'] = 'int';

            // create the link to the privacy page if set
            $this->privacyLink = new Link();
            $this->privacyLink->setLinkText($this->_('Privacy Policy'));
            $this->privacyLink->setAttribute('title', $this->_('To the Privacy Policy page'));
            $linkExists = false;

            if ($this->frontendforms['input_privacypageselect'] === 'int' && $this->frontendforms['input_privacy']) {
                $linkExists = true;
                $privacyPage = $this->wire('pages')->get($this->frontendforms['input_privacy']); // grab the privacy page
                $this->privacyLink->setPageLink($privacyPage);
            }

            if ($this->frontendforms['input_privacypageselect'] === 'ext' && $this->frontendforms['input_privacypageurl']) {
                $linkExists = true;

                // check for multi-language page
                $languages = $this->wire('languages');
                if ($languages) {

                    $userLanguage = $this->wire('user')->language;

                    if ($userLanguage->isDefault()) {
                        $url = 'input_privacypageurl';
                    } else {
                        $url = 'input_privacypageurl__' . $userLanguage->id;
                    }

                }

                $this->privacyLink->setUrl($this->frontendforms[$url]);
            }

            if ($linkExists) {
                $this->setLabel($this->getLabel()->getText() . ' (' . $this->privacyLink->___render() . ')');
            }
        }

        /**
         * Method to set the url where you can find the privacy policy
         * @param string|null $privacyUrl
         * @return Link
         */
        public function setPrivacyUrl(?string $privacyUrl = null): Link
        {
            return $this->privacyLink->setUrl($privacyUrl);
        }

        /**
         * Get the url of the page where you can find the privacy policy
         * @return string|null
         */
        public function getPrivacyUrl(): ?string
        {
            return $this->privacyLink->getUrl();
        }

        /**
         * Render the privacy checkbox
         * @return string
         */
        public function ___renderPrivacy(): string
        {
            return parent::___renderInputCheckbox();
        }

    }
