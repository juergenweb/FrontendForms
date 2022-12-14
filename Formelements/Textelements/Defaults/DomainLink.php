<?php
declare(strict_types=1);

/*
 * Create a link which points to the domain itself (link to the homepage)
 * You can decide to output relative or absolute url
 * This class can be used, whenever a link to the homepage should be displayed
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: DomainLink.php
 * Created: 10.11.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class DomainLink extends Link
{

    /**
     * @param $id
     * @param bool $internal - true: relative url, false: absolute url
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct($id = null, bool $internal = true)
    {
        parent::__construct($id);
        $this->setCSSClass('home-link');
        $this->setLinkText($this->wire('input')->httpHostUrl());
        if($internal){
            $this->setPageLink($this->wire('pages')->get('/'));
        } else {
            $this->setUrl($this->wire('input')->httpHostUrl());
        }
    }

}