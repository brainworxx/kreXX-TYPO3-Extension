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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Special code generation for fluid.
 */
class Tx_Includekrexx_Rewrite_ServiceCodeCodegen extends Codegen
{
    /**
     * Constant identifier for the multiline code generation for fluid
     */
    const VHS_CALL_VIEWHELPER = 2;

    /**
     * The we wrap this one arround the fluid code generation, on the left.
     *
     * @var string
     */
    protected $wrapper1 = '{';

    /**
     * The we wrap this one arround the fluid code generation, on the right.
     *
     * @var string
     */
    protected $wrapper2 = '}';

    /**
     * The recalculated nesting level.
     *
     * @var int
     */
    protected $currentNesting = 0;

    protected $isAll = false;

    /**
     * We are only handling the VHS Call VireHelper generation here.
     * Everything els will be done by the parent class.
     *
     * {@inheritdoc}
     */
    public function generateSource(Model $model)
    {
        // Get out of here as soon as possible.
        if (!$this->allowCodegen) {
            return '';
        }

         // Disallowing code generation for configured debug methods.
        if ($model->getType() === 'debug method') {
            return '. . .';
        }

        // Check for VHS values.
        if ($model->getMultiLineCodeGen() === static::VHS_CALL_VIEWHELPER) {
            return $this->generateVhsCall($model);
        }

        // Check for the {_all} varname, which is not a real varname.
        // We can not use this one for code generation.
        if ($model->getName() === '_all') {
            $this->isAll = true;
            return '';
        }

        // Do the parent generation.
        // We must also remove a leading dot, which may be there, if we are
        // analysing the dreaded {_all} in fluid.
        if ($this->isAll) {
            // Simple types inherit their nesting level from the parent none-simple-type.
            // We need to correct this value, to really get only the ones from
            // the first level and remove the dot there. But do this only if
            // we are analysing the {_all} at this time.
            $nestingLevel = $this->pool->emergencyHandler->getNestingLevel();
            $type = $model->getType();
            if ($type === 'array' || $type === 'class') {
                --$nestingLevel;
            }
            if ($nestingLevel === 1) {
                return trim(parent::generateSource($model), '.');
            }
        }

        return parent::generateSource($model);
    }

    /**
     * We are generation fluid inline code, usinf the CHS Call ViewHelper
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
    protected function generateVhsCall(Model $model)
    {
        $data = $model->getParameters();
        $counter = 1;
        $args = '';

        foreach ($data['paramArray'] as $parameter) {
            $args .= 'arg' . $counter . ': \'' . $parameter . '\', ';
            $counter++;
        }

        $firstPart = ' -> v:call(method: \'';
        $secondPart = '\', arguments: {';
        $lastPart = '})';
        $alternativeLastPart = '\')';

        if ($counter >= 2) {
            return $firstPart . $model->getName() . $secondPart . rtrim($args, ', ') . $lastPart;
        }

        return $firstPart . $model->getName() . $alternativeLastPart;
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
    public function setComplicatedWrapper1($wrapper)
    {
        $this->wrapper1 = $wrapper;
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
    public function setComplicatedWrapper2($wrapper)
    {
        $this->wrapper2 = $wrapper;
        return $this;
    }

    /**
     * Warp everything into those fluid brackets, but only on the first level.
     *
     * @return string
     */
    public function generateWrapper1()
    {
        return $this->wrapper1;
    }

    /**
     * Warp everything into those fluid brackets, but only on the first level.
     *
     * @return string
     */
    public function generateWrapper2()
    {
        return $this->wrapper2;
    }
}
