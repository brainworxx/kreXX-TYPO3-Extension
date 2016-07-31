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

namespace Brainworxx\Krexx\Analysis;

use Brainworxx\Krexx\Controller\OutputActions;
use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\Model\Simple;

/**
 * Code generation methods.
 *
 * @package Brainworxx\Krexx\View
 */
class Codegen
{

     /**
     * The "scope" we are starting with. When it is $this in combination with a
     * nesting level of 1, we treat protected and private variables and functions
     * as public, because they are reachable from the current scope.
     *
     * @var string
     */
    public static $scope = '';

    /**
     * We are counting the level of the object.
     *
     * @var int
     */
    protected static $counter = 0;

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
    public static function generateSource(Simple $model)
    {
        if (!Config::$allowCodegen) {
            return '';
        }

        $result = '';
        // We will not generate anything for function analytic data.
        $connector2 = trim($model->getConnector2(), ' = ');

        // We handle the first one special.
        if ($model->getConnector1() . $connector2 == '' && self::$counter !== 0) {
            // No connectors mean, we are dealing with some meta stuff, like functions
            // We will not add anything for them.
        } else {
            // Simply fuse the connectors.
            // The connectors are a representation of the current used "language".
            switch (self::analyseType($model->getType())) {
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
            }
        }

        self::$counter++;
        return $result;
    }

    /**
     * Returns a '. . .' to tell our js that this property is not reachable.
     *
     * @return string
     *   Always retuns a '. . .'
     */
    protected static function reflectProperty()
    {
        // We stop the current codeline here.
        // This value is not reachable, and we will *not* create a refletion here.
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
     *   Always retuns a '. . .'
     */
    protected static function reflectFunction()
    {
        // We stop the current codeline here.
        // This value is not reachable, and we will *not* create a refletion here.
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
     * @param string $type
     *   The type we are analysing, for example 'private array'.
     *
     * @return string
     *   Possible values:
     *   - contagination
     *   - method
     *   - property
     */
    protected static function analyseType($type)
    {
        $contagination = 'contagination';
        $method = 'method';
        $property = 'property';

        // Debug methods are always public.
        if ($type == 'debug method' || self::$counter == 0) {
            return $contagination;
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
     * Here we reset the counter
     *
     * We are handling the first time a little bit different. If not we would
     * get something like '$myClass->value->anotherValue'
     *
     * When we start the run, we need to generate something like:
     * $result = $myClass->value
     */
    public static function resetCounter()
    {
        self::$counter = 0;
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
    public static function isInScope($type = '')
    {
        // When analysing a class or array, we have + 1 on our nesting level, when
        // coming from the code generation. That is, because that class is currently
        // being analysed.
        if (strpos($type, 'class') === false && strpos($type, 'array') === false) {
            $nestingLevel = OutputActions::$nestingLevel;
        } else {
            $nestingLevel = OutputActions::$nestingLevel - 1;
        }

        return $nestingLevel <= 1 && self::$scope == '$this';
    }
}
