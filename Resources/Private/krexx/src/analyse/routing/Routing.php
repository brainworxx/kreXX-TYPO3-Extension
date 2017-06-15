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

namespace Brainworxx\Krexx\Analyse\Routing;

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
        if ($this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }
        $data = $model->getData();

        // String?
        if (is_string($data)) {
            return $this->processString->process($model);
        }

        // Integer?
        if (is_int($data)) {
            return $this->processInteger->process($model);
        }

        // Null ?
        if (is_null($data)) {
            return $this->processNull->process($model);
        }

        if (is_array($data) || is_object($data)) {
            // Check nesting level
            $this->pool->emergencyHandler->upOneNestingLevel();

            if ($this->pool->emergencyHandler->checkNesting()) {
                $this->pool->emergencyHandler->downOneNestingLevel();
                $text = $this->pool->messages->getHelp('maximumLevelReached2');
                $model->setData($text)
                    ->setNormal($this->pool->messages->getHelp('maximumLevelReached1'))
                    ->setType(gettype($data))
                    ->hasExtras();
                // Render it directly.
                return $this->pool->render->renderSingleChild($model);
            }
            // Handle the non simple types like array and object.
            $result = $this->handleNoneSimpleTypes($data, $model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Boolean?
        if (is_bool($data)) {
            return $this->processBoolean->process($model);
        }

        // Float?
        if (is_float($data)) {
            return $this->processFloat->process($model);
        }

        // Resource?
        if (is_resource($data)) {
            return $this->processResource->process($model);
        }

        // Still here? This should not happen. Return empty string, just in case.
        return '';
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
        if ($this->pool->recursionHandler->isInHive($data)) {
            // Render recursion.
            if (is_object($data)) {
                $type = get_class($data);
            } else {
                // Must be the globals array.
                $type = '$GLOBALS';
            }


            return $this->pool->render->renderRecursion($model
                ->setDomid($this->generateDomIdFromObject($data))
                ->setNormal($type));
        }

        if (is_object($data)) {
            // Object?
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);

            // We need to check if this is an object first.
            // When calling is_a('myClass', 'anotherClass') the
            // autoloader is triggered, trying to load 'myClass', although
            // it is just a string.
            if (is_a($data, '\\Closure')) {
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
