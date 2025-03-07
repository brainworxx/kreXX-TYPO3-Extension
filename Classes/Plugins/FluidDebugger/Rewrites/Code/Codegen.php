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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\ConstInterface;
use Brainworxx\Krexx\Analyse\Code\Codegen as OrgCodegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;

/**
 * Special code generation for fluid.
 */
class Codegen extends OrgCodegen implements ConstInterface, ProcessConstInterface
{
    /**
     * We wrap this one around the fluid code generation, on the left.
     *
     * @var string
     */
    protected string $wrapperLeft = '{';

    /**
     * We wrap this one around the fluid code generation, on the right.
     *
     * @var string
     */
    protected string $wrapperRight = '}';

    /**
     * Are we analysing the dreaded {_all}?
     *
     * @var bool
     */
    protected bool $isAll = false;

    /**
     * We are only handling the VHS Call ViewHelper generation here.
     * Everything els will be done by the parent class.
     *
     * {@inheritdoc}
     */
    public function generateSource(Model $model): string
    {
        // Get out of here as soon as possible.
        if (!$this->codegenAllowed) {
            return '';
        }

        if (
            $model->getType() === $this->pool->messages->getHelp('debugMethod')
            && $model->getName() === 'getProperties'
        ) {
            // Doing special treatment for the getProperties debug method.
            // This one is directly callable in fluid.
            $model->setName('properties');
        } elseif ($this->isUnknownType($model)) {
            return static::UNKNOWN_VALUE;
        } elseif ($model->getCodeGenType() === static::VHS_CALL_VIEWHELPER) {
            // Check for VHS values.
            return $this->generateVhsCall($model);
        }

        return $this->generateAll($model);
    }

    /**
     * Test if we need to stop the code generation in its tracks.
     *
     * - Test for a point in a variable name.
     *   Stuff like this is not reachable by normal means.
     * - Disallowing code generation for configured debug methods.
     *   There is no real iterator_to_array method in vanilla fluid or vhs.
     *   The groupedFor viewhelper can be abused, but the new variable would
     *   only be visible inside the viewhelper scope. And adding a
     *   {v:variable.set()} inside that scope would make the code generation
     *   really complicated.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     * @return bool
     */
    protected function isUnknownType(Model $model): bool
    {
        $name = $model->getName();
        return
            (is_string($name) &&  strpos($name, '.') !== false && $this->pool->scope->getScope() !== $name)
            || $model->getType() === $this->pool->messages->getHelp('debugMethod')
            || $model->getCodeGenType() === static::CODEGEN_TYPE_ITERATOR_TO_ARRAY
            || $model->getCodeGenType() === static::CODEGEN_TYPE_JSON_DECODE;
    }

    /**
     * Generate the code for the dreaded {_all} variable.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     *
     * @return string
     *   The generated source code.
     */
    protected function generateAll(Model $model): string
    {
        // Check for the {_all} varname, which is not a real varname.
        // We can not use this one for code generation.
        if ($model->getName() === '_all') {
            $this->isAll = true;
            return '';
        }

        // Do the child generation.
        $result = parent::generateSource($model);
        if (!$this->isAll) {
            return $result;
        }

        // We must also remove a leading dot, which may be there, if we are
        // analysing the dreaded {_all} in fluid.
        // Simple types inherit their nesting level from the parent none-simple-type.
        // We need to correct this value, to really get only the ones from
        // the first level and remove the dot there. But do this only if
        // we are analysing the {_all} at this time.
        $nestingLevel = $this->pool->emergencyHandler->getNestingLevel();
        $type = $model->getType();
        if ($type === static::TYPE_ARRAY || $type === static::TYPE_CLASS) {
            --$nestingLevel;
        }
        if ($nestingLevel === 1) {
            $result = trim($result, '.');
        }

        return $result;
    }

    /**
     * We are generation fluid inline code, using the VHS Call ViewHelper
     *
     * @example
     *   object -> v:call(method: 'functionname', arguments: {arg1: 'parameter1', arg2: 'parameter2'})
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model for which we are generation the source code.
     *
     * @return string
     *   The generated fluid source.
     */
    protected function generateVhsCall(Model $model): string
    {
        $data = $model->getParameters();
        $args = '';

        foreach ($data[static::PARAM_ARRAY] as $parameter) {
            $args .= $parameter . ': \'' . $parameter . '\', ';
        }

        $firstPart = ' -> v:call(method: \'';
        if (count($data[static::PARAM_ARRAY]) >= 1) {
            return $firstPart . $model->getName() . '\', arguments: {' . rtrim($args, ', ') . '})';
        }

        return $firstPart . $model->getName() . '\')';
    }

    /**
     * Set an individual wrapper for source generation.
     *
     * @param string $wrapper
     *   The wrapper we want to use.
     *
     * @return $this
     *   Return this, for chaining.
     */
    public function setComplicatedWrapperLeft(string $wrapper): Codegen
    {
        $this->wrapperLeft = $wrapper;
        return $this;
    }

    /**
     * Set an individual wrapper for source generation.
     *
     * @param string $wrapper
     *   The wrapper we want to use.
     *
     * @return $this
     *   Return this, for chaining.
     */
    public function setComplicatedWrapperRight(string $wrapper): Codegen
    {
        $this->wrapperRight = $wrapper;
        return $this;
    }

    /**
     * Warp everything into those fluid brackets, but only on the first level.
     *
     * @return string
     */
    public function generateWrapperLeft(): string
    {
        return $this->wrapperLeft;
    }

    /**
     * Warp everything into those fluid brackets, but only on the first level.
     *
     * @return string
     */
    public function generateWrapperRight(): string
    {
        return $this->wrapperRight;
    }
}
