<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating a progressbar element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Progressbar.php
 * Created: 20.10.2025
 */


class Progressbar extends Element
{
    /**
     * @param string|null $id
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setTag('progress'); // default is progress
    }

    /**
     * Render the progressbar
     * @return string
     */
    public function ___render(): string
    {
        return $this->renderNonSelfclosingTag($this->getTag(), true, true);
    }


}