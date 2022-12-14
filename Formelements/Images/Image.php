<?php
declare(strict_types=1);

/*
 * Class for creating an image tag
 * Used for Captcha
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: Image.php
 * Created: 31.07.2022 
 */


namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Image extends Element
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTag('img');
    }

    /**
     * Render the image tag
     * @return string
     */
    public function ___render(): string
    {
        if ($this->wrapper) {
            $this->wrapper->setContent($this->renderSelfclosingTag($this->getTag()));
            return $this->wrapper->___render();
        }
        return $this->renderSelfclosingTag($this->getTag());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->___render();
    }

}