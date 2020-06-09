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
    const ITERATOR_TO_ARRAY = 'iteratorToArray';

    /**
     * Identifier for inaccessible array multiline code generation.
     */
    const ARRAY_VALUES_ACCESS = 'arrayValuesAccess';

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
     * Here we count haw often the code generation was disabled.
     *
     * We ony enable it, when it is 0.
     *
     * Background:
     * Some code may disable it, while it is still disabled.
     * When that code part re-enables it, it is not aware that it must not
     * re-enable it, because it was disabled before hand.
     *
     * @var int
     */
    protected $disableCount = 0;

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
        $result = static::UNKNOWN_VALUE;

        if ($this->allowCodegen === false) {
            // Nothing to do here, early return
            return $result;
        } elseif ($this->firstRun === true) {
            // We handle the first one special, because we need to add the original
            // variable name to the source generation.
            // Also, the string is already prepared for code generation, because
            // it comes directly from the source code itself.
            // And of cause, there are no connectors.
            $this->firstRun = false;
            $result = $this->pool->encodingService->encodeString($model->getName());
        } elseif ($model->getIsMetaConstants() === true) {
            // Test for constants.
            // They have no connectors, but are marked as such.
            // although this is meta stuff, we need to add the stop info here.
            $result = ';stop;';
        } elseif (empty($model->getConnectorLeft() . $model->getConnectorRight()) === true) {
            // No connectors, no nothing. We must be dealing with meta stuff.
            // We will ignore this one.
            $result = '';
        } elseif ($model->getType() === static::TYPE_DEBUG_METHOD) {
            // Debug methods are always public.
            $result = $this->concatenation($model);
        } elseif ($model->getMultiLineCodeGen() === static::ITERATOR_TO_ARRAY) {
            // Multi line code generation starts here.
            $result = 'iterator_to_array(;firstMarker;)' . $this->concatenation($model);
        } elseif ($model->getMultiLineCodeGen() === static::ARRAY_VALUES_ACCESS) {
            $result = 'array_values(;firstMarker;)[' . $model->getConnectorParameters() . ']';
        } elseif ($model->getIsPublic() === true) {
            // Test for private or protected access.
            $result = $this->concatenation($model);
        } elseif ($this->pool->scope->testModelForCodegen($model) === true) {
            // Test if we are inside the scope. Everything within our scope is reachable.
            $result = $this->concatenation($model);
        }

        return $result;
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
        if ($bool === false) {
            $this->allowCodegen = false;
            ++$this->disableCount;
            return;
        } else {
            --$this->disableCount;
            if ($this->disableCount < 1) {
                $this->allowCodegen = true;
            }
        }

        if ($this->disableCount < 0) {
            $this->disableCount = 0;
        }
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
        $type = explode(' ', $reflectionParameter->__toString())[4];
        $name = '';

        // Retrieve the type and the name, without calling a possible autoloader.
        if ($reflectionParameter->isPassedByReference() === true) {
            $prefix = '&$';
        } else {
            $prefix = '$';
        }
        if (strpos($type, $prefix) !== 0) {
            $name = $type . ' ';
        }
        $name .= $prefix . $reflectionParameter->getName();

        // Retrieve the default value, if available.
        if ($reflectionParameter->isDefaultValueAvailable() === true) {
            try {
                $default = $reflectionParameter->getDefaultValue();
            } catch (ReflectionException $e) {
                $default = null;
            }

            $name .= ' = ' . $this->translateDefaultValue($default);
        }

        // Escape it, just in case.
        return $this->pool->encodingService->encodeString($name);
    }

    /**
     * Translate the default value into something human readable.
     *
     * @param mixed $default
     *
     * @return string
     *   The type in a human readable form.
     */
    protected function translateDefaultValue($default)
    {
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

        return $default;
    }
}
