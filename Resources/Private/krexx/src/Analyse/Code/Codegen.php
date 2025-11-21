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

namespace Brainworxx\Krexx\Analyse\Code;

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionParameter;
use UnitEnum;

/**
 * Code generation methods.
 */
class Codegen implements CallbackConstInterface, CodegenConstInterface, ProcessConstInterface
{
    /**
     * The whitelist to decide if we are displaying type hints.
     *
     * @var string[]
     */
    protected const TYPE_HINT_WHITE_LIST = ['->', '::', '[', ']', '(', ')', '.'];

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * Retrieves the declared method parameters from the declaration.
     *
     * @var \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration
     */
    protected MethodDeclaration $methodDeclaration;

    /**
     * Is the code generation allowed? We only allow it during a normal analysis.
     *
     * @var bool
     */
    protected bool $codegenAllowed = false;

    /**
     * We treat the first run of the code generation different, because then we
     * always generate a value.
     *
     * @var bool
     */
    protected bool $firstRun = true;

    /**
     * Code generation lookup table with a static instruction.
     *
     * @var array
     */
    protected array $codegenLookup = [
        self::CODEGEN_TYPE_META_CONSTANTS => self::CODEGEN_STOP_BIT,
        self::CODEGEN_TYPE_JSON_DECODE => 'json_decode(;firstMarker;)',
        self::CODEGEN_TYPE_BASE64_DECODE => 'base64_decode(;firstMarker;)'
    ];

    /**
     * Here we count haw often the code generation was disabled.
     *
     * We ony enable it, when it is 0.
     *
     * Background:
     * Some code may disable it, while it is still disabled.
     * When that code part re-enables it, it is not aware that it must not
     * re-enable it, because it was disabled beforehand.
     *
     * @var int
     */
    protected int $disableCount = 0;

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
        $this->methodDeclaration = $pool->createClass(MethodDeclaration::class);
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
        if (!$this->codegenAllowed) {
            return static::UNKNOWN_VALUE;
        }

        // Handle the first run.
        if ($this->firstRun) {
            // We handle the first one special, because we need to add the original
            // variable name to the source generation.
            // Also, the string is already prepared for code generation, because
            // it comes directly from the source code itself.
            // And of course, there are no connectors.
            $this->firstRun = false;
            $this->addTypeHint($model);
            return $this->pool->encodingService->encodeString((string) $model->getName());
        }

        $type = $model->getCodeGenType();
        $result = '';
        if ($type === static::CODEGEN_TYPE_PUBLIC) {
            // Public methods, debug methods.
            $result = $this->concatenation($model);
        } elseif ($type !== static::CODEGEN_TYPE_EMPTY) {
            // We go for the more complicated stuff.
            $result = $this->generateComplicatedStuff($model);
        }

        // I'm not really sure if it is possible to create element names that
        // we need to escape.
        return $this->pool->encodingService->encodeString($result);
    }

    /**
     * Adding the typehint to the model, on the first run.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     */
    protected function addTypeHint(Model $model): void
    {
        if (
            empty($name = (string) $model->getName())
            || strpos($name, '$') !== 0
        ) {
            // There is no name, no need for a hint.
            return;
        }

        $type = $model->getType() === static::TYPE_CLASS ? $model->getNormal() : $model->getType();
        foreach (static::TYPE_HINT_WHITE_LIST as $value) {
            if (strpos($name, $value) !== false) {
                // We are analysing something like:
                // $this->getWhatever();
                // We can not type hint this.
                return;
            }
        }

        $model->addToJson(
            $this->pool->messages->getHelp('metaTypeHint'),
            '/** @var ' . $type . ' ' . $name . ' */'
        );
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
    protected function generateComplicatedStuff(Model $model): string
    {
        $type = $model->getCodeGenType();
        if (isset($this->codegenLookup[$type])) {
            return $this->codegenLookup[$type];
        }

        // Define a fallback value.
        $result = static::UNKNOWN_VALUE;

        // And now for the more serious stuff.
        switch ($type) {
            case static::CODEGEN_TYPE_ITERATOR_TO_ARRAY:
                $result = 'iterator_to_array(;firstMarker;)' . $this->concatenation($model);
                break;

            case static::CODEGEN_TYPE_ARRAY_VALUES_ACCESS:
                $result = 'array_values(;firstMarker;)[' . $model->getConnectorParameters() . ']';
                break;

            default:
                if ($this->pool->scope->testModelForCodegen($model)) {
                    // Test if we are inside the scope. Everything within our scope is reachable.
                    $result = $this->concatenation($model);
                }
        }

        return $result;
    }

    /**
     * In case we need to wrap everything until this point into something.
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
     * In case we need to wrap everything until this point into something.
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
     * Set, if we are allowed to generate code to reach the stuff inside
     * the analysis.
     *
     * @param bool $bool
     */
    public function setCodegenAllowed(bool $bool): void
    {
        if (!$bool) {
            $this->codegenAllowed = false;
            ++$this->disableCount;
            return;
        }

        --$this->disableCount;
        if ($this->disableCount < 1) {
            $this->codegenAllowed = true;
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
    public function isCodegenAllowed(): bool
    {
        return $this->codegenAllowed;
    }

    /**
     * Transform a reflection parameter into a human-readable form.
     *
     * @param \ReflectionParameter $reflectionParameter
     *   The reflection parameter we want to wrap.
     *
     * @return string
     *   The parameter data in a human-readable form.
     */
    public function parameterToString(ReflectionParameter $reflectionParameter): string
    {
        // Retrieve the type and the name, without calling a possible autoloader.
        $prefix = '';
        if ($reflectionParameter->isPassedByReference()) {
            $prefix = '&';
        }

        $name = $this->methodDeclaration->retrieveParameterType($reflectionParameter) .
            $prefix . '$' . $reflectionParameter->getName();

        // Retrieve the default value, if available.
        if ($reflectionParameter->isDefaultValueAvailable()) {
            $default = $reflectionParameter->getDefaultValue();
            $name .= ' = ' . $this->translateDefaultValue($default);
        }

        // Escape it, just in case.
        return $this->pool->encodingService->encodeString($name);
    }

    /**
     * Translate the default value into something human-readable.
     *
     * @param mixed $default
     *
     * @return mixed
     *   The type in a human-readable form.
     */
    protected function translateDefaultValue($default)
    {
        if (is_string($default)) {
            $default = '\'' . str_replace('\'', '\\\'', $default) . '\'';
        } elseif (is_array($default)) {
            $default = 'array()';
        } elseif ($default ===  true) {
            $default = 'TRUE';
        } elseif ($default === false) {
            $default = 'FALSE';
        } elseif ($default === null) {
            $default = 'NULL';
        } elseif ($default instanceof UnitEnum) {
            $default = get_class($default) . '::' . $default->name;
        }

        return $default;
    }
}
