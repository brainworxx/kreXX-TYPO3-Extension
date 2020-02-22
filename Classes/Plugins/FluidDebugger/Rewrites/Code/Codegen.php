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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code;

use Brainworxx\Includekrexx\Plugins\FluidDebugger\ConstInterface;
use Brainworxx\Krexx\Analyse\Code\Codegen as OrgCodegen;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Special code generation for fluid.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code
 */
class Codegen extends OrgCodegen implements ConstInterface
{
    /**
     * Constant identifier for the multiline code generation for fluid
     */
    const VHS_CALL_VIEWHELPER = 'vhsCallViewhelper';

    /**
     * The we wrap this one around the fluid code generation, on the left.
     *
     * @var string
     */
    protected $wrapperLeft = '{';

    /**
     * The we wrap this one around the fluid code generation, on the right.
     *
     * @var string
     */
    protected $wrapperRight = '}';

    /**
     * Are we analysing the dreaded {_all}?
     *
     * @var bool
     */
    protected $isAll = false;

    /**
     * We are only handling the VHS Call ViewHelper generation here.
     * Everything els will be done by the parent class.
     *
     * {@inheritdoc}
     */
    public function generateSource(Model $model): string
    {
        // Get out of here as soon as possible.
        if ($this->allowCodegen === false) {
            return '';
        }

        $name = $model->getName();

        if (
            (strpos($name, '.') !== false && $this->pool->scope->getScope() !== $name) ||
            $model->getType() === static::TYPE_DEBUG_METHOD ||
            $model->getMultiLineCodeGen() === static::ITERATOR_TO_ARRAY
        ) {
            // Test for a point in a variable name.
            // Stuff like this is not reachable by normal means.
            // Disallowing code generation for configured debug methods.
            // There is no real iterator_to_array method in vanilla fluid or vhs.
            // The groupedFor viewhelper can be abused, but the new variable would
            // only be visible inside the viewhelper scope. And adding a
            // variable.set inside that scope would make the code generation
            // really complicated.
            // Meh, we simply stop the generation in it's track.
            return static::UNKNOWN_VALUE;
        }

        // Check for VHS values.
        if ($model->getMultiLineCodeGen() === static::VHS_CALL_VIEWHELPER) {
            return $this->generateVhsCall($model);
        }

        // Check for the {_all} varname, which is not a real varname.
        // We can not use this one for code generation.
        if ($name === '_all') {
            $this->isAll = true;
            return '';
        }

        // Do the child generation.
        // We must also remove a leading dot, which may be there, if we are
        // analysing the dreaded {_all} in fluid.
        if ($this->isAll) {
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
                return trim(parent::generateSource($model), '.');
            }
        }

        return parent::generateSource($model);
    }

    /**
     * We are generation fluid inline code, using the VHS Call ViewHelper
     *
     * @example
     *   object -> v:call(method: 'functionname', arguments: {arg1: 'parmeter1', arg2: 'parameter2'})
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
        $counter = 1;
        $args = '';

        foreach ($data[static::PARAM_ARRAY] as $parameter) {
            $args .= 'arg' . $counter . ': \'' . $parameter . '\', ';
            $counter++;
        }

        $firstPart = ' -> v:call(method: \'';
        $secondPart = '\', arguments: {';
        $lastPart = '})';

        if ($counter >= 2) {
            return $firstPart . $model->getName() . $secondPart . rtrim($args, ', ') . $lastPart;
        }

        return $firstPart . $model->getName() . '\')';
    }

    /**
     * Set a individual wrapper for source generation.
     *
     * @param string $wrapper
     *   The wrapper we want to use.
     *
     * @return $this
     *   Return this, for chaining.
     */
    public function setComplicatedWrapperLeft($wrapper): Codegen
    {
        $this->wrapperLeft = $wrapper;
        return $this;
    }

    /**
     * Set a individual wrapper for source generation.
     *
     * @param string $wrapper
     *   The wrapper we want to use.
     *
     * @return $this
     *   Return this, for chaining.
     */
    public function setComplicatedWrapperRight($wrapper): Codegen
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
