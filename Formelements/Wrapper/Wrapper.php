<?php
declare(strict_types=1);

namespace FrontendForms;

/*
 * Base class for creating a wrapper
 * fe <div>...</div>
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Wrapper.php
 * Created: 03.07.2022
 * Optimized via Claude AI 06.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Wrapper extends Tag
{

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();
        $this->setTag('div');
    }

    /**
     * Render the wrapper
     * @return string
     */
    public function ___render(): string
    {
        return $this->renderNonSelfclosingTag($this->getTag());
    }

    public function __toString(): string
    {
        return $this->render();
    }

}
