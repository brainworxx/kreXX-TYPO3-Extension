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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Misc;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Registry class, to store stuff from the outside and inside.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class Registry
{
    /**
     * Here we store stuff inside.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Add itself to the pool.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $pool->registry = $this;
    }

    /**
     * Storing stuff in the registry.
     *
     * To 'unset' any value, just submit an empty() value.
     *
     * @param mixed $key
     *   The key under what we store the $value,
     * @param mixed $value
     *   The stuff we want to store.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function set($key, $value)
    {
        // We don't really care if there is already a value.
        $this->data[$key] = $value;

        return $this;
    }

    /**
     * Getter for the registry.
     *
     * @param $key
     *   The key under what we once stored the $value,
     *
     * @return null|mixed
     *   The value, if available.
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        return null;
    }

    /**
     * Check if we actually have a value to this key.
     *
     * @param $key
     *   The key we want to check.
     *
     * @return bool
     *   If we have a value, or not.
     */
    public function has($key)
    {
        return isset($this->data[$key]);
    }
}
