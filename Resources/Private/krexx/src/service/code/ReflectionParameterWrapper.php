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

namespace Brainworxx\Krexx\Service\Code;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Wrapper arround the \ReflectionParameter, with a standardized __toString
 * method, so that we can get the analysis string from it without any fuzz.
 *
 * @package Brainworxx\Krexx\Service\Code
 */
class ReflectionParameterWrapper
{
    /**
     * @var \ReflectionParameter
     */
    protected $reflectionParameter;

    /**
     * Is this parameter optional?
     *
     * @var bool
     */
    protected $isOptional;

    /**
     * Type of the parameter, like 'array' or
     * \Brainworxx\Krexx\Whatever\Class
     *
     * @var string
     */
    protected $parameterType;

    /**
     * Name of the parameter, like '$myParameter' from the declaration.
     *
     * @var string
     */
    protected $parameterName;

    /**
     * The default value for the parameter
     *
     * @var string
     */
    protected $defaultValue;

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
     * Setter for the rflection parameter
     *
     * @param \ReflectionParameter $reflectionParameter
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setReflectionParameter(\ReflectionParameter $reflectionParameter)
    {
        $this->reflectionParameter = $reflectionParameter;


        $this->parameterName = $reflectionParameter->getName();
        $this->isOptional = $reflectionParameter->isOptional();

        // Check for type value
        $parameterClass = $reflectionParameter->getClass();
        if (is_a($parameterClass, 'ReflectionClass')) {
            $this->parameterType = $reflectionParameter->getClass()->name;
        } else {
            // Check for array
            if ($reflectionParameter->isArray()) {
                $this->parameterType = 'array';
            }
        }

        // Check for default value.
        if ($reflectionParameter->isDefaultValueAvailable()) {
            $default = $reflectionParameter->getDefaultValue();
            if (is_object($default)) {
                $this->defaultValue = get_class($reflectionParameter->getDefaultValue());
            } else {
                $this->defaultValue = $this->pool->encodeString($reflectionParameter->getDefaultValue());
            }
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @return string
     */
    public function getParameterName()
    {
        return $this->parameterName;
    }

    /**
     * @return string
     */
    public function getParameterType()
    {
        return $this->parameterType;
    }

    /**
     * @return \ReflectionParameter
     */
    public function getReflectionParameter()
    {
        return $this->reflectionParameter;
    }

    /**
     * @return bool
     */
    public function getIsOptional()
    {
        return $this->isOptional;
    }

    /**
     * Output everything we have so far in a human readable form.
     *
     * @return string
     *   '\Brainworxx\Krexx\Whatever $varName
     *   or
     *   $varName = 'stuff'
     */
    public function __toString()
    {

        if (!empty($this->toString)) {
            return $this->toString;
        }

        $this->toString = $this->parameterType . ' $' . $this->parameterName;

        if (!empty($this->defaultValue)) {
            $this->toString .= ' = ' . $this->defaultValue;
        }
        $this->toString = trim($this->toString);

        return $this->toString;
    }
}