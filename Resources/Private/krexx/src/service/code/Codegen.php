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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Code;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Code generation methods.
 *
 * @package Brainworxx\Krexx\Service\Code
 */
class Codegen
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
     * We are counting the level of the object.
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * Initializes the code generation.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
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
        if (!$this->allowCodegen) {
            return '';
        }

        $result = '';
        // We will not generate anything for function analytic data.
        $connector2 = trim($model->getConnector2(), ' = ');

        $isConstants = $model->getType() === 'class internals' && $model->getName() === 'Constants';
        if ($model->getConnector1() . $connector2 === '' && $this->counter !== 0 && !$isConstants) {
            // No connectors mean, we are dealing with some meta stuff, like functions
            // We will not add anything for them.
        } else {
            // Simply fuse the connectors.
            // The connectors are a representation of the current used "language".
            switch ($this->analyseType($model)) {
                case 'concatenation':
                    $result = $this->concatenation($model);
                    break;

                case 'method':
                    // We create a reflection method and then call it.
                    $result = $this->reflectFunction();
                    break;

                case 'property':
                    // We create a reflection property an set it to public to access it.
                    $result = $this->reflectProperty();
                    break;

                case 'stop':
                    // This tells the JS to stop iterating for previous gencode.
                    $result =';stop;';
                    break;

                // Multiline code generation starts here.
                case 'iterator_to_array':
                    $result = $this->iteratorToArray() . $this->concatenation($model);
                    break;
            }
        }

        $this->counter++;
        return $result;
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
        // Escape the quotes. This is not done by the model.
        $name = str_replace('"', '&#034;', $model->getName());
        $name = str_replace("'", '&#039;', $name);
        return $model->getConnector1() . $name . trim($model->getConnector2(), ' = ');
    }

    /**
     * Returns a '. . .' to tell our js that this property is not reachable.
     *
     * @return string
     *   Always returns a '. . .'
     */
    protected function reflectProperty()
    {
        // We stop the current code line here.
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
     * Generates the code for the iterator_to_array multiline.
     *
     * @return string
     *   The generated code.
     */
    protected function iteratorToArray()
    {
        $result = 'iterator_to_array(;firstMarker;)';

        return $result;
    }

    /**
     * Analyses the type and then decides what to do with it
     *
     * @param Model $model
     *   The type we are analysing, for example 'private array'.
     *
     * @return string
     *   Possible values:
     *   - concatenation
     *   - method
     *   - property
     */
    protected function analyseType(Model $model)
    {
        $type = $model->getType();
        $multiline = $model->getMultiLineCodeGen();

        $concatenation = 'concatenation';
        $method = 'method';
        $property = 'property';
        $stop = 'stop';

        // Debug methods are always public.
        if ($type === 'debug method' || $this->counter === 0) {
            return $concatenation;
        }

        // Test for constants.
        if ($type === 'class internals' && $model->getName() === 'Constants') {
            // We must only take the stuff from the constant itself
            return $stop;
        }

        // Test for  multiline code generation.
        if (!empty($multiline)) {
            return $multiline;
        }

        // Test for protected or private.
        if (strpos($type, 'protected') === false && strpos($type, 'private') === false) {
            // Is not protected.
            return $concatenation;
        }

        // Test if we are inside the scope.
        if ($this->pool->scope->testModelForCodegen($model)) {
            // We are inside the scope, this value, function or class is reachable.
            return $concatenation;
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
     * Gets set, as soon as we have a scope to come from.
     *
     * @param boolean $bool
     */
    public function setAllowCodegen($bool)
    {
        $this->allowCodegen = $bool;
    }
}
