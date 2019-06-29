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

namespace Brainworxx\Krexx\Analyse\Code;

use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionException;
use ReflectionParameter;

/**
 * Code generation methods.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class Codegen implements ConstInterface
{
    /**
     * Constant identifier for the array multiline code generation.
     */
    const ITERATOR_TO_ARRAY = 1;


    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Is the code generation allowed? We only allow it during a normal analysis.
     *
     * @var bool
     */
    protected $allowCodegen = false;

    /**
     * We treat the first run of the code generation different, because then we
     * always generate a value.
     *
     * @var bool
     */
    protected $firstRun = true;

    /**
     * Initializes the code generation.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        $pool->codegenHandler = $this;
    }

    /**
     * Generates PHP sourcecode.
     *
     * From the 2 connectors and from the name of name/key of the attribute
     * we can generate PHP code to actually reach the corresponding value.
     * This function generates this code.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated PHP source.
     */
    public function generateSource(Model $model)
    {
        if ($this->allowCodegen === true) {
            // We handle the first one special, because we need to add the original
            // variable name to the source generation.
            if ($this->firstRun === true) {
                $this->firstRun = false;
                return $this->concatenation($model);
            }

            // Test for constants.
            // They have no connectors, but are marked as such.
            // although this is meta stuff, we need to add the stop info here.
            if ($model->getIsMetaConstants() === true) {
                // We must only take the stuff from the constant itself
                return ';stop;';
            }

            $connectors = $model->getConnectorLeft() . $model->getConnectorRight();
            if (empty($connectors) === true) {
                // No connectors, no nothing. We must be dealing with meta stuff.
                // We will ignore this one.
                return '';
            }

            // Debug methods are always public.
            if ($model->getType() === static::TYPE_DEBUG_METHOD) {
                return $this->concatenation($model);
            }

            // Multi line code generation starts here.
            if ($model->getMultiLineCodeGen() === static::ITERATOR_TO_ARRAY) {
                return 'iterator_to_array(;firstMarker;)' . $this->concatenation($model);
            }

            // Test for private or protected access.
            if ($model->getIsPublic() === true) {
                return $this->concatenation($model);
            }

            // Test if we are inside the scope. Everything within our scope is reachable.
            if ($this->pool->scope->testModelForCodegen($model) === true) {
                // We are inside the scope, this value, function or class is reachable.
                return $this->concatenation($model);
            }

            // We are still here? Must be a protected method or property.
            // The '. . .' will tell the code generation to stop in it's tracks
            // and do nothing.
        }

        // No code generation in this path.
        // We must prevent code generation when copying stuff here by recursion
        // resolving by adding these dots.
        return static::UNKNOWN_VALUE;
    }

    /**
     * In case we need to wrap the everything until this point into something.
     * Right now, this is not used for PHP, only for Fluid.
     *
     * @return string
     *   Return an empty string.
     */
    public function generateWrapperLeft()
    {
        return '';
    }

    /**
     * In case we need to wrap the everything until this point into something.
     * Right now, this is not used for PHP, only for Fluid.
     *
     * @return string
     *   Return an empty string.
     */
    public function generateWrapperRight()
    {
        return '';
    }

    /**
     * Simple concatenation of all parameters.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *
     * @return string
     *   The generated code.
     */
    protected function concatenation(Model $model)
    {
        // We simply add the connectors for public access.
        return $model->getConnectorLeft() .
            $this->pool->encodingService->encodeStringForCodeGeneration($model->getName()) .
            $model->getConnectorRight();
    }

    /**
     * Gets set, as soon as we have a scope to come from.
     *
     * @param bool $bool
     */
    public function setAllowCodegen($bool)
    {
        $this->allowCodegen = $bool;
    }

    /**
     * Getter for the allowance of the code generation.
     *
     * @return bool
     */
    public function getAllowCodegen()
    {
        return $this->allowCodegen;
    }

    /**
     * Abusing the __toString() magic to get informations about a parameter.
     *
     * If a parameter must have a specific class, that is not present in the
     * system, we will get a reflection error. That is why we abuse the
     * __string() method.
     * The method getType() is available for PHP 7 only.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter we want to wrap.
     *
     * @return string
     *   The parameter data in a human readable form.
     */
    public function parameterToString(ReflectionParameter $reflectionParameter)
    {
        // Slice off the first part.
        $paremExplode = array_slice(explode(' ', trim($reflectionParameter->__toString(), ' ]')), 4);
        // A long standard value gets cut off. We do not want that.
        if ($reflectionParameter->isDefaultValueAvailable()) {
            // Remove the standard value
            $paremExplode = array_slice($paremExplode, 0, 2);
            try {
                $default = $reflectionParameter->getDefaultValue();
            } catch (ReflectionException $e) {
                $default = null;
            }

            // If we are dealing with a reflection parameter from a closure,
            // there is a missing '=' in the return string.
            if (end($paremExplode) !== '=') {
                $paremExplode[] = '=';
            }

            if (is_string($default)) {
                $default = '\'' . $default . '\'';
            } elseif (is_array($default)) {
                $default = 'array()';
            } elseif ($default ===  true) {
                $default = 'TRUE';
            } elseif ($default === false) {
                $default = 'FALSE';
            } elseif ($default === null) {
                $default = 'NULL';
            }

            $paremExplode[] = $default;
        }

        // Escape it, just in case.
        return $this->pool->encodingService->encodeString(implode(' ', $paremExplode));
    }
}
