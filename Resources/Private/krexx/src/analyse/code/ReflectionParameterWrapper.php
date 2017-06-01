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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Code;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Wrapper around the \ReflectionParameter, with a standardized __toString
 * method, so that we can get the analysis string from it without any fuzz.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class ReflectionParameterWrapper
{

    /**
     * Our pool.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The __toString result.
     *
     * @var string
     */
    protected $toString;

    /**
     * Injects the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Setter for the reflection parameter, it also calculates the
     * toString() return value.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter we want to wrap.
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setReflectionParameter(\ReflectionParameter $reflectionParameter)
    {
        // Fun fact:
        // I tried to add a static cache here, but it was counter productive.
        // Things were not faster, memory usage went up!

        $parameterType = '';

        // Check for type value
        if ($reflectionParameter->isArray()) {
            $parameterType = 'array';
        } elseif (!is_null($reflectionParameter->getClass())) {
            // We got ourselves an object!
            $parameterType = $reflectionParameter->getClass()->name;
        }

        $this->toString .= $parameterType . ' $' . $reflectionParameter->getName();

        // Check for default value.
        if ($reflectionParameter->isDefaultValueAvailable()) {
            $this->toString .= ' = ' . $this->prepareDefaultValue($reflectionParameter->getDefaultValue());
        }

        return $this;
    }

    /**
     * Convert the default value into a human readabla form.
     *
     * @param mixed $default
     * The default value we need to bring into a human readable form.
     *
     * @return string
     *  The human readable form.
     */
    protected function prepareDefaultValue($default)
    {
        if (is_string($default)) {
            // We need to escape this one.
            return '\'' . $this->pool->encodeString($default) . '\'';
        }

        if (is_null($default)) {
            return 'NULL';
        }

        if (is_array($default)) {
            return 'array()';
        }

        if (is_bool($default)) {
            // Transform it to readable values.
            if ($default === true) {
                return 'TRUE';
            } else {
                return 'FALSE';
            }
        }

        // Still here ?!?
        return (string) $default;
    }


    /**
     * Output everything we have so far in a human readable form.
     *
     * @return string
     *   \Brainworxx\Krexx\Whatever $varName
     *   or
     *   $varName = 'stuff'
     */
    public function toString()
    {
        return $this->toString;
    }
}
