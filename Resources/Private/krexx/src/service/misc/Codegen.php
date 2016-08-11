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

namespace Brainworxx\Krexx\Service\Misc;

use Brainworxx\Krexx\Model\Simple;
use Brainworxx\Krexx\Service\Storage;

/**
 * Code generation methods.
 *
 * @package Brainworxx\Krexx\Service\Misc
 */
class Codegen
{

    /**
     * Here we store all relevant data.
     *
     * @var Storage
     */
    protected $storage;

    /**
     * Is the code generation allowed? We only allow it during a normal analysis.
     *
     * @var bool
     */
    protected $allowCodegen = false;

     /**
     * The "scope" we are starting with. When it is $this in combination with a
     * nesting level of 1, we treat protected and private variables and functions
     * as public, because they are reachable from the current scope.
     *
     * @var string
     */
    protected $scope = '';

    /**
     * We are counting the level of the object.
     *
     * @var int
     */
    protected $counter = 0;

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
        }
    }

    /**
     * Generates PHP sourcecode.
     *
     * From the 2 connectors and from the name of name/key of the attribute
     * we can generate PHP code to actually reach the corresponding value.
     * This function generates this code.
     *
     * @param \Brainworxx\Krexx\Model\Simple $model
     *   The model, which hosts all the data we need.
     *
     * @return string
     *   The generated PHP source.
     */
    public function generateSource(Simple $model)
    {
        if (!$this->allowCodegen) {
            return '';
        }

        $result = '';
        // We will not generate anything for function analytic data.
        $connector2 = trim($model->getConnector2(), ' = ');

        $isConstants = $model->getType() === 'class internals' && $model->getName() === 'Constants';
        if ($model->getConnector1() . $connector2 == '' && $this->counter !== 0 && !$isConstants) {
            // No connectors mean, we are dealing with some meta stuff, like functions
            // We will not add anything for them.
        } else {
            // Simply fuse the connectors.
            // The connectors are a representation of the current used "language".
            switch (self::analyseType($model)) {
                case 'contagination':
                    // We simply add the connectors for public access.
                    // Escape the quotes. This is not done by the model.
                    // To prevent double escaping-slashes, we ned to un-slash it
                    // first. Vunterslaush anyone?
                    $name = str_replace('"', '&#034;', addslashes(stripslashes($model->getName())));
                    $name = str_replace("'", '&#039;', $name);

                    $result = $model->getConnector1() . $name . $connector2;
                    break;

                case 'method':
                    // We create a reflection method and then call it.
                    $result = self::reflectFunction();
                    break;

                case 'property':
                    // We create a reflection property an set it to public to access it.
                    $result = self::reflectProperty();
                    break;

                case 'stop':
                    // This tells the JS to stop iterating for previous gencode.
                    $result ='.stop.';
                    break;
            }
        }

        $this->counter++;
        return $result;
    }

    /**
     * Returns a '. . .' to tell our js that this property is not reachable.
     *
     * @return string
     *   Always returns a '. . .'
     */
    protected function reflectProperty()
    {
        // We stop the current codeline here.
        // This value is not reachable, and we will *not* create a reflection here.
        // Some people would abuse this to break open protected and private values.
        // These values are protected for a reason.
        // Adding the '. . .' tells out js that is should not search through the
        // underlying data-source, but simply add a text stating that this value
        // is not reachable.
        $result = ". . .";

        return $result;
    }

    /**
     * Returns a '. . .' to tell our js that this function is not reachable.
     *
     * @return string
     *   Always returns a '. . .'
     */
    protected function reflectFunction()
    {
        // We stop the current codeline here.
        // This value is not reachable, and we will *not* create a reflection here.
        // Some people would abuse this tho break open protected and private values.
        // These values are protected for a reason.
        // Adding the '. . .' tells out js that is should not search through the
        // underlying data-source, but simply add a text stating that this value
        // is not reachable.
        $result = ". . .";

        return $result;
    }

    /**
     * Analyses the type and then decides what to do with it
     *
     * @param Simple $model
     *   The type we are analysing, for example 'private array'.
     *
     * @return string
     *   Possible values:
     *   - contagination
     *   - method
     *   - property
     */
    protected function analyseType(Simple $model)
    {
        $type = $model->getType();

        $contagination = 'contagination';
        $method = 'method';
        $property = 'property';
        $stop = 'stop';

        // Debug methods are always public.
        if ($type == 'debug method' || $this->counter == 0) {
            return $contagination;
        }

        // Test for constants.
        if ($type == 'class internals' && $model->getName() == 'Constants') {
            // We must only take the stuff from the constant itself
            return $stop;
        }

        // Test for protected or private.
        if (strpos($type, 'protected') === false && strpos($type, 'private') === false) {
            // Is not protected.
            return $contagination;
        }

        // Test if we are inside the scope.
        if (self::isInScope($type)) {
            // We are inside the scope, this value, function or class is reachable.
            return $contagination;
        }


        // We are still here? Must be a protected method or property.
        if (strpos($type, 'method') === false) {
            // This is not a method.
            return $property;
        } else {
            return $method;
        }
    }

    /**
     * We decide if a function is currently within a reachable scope.
     *
     * @param string $type
     *   The type we are looking at, either class or array.
     *
     * @return bool
     *   Whether it is within the scope or not.
     */
    public function isInScope($type = '')
    {
        // When analysing a class or array, we have + 1 on our nesting level, when
        // coming from the code generation. That is, because that class is currently
        // being analysed.
        if (strpos($type, 'class') === false && strpos($type, 'array') === false) {
            $nestingLevel = $this->storage->emergencyHandler->getNestingLevel();
        } else {
            $nestingLevel = $this->storage->emergencyHandler->getNestingLevel() - 1;
        }

        return $nestingLevel <= 1 && $this->scope == '$this';
    }

    public function checkAllowCodegen()
    {
        if (!empty($this->scope)) {
            $this->allowCodegen = true;
        }
    }
}
