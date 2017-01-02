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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Service\Storage;

/**
 * Scope analysis decides if a property of method is accessible in the current
 * analysis scope.
 *
 * @package Brainworxx\Krexx\Analyse
 */
class Scope
{
    /**
     * Here we store all relevant data.
     *
     * @var Storage
     */
    protected $storage;

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
     * @param Storage $storage
     *   The storage, where we store the classes we need.
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Sets the scope in which we are moving ('$this' or something else).
     *
     * @param string $scope
     *   The scope ('$this' or something else) .
     */
    public function setScope($scope)
    {
        if ($scope != '. . .') {
            $this->scope = $scope;
            // Now that we have a scope, we can actually generate code to
            // reach the variables inside the analysis.
            $this->storage->codegenHandler->setAllowCodegen(true);
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
        return  $this->storage->emergencyHandler->getNestingLevel() <= 1 &&
            $this->scope === '$this' &&
            $this->storage->config->getSetting('useScopeAnalysis');
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
    public function testModelForCodegen($model)
    {
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
        if (strpos($model->getType(), 'class') === false && strpos($model->getType(), 'array') === false) {
            $nestingLevel = $this->storage->emergencyHandler->getNestingLevel();
        } else {
            $nestingLevel = $this->storage->emergencyHandler->getNestingLevel() - 1;
        }



        return $nestingLevel <= 1 && $this->scope === '$this';
    }
}
