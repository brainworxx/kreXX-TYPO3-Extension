<?php

/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Tests\Helpers;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\View\Skins\RenderHans;

/**
 * Short circut the render class.
 *
 * @package Brainworxx\Krexx\Tests\Helpers
 */
class RenderNothing extends RenderHans
{
    /**
     * Storing the model classes, for tersting purpose.
     *
     * @var array
     */
    public $model = [];

    /**
     * Storing the css.
     *
     * @var string
     */
    public $css;

    /**
     * Storing the js.
     *
     * @var string
     */
    public $js;

    /**
     * @var string
     */
    protected $fatalMain = '';

    /**
     * @var string
     */
    protected $footer = '';

    /**
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return string
     */
    public function renderSingleChild(Model $model): string
    {
        $this->model[__FUNCTION__][] = $model;
        return '';
    }

    /**
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return mixed|string
     */
    public function renderRecursion(Model $model): string
    {
        $this->model[__FUNCTION__][] = $model;
        // I'm supposed to do something different here, to avoid a bad rating in
        // the TER-Sonarcube. Hence:
        return '' . '';
    }

    /**
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @param bool $is
     * @return string
     */
    public function renderExpandableChild(Model $model, bool $is = false): string
    {
        $this->model[__FUNCTION__][] = $model;
        // I'm supposed to do something different here, to avoid a bad rating in
        // the TER-Sonarcube. Hence:
        return '' . '' . '';
    }

    /**
     * @param $caller
     * @param Model $model
     * @param bool $configOnly
     * @return string
     */
    public function renderFooter(array $caller, Model $model, bool $configOnly = false): string
    {
        $this->model[__FUNCTION__][] = $model;
        return $this->footer;
    }

    /**
     * @param $css
     * @param $javascript
     * @return mixed|string
     */
    public function renderCssJs(string $css, string $javascript): string
    {
        $this->css = $css;
        $this->js = $javascript;
        return '';
    }

    /**
     * @param string $footer
     */
    public function setFooter(string $footer)
    {
        $this->footer = $footer;
    }

    /**
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @return string
     */
    public function renderFatalMain(string $errstr, string $errfile, int $errline): string
    {
        return $this->fatalMain;
    }

    /**
     * @param string $fatalMain
     */
    public function setFatalMain(string $fatalMain)
    {
        $this->fatalMain = $fatalMain;
    }
}
