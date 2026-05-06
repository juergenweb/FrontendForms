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
 * Optimized via Claude AI 06.05.26
 */

use Exception;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;
use function ProcessWire\wire;

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

        $this->frontendforms['input_privacypageselect'] ??= 'int';

        $this->privacyLink = new Link();
        $this->privacyLink->setLinkText($this->_('Privacy Policy'));
        $this->privacyLink->setAttribute('title', $this->_('To the Privacy Policy page'));

        if (self::setPrivacyPageUrl($this->frontendforms, $this->privacyLink)) {
            $this->setLabel($this->getLabel()->getText() . ' (' . $this->privacyLink->render() . ')');
        }
    }

    /**
     * Static method to generate the link to the privacy page depending on configuration settings
     * @param array $frontendformsConfig
     * @param \FrontendForms\Link $link
     * @return bool
     */
    public static function setPrivacyPageUrl(array $frontendformsConfig, Link $link): bool
    {
        if ($frontendformsConfig['input_privacypageselect'] === 'int' && ($frontendformsConfig['input_privacy'] ?? null)) {
            $link->setPageLink(wire('pages')->get($frontendformsConfig['input_privacy']));
            return true;
        }

        if ($frontendformsConfig['input_privacypageselect'] === 'ext' && ($frontendformsConfig['input_privacypageurl'] ?? null)) {
            $urlKey = 'input_privacypageurl';

            $languages = wire('languages');
            if ($languages) {
                $userLanguage = wire('user')->language;
                if (!$userLanguage->isDefault()) {
                    $langKey = 'input_privacypageurl__' . $userLanguage->id;
                    if (!empty($frontendformsConfig[$langKey])) {
                        $urlKey = $langKey;
                    }
                }
            }

            $link->setUrl($frontendformsConfig[$urlKey]);
            return true;
        }

        return false;
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
        return parent::renderInputCheckbox();
    }

}
