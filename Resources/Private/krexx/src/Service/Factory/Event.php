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

namespace Brainworxx\Krexx\Service\Factory;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Calling all registered event handlers on the event.
 */
class Event
{
    /**
     * Here we save the registered event handler.
     *
     * @var string[]
     */
    public $register = [];

    /**
     * The pool.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool. Retrieve the global event handlers from the overwrites.
     *
     * @param Pool $pool
     *   The pool, what else?
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
        $this->register = SettingsGetter::getEventList();

        $pool->eventService = $this;
    }

    /**
     * Dispatches an event.
     *
     * @param string $name
     *   The name of the event.
     * @param AbstractCallback|null $callback
     *   The callback, that emitted the event.
     *   Null, when coming from the routing.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model so far, if available.
     *
     * @return string
     *   The generated markup from the event handlers
     */
    public function dispatch(string $name, AbstractCallback $callback = null, Model $model = null): string
    {
        if (isset($this->register[$name]) === false) {
            // No registered handler. Early return.
            return '';
        }

        $output = '';

        // Got to handle them all.
        foreach ($this->register[$name] as $classname) {
            $output .= $this->pool->createClass($classname)->handle($callback, $model);
        }

        return $output;
    }
}
