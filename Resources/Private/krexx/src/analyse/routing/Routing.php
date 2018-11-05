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

namespace Brainworxx\Krexx\Analyse\Routing;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * "Routing" for kreXX
 *
 * The analysisHub decides what to do next with the model.
 *
 * @package Brainworxx\Krexx\Analyse\Routing
 */
class Routing extends AbstractRouting
{

    /**
     * Inject the pool and create all the routing classes.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->processArray = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessArray');
        $this->processBoolean = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessBoolean');
        $this->processClosure = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessClosure');
        $this->processFloat = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessFloat');
        $this->processInteger = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessInteger');
        $this->processNull = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessNull');
        $this->processObject = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessObject');
        $this->processResource = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessResource');
        $this->processString = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessString');
        $this->processOther = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessOther');

        $pool->routing = $this;
    }

    /**
     * Dump information about a variable.
     *
     * This function decides what functions analyse the data
     * and acts as a hub.
     *
     * @param Model $model
     *   The variable we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    public function analysisHub(Model $model)
    {
        // Check memory and runtime.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        $data = $model->getData();

        // String?
        if (is_string($data) === true) {
            return $this->processString->process($model);
        }

        // Integer?
        if (is_int($data) === true) {
            return $this->processInteger->process($model);
        }

        // Null?
        if ($data === null) {
            return $this->processNull->process($model);
        }

        // Handle the complex types.
        if (is_array($data) === true || is_object($data) === true) {
            // Up one nesting Level.
            $this->pool->emergencyHandler->upOneNestingLevel();
            // Handle the non simple types like array and object.
            $result = $this->handleNoneSimpleTypes($data, $model);
            // We are done here, down one nesting level.
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Boolean?
        if (is_bool($data) === true) {
            return $this->processBoolean->process($model);
        }

        // Float?
        if (is_float($data) === true) {
            return $this->processFloat->process($model);
        }

        // Resource?
        // The is_resource can not identify closed stream resource types.
        // And the get_resource_type() throws a warning, in case this is not a
        // resource.
        set_error_handler(function () {
            // Do nothing. We need to catch a possible warning.
        });
        if (get_resource_type($data) !== null) {
            restore_error_handler();
            return $this->processResource->process($model);
        }
        restore_error_handler();

        // Still here? Tell the dev that we can not analyse this one.
        return $this->processOther->process($model);
    }

    /**
     * Routing of objects and arrays.
     *
     * @param object|array $data
     *   The object / array we are analysing.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML code.
     */
    protected function handleNoneSimpleTypes($data, Model $model)
    {
        // Check the nesting level.
        if ($this->pool->emergencyHandler->checkNesting() === true) {
            $text = $this->pool->messages->getHelp('maximumLevelReached2');
            if (is_array($data) === true) {
                $type = static::TYPE_ARRAY;
            } else {
                $type = static::TYPE_OBJECT;
            }
            $model->setData($text)
                ->setNormal($this->pool->messages->getHelp('maximumLevelReached1'))
                ->setType($type)
                ->setHasExtra(true);
            // Render it directly.
            return $this->pool->render->renderSingleChild($model);
        }

        if ($this->pool->recursionHandler->isInHive($data) === true) {
            // Render recursion.
            if (is_object($data) === true) {
                $normal = '\\' . get_class($data);
                $domId = $this->generateDomIdFromObject($data);
            } else {
                // Must be the globals array.
                $normal = '$GLOBALS';
                $domId = '';
            }

            return $this->pool->render->renderRecursion(
                $model->setDomid($domId)->setNormal($normal)
            );
        }

        // Looks like we are good.
        return $this->preprocessNoneSimpleTypes($data, $model);
    }

    /**
     * Do some pre processing, before the routing.
     *
     * @param object|array $data
     *   The object / array we are analysing.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML code.
     */
    protected function preprocessNoneSimpleTypes($data, Model $model)
    {
        if (is_object($data) === true) {
            // Object?
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);

            // We need to check if this is an object first.
            // When calling is_a('myClass', 'anotherClass') the autoloader is
            // triggered, trying to load 'myClass', although it is just a string.
            if ($data instanceof \Closure) {
                // Closures are handled differently than normal objects
                return $this->processClosure->process($model);
            }

            // Normal object.
            return $this->processObject->process($model);
        }

        // Must be an array.
        return $this->processArray->process($model);
    }
}
