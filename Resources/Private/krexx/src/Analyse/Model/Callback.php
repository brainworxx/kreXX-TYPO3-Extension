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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Model;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Analysis model trait with the callback that renders the output.
 */
trait Callback
{
    /**
     * Callback for the renderMe() method.
     *
     * @var \Brainworxx\Krexx\Analyse\Callback\AbstractCallback
     */
    protected $callback;

    /**
     * Parameters for the renderMe method.
     *
     * Should be used in the extending classes.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Inject the callback for the renderer
     *
     * @param AbstractCallback $object
     *   The callback.
     *
     * @return Model
     *   $this for chaining
     */
    public function injectCallback(AbstractCallback $object): Model
    {
        $this->callback = $object;
        return $this;
    }

    /**
     * Simply add a parameter for the $closure.
     *
     * @param string $name
     *   The name of the parameter.
     * @param mixed $value
     *   The value of the parameter, by reference.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function addParameter(string $name, $value): Model
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Triggers the callback (if set).
     *
     * @return string
     */
    public function renderMe(): string
    {
        if ($this->callback === null) {
            return '';
        }

        return $this->callback
            ->setParameters($this->parameters)
            ->callMe();
    }

    /**
     * Getter for all parameters for the internal callback.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Is a callback set, or doe we have extra data?
     *
     * @return bool
     */
    public function isExpandable(): bool
    {
        return $this->callback !== null || $this->hasExtra;
    }
}
