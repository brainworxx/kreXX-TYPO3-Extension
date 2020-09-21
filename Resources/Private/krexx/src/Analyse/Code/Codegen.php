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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Code;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionException;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionType;

/**
 * Code generation methods.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class Codegen implements CallbackConstInterface, CodegenConstInterface
{

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
    public function generateSource(Model $model): string
    {
        // Do some early return stuff.
        if ($this->allowCodegen === false) {
            return static::UNKNOWN_VALUE;
        }

        // Handle the first run.
        $type = $model->getCodeGenType();
        if ($this->firstRun === true) {
            // We handle the first one special, because we need to add the original
            // variable name to the source generation.
            // Also, the string is already prepared for code generation, because
            // it comes directly from the source code itself.
            // And of cause, there are no connectors.
            $this->firstRun = false;
            return $this->pool->encodingService->encodeString((string)$model->getName());
        }
        if ($type === static::CODEGEN_TYPE_PUBLIC) {
            // Public methods, debug methods.
            return $this->concatenation($model);
        }

        if ($type === static::CODEGEN_TYPE_EMPTY) {
            return '';
        }

        // Still here?
        // We go for the more complicated stuff.
        return $this->generateComplicatedStuff($model);
    }

    /**
     * The more obscure stuff for the code generation.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated PHP source.
     */
    protected function generateComplicatedStuff(Model $model)
    {
        // Define a fallback value.
        $result = static::UNKNOWN_VALUE;

        // And now for the more serious stuff.
        switch ($model->getCodeGenType()) {
            case static::CODEGEN_TYPE_META_CONSTANTS:
                // Test for constants.
                // They have no connectors, but are marked as such.
                // although this is meta stuff, we need to add the stop info here.
                $result = ';stop;';
                break;

            case static::CODEGEN_TYPE_ITERATOR_TO_ARRAY:
                $result = 'iterator_to_array(;firstMarker;)' . $this->concatenation($model);
                break;

            case static::CODEGEN_TYPE_ARRAY_VALUES_ACCESS:
                $result = 'array_values(;firstMarker;)[' . $model->getConnectorParameters() . ']';
                break;

            case static::CODEGEN_TYPE_JSON_DECODE:
                // Meta json decoding.
                $result = 'json_decode(;firstMarker;)';
                break;

            default:
                if ($this->pool->scope->testModelForCodegen($model) === true) {
                    // Test if we are inside the scope. Everything within our scope is reachable.
                    $result = $this->concatenation($model);
                }
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
    public function generateWrapperLeft(): string
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
    public function generateWrapperRight(): string
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
    protected function concatenation(Model $model): string
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
    public function setAllowCodegen(bool $bool)
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
    public function getAllowCodegen(): bool
    {
        return $this->allowCodegen;
    }

    /**
     * Transform a reflection parameter into a human readable form.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter we want to wrap.
     *
     * @return string
     *   The parameter data in a human readable form.
     */
    public function parameterToString(ReflectionParameter $reflectionParameter): string
    {
        // Retrieve the type and the name, without calling a possible autoloader.
        $prefix = '';
        if ($reflectionParameter->isPassedByReference() === true) {
            $prefix = '&' ;
        }

        $name = $this->retrieveParameterType($reflectionParameter) . $prefix . '$' . $reflectionParameter->getName();

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
     * Retrieve the parameter type.
     *
     * Depending on the available PHP version, we need to take different measures.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter, what the variable name says.
     *
     * @return string
     *   The parameter type, if available.
     */
    protected function retrieveParameterType(ReflectionParameter $reflectionParameter): string
    {
        $type = '';
        if ($reflectionParameter->hasType() === true) {
            $reflectionNamedType = $reflectionParameter->getType();
            if (is_a($reflectionNamedType, '\\ReflectionNamedType')) {
                // PHP 7.1 and later
                /** @var ReflectionNamedType $reflectionNamedType */
                $type = $reflectionNamedType->getName() . ' ';
            } else {
                // PHP 7.0 only.
                /** @var ReflectionType $reflectionNamedType */
                $type = $reflectionNamedType->__toString() . ' ';
            }
        }

        return $type;
    }

    /**
     * Translate the default value into something human readable.
     *
     * @param mixed $default
     *
     * @return mixed
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
