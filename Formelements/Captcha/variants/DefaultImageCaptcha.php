<?php
declare(strict_types=1);

/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: DefaultImageCaptcha.php
 * Created: 18.08.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class DefaultImageCaptcha extends AbstractImageCaptcha
{
    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = $this->_('Image captcha');
        $this->desc = $this->_('Assign the image to a category');
    }

}
