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

namespace Brainworxx\Krexx\Analyse\Code;

use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Scope analysis decides if a property of method is accessible in the current
 * analysis scope.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class Scope
{
    /**
     * We use this scope, when kreXX was called like kreXX($this);
     *
     * This determines, that all private and protected variables will be
     * analysed when using the scope analysis.
     */
    const THIS_SCOPE = '$this';

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The "scope" we are starting with. When it is $this in combination with a
     * nesting level of 1, we treat protected and private variables and functions
     * as public, because they are reachable from the current scope.
     *
     * @var string
     */
    protected $scope = '';

    /**
     * Initializes the code generation.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        $pool->scope = $this;
    }

    /**
     * Sets the scope in which we are moving ('$this' or something else).
     *
     * @param string $scope
     *   The scope ('$this' or something else) .
     */
    public function setScope($scope)
    {
        if ($scope !== '. . .') {
            $this->scope = $scope;
            // Now that we have a scope, we can actually generate code to
            // reach the variables inside the analysis.
            $this->pool->codegenHandler->setAllowCodegen(true);
        }
    }

    /**
     * Getter for the scope string.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * We decide if a method or property is currently within a reachable scope.
     *
     * @return bool
     *   Whether it is within the scope or not.
     */
    public function isInScope()
    {
        return  $this->pool->emergencyHandler->getNestingLevel() <= 1 &&
            $this->scope === static::THIS_SCOPE &&
            $this->pool->config->getSetting(Fallback::SETTING_USE_SCOPE_ANALYSIS);
    }

    /**
     * Decide if we allow code generation for this property.
     *
     * @param Model $model
     *   The type we want to generate the code for.
     *
     * @return bool
     *   Can we allow code generation here?
     */
    public function testModelForCodegen(Model $model)
    {
        $nestingLevel = $this->pool->emergencyHandler->getNestingLevel();

        // If we are too deep at this moment, we will stop right here!
        // Also, anything not coming from $this is not reachable, since
        // we are testing protected stuff here.
        if ($nestingLevel > 2 || $this->scope !== static::THIS_SCOPE) {
            return false;
        }

        // Inherited private properties or methods are not accessible from the
        // $this scope. We need to make sure that we do not generate any code
        // for them.
        if (strpos($model->getType(), 'private inherited') !== false) {
            // No source generation for you!
            return false;
        }

        // When analysing a class or array, we have + 1 on our nesting level, when
        // coming from the code generation. That is, because that class is currently
        // being analysed.
        if (is_object($model->getData()) === true || is_array($model->getData()) === true) {
            --$nestingLevel;
        }

        return $nestingLevel === 1 && $this->scope === static::THIS_SCOPE;
    }
}
