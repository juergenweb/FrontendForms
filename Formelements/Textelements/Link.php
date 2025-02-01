<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating links
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Link.php
 * Created: 03.07.2022
 */

use ProcessWire\Page;
use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

/**
 * A class for creating links
 */
class Link extends TextElements
{

    protected string $url = '';
    protected string $anchor = '';
    protected array $queryString = []; // array that can contain multiple querystrings
    protected string $linkText = '';

    /**
     * @param $id
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
        $this->setTag('a');
    }

    /**
     * Get the link text
     * @return string
     */
    public function getLinkText(): string
    {
        return $this->getText();
    }

    /**
     * Set the link text
     * @param string $linktext
     * @return $this
     */
    public function setLinkText(string $linktext): self
    {
        $this->setText($linktext);
        return $this;
    }

    /**
     * Method to set all parameters of an internal link to a page including SeoMaestro robots index
     * If the linked page has "noindex" attribute, then "nofollow will be added as rel attribute
     * @param Page $page
     * @return $this
     */
    public function setPageLink(Page $page): self
    {
        $this->setUrl($page->url);
        $page->of(true); // turn output formatting on the get a title string and not a LanguagesPageFieldValue
        $this->setLinkText($page->title);
        $this->setAttribute('title', $this->_('To the page') . ': ' . $page->title);
        // check if SEO Maestro is installed and check if page should be indexed
        $seo = Form::getSeoMaestro();
        if ($seo) {
            $fieldName = $seo->name;
            $seo = $page->$fieldName;
            // if page has no index -> nofollow attribute will be added to the link
            if (isset($seo->robots_noIndex)) {
                $this->setAttribute('rel', 'nofollow');
            }
        }
        return $this;
    }

    /**
     * Render the link including anchor if present
     * @return string
     */
    public function ___render(): string
    {
        if ($this->getQueryString()) {
            $this->setUrl($this->getUrl() . '?' . $this->getQueryString());
        }
        if ($this->getAnchor()) {
            $this->setUrl($this->getUrl() . '#' . $this->getAnchor());
        }

        return parent::___render();
    }

    /**
     * Set the parameter (anchor or querystring) of a link
     * @param string $get
     * @param string $parameterValue
     * @return $this
     */
    protected function setGetParameter(string $get, string $parameterValue): string
    {
        $parameterValue = trim($parameterValue);
        // remove prefix if present
        if (str_starts_with($parameterValue, $get)) {
            $parameterValue = ltrim($parameterValue, $get);
        }
        return $parameterValue;
    }

    /**
     * Get the anchor of a link
     * @return string
     */
    public function getAnchor(): string
    {
        return $this->anchor;
    }

    /**
     * Add an anchor to a link
     * @param string $anchor
     * @return $this
     */
    public function setAnchor(string $anchor): self
    {
        $this->anchor = $this->setGetParameter('#', $anchor);
        return $this;
    }

    /**
     * Add a querystring to a link
     * You can use this method several times to add multiple query strings
     * @param string $queryString
     * @return $this
     */
    public function setQueryString(string $queryString): self
    {
        $qs = $this->queryString;
        $qs[] = $queryString;
        $this->queryString = $qs;
        return $this;
    }

    /**
     * Get the querystring of a link
     * @return string
     */
    public function getQueryString(): string
    {
        return implode('&',$this->queryString);
    }

    /**
     * Get the url of the link
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->getAttribute('href');
    }

    /**
     * Set the url for the link target
     * @param string|null $url
     * @return $this
     */
    public function setUrl(?string $url = null): self
    {
        if ($url) {
            $this->setAttribute('href', $url);
        }
        return $this;
    }

}
