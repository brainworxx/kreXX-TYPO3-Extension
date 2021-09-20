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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;

/**
 * Abstract class for further processing of found class properties.
 */
abstract class AbstractRouting
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

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
     * Processes the model according to the type of the variable.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *
     * @deprecated
     *   Will be removed. Use $this->handle;
     *
     * @codeCoverageIgnore
     *   We will not test methods that are deprecated.
     *
     * @return string
     */
    public function process(Model $model): string
    {
        return $this->handle($model);
    }

    /**
     * Generates an id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is the object hash as well as the kruXX call number, to avoid
     * collisions (even if they are unlikely).
     *
     * @param mixed $data
     *   The object from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromObject($data): string
    {
        return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_' . spl_object_hash($data);
    }

    /**
     * Dispatches the process event of the routing.
     *
     * @param Model $model
     *   The model so far.
     *
     * @return Model
     *   The changed Model.
     */
    protected function dispatchProcessEvent(Model $model): Model
    {
        $this->pool->eventService->dispatch(
            static::class . PluginConfigInterface::START_PROCESS,
            null,
            $model
        );

        return $model;
    }

    /**
     * Dispatch a named event.
     *
     * @param string $name
     *   The event name.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     *
     * @return \Brainworxx\Krexx\Analyse\Model
     *   The changed model.
     */
    protected function dispatchNamedEvent(string $name, Model $model): Model
    {
        $this->pool->eventService->dispatch(
            static::class . '::' . $name,
            null,
            $model
        );

        return $model;
    }
}
