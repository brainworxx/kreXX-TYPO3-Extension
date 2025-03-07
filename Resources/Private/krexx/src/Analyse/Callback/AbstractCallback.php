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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;

/**
 * Abstract class for the callback classes inside the model.
 */
abstract class AbstractCallback
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * The parameters for the callback.
     *
     * @var array
     */
    protected array $parameters = [];

    /**
     * The actual callback function for the renderer.
     *
     * @return string
     *   The generated markup.
     */
    abstract public function callMe(): string;

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Add callback parameters at class construction.
     *
     * @param array $parameters
     *   The parameters for the callMe() method.
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function setParameters(array $parameters): AbstractCallback
    {
        $this->parameters = $parameters;
        return $this;
    }

    /**
     * Access the internal parameters from the outside.
     * Used mostly (only?) in the event system.
     *
     * @return array
     *   The internal parameters for the callback.
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Dispatches the start event of the callMe callback.
     *
     * @return string
     *   The generated markup from the event handler.
     */
    protected function dispatchStartEvent(): string
    {
        return $this->pool->eventService->dispatch(
            static::class . PluginConfigInterface::START_EVENT,
            $this
        );
    }

    /**
     * Dispatches an event where the model is available.
     *
     * @param string $name
     *   The name of the event.
     * @param Model $model
     *   The model so far.
     *
     * @return Model
     *   Return the model for chaining.
     */
    protected function dispatchEventWithModel(string $name, Model $model): Model
    {
        $this->pool->eventService->dispatch(
            static::class . '::' . $name,
            $this,
            $model
        );

        return $model;
    }
}
