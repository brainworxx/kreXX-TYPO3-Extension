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
 * @package Brainworxx\Krexx\Analysis
 */
class Routing extends AbstractRouting
{

    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->processArray = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessArray');
        $this->processBacktrace = $pool->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Process\\ProcessBacktrace');
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
        if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
            return '';
        }
        $data = $model->getData();

        // Check nesting level
        $this->pool->emergencyHandler->upOneNestingLevel();
        if (is_array($data) || is_object($data)) {
            if ($this->pool->emergencyHandler->checkNesting()) {
                $this->pool->emergencyHandler->downOneNestingLevel();
                $text = gettype($data) . ' => ' . $this->pool->messages->getHelp('maximumLevelReached');
                $model->setData($text);
                return $this->processString->process($model);
            }
        }

        // Check for recursion.
        if (is_object($data) || is_array($data)) {
            if ($this->pool->recursionHandler->isInHive($data)) {
                // Render recursion.
                if (is_object($data)) {
                    $type = get_class($data);
                } else {
                    // Must be the globals array.
                    $type = '$GLOBALS';
                }
                $model->setDomid($this->generateDomIdFromObject($data))
                    ->setNormal($type);
                $result = $this->pool->render->renderRecursion($model);
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            }
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);
        }

        // Object?
        if (is_object($data)) {
            // We need to check if this is an object first.
            // When calling is_a('myClass', 'anotherClass') the
            // autoloader is triggered, trying to load 'myClass', although
            // it is just a string.
            if (is_a($data, '\\Closure')) {
                // Closures are handled differently than normal objects
                $result = $this->processClosure->process($model);
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            } else {
                // Normal object.
                $result = $this->processObject->process($model);
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            }
        }

        // Array?
        if (is_array($data)) {
            $result = $this->processArray->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Resource?
        if (is_resource($data)) {
            $result = $this->processResource->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // String?
        if (is_string($data)) {
            $result = $this->processString->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Float?
        if (is_float($data)) {
            $result = $this->processFloat->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Integer?
        if (is_int($data)) {
            $result = $this->processInteger->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Boolean?
        if (is_bool($data)) {
            $result = $this->processBoolean->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Null ?
        if (is_null($data)) {
            $result = $this->processNull->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Still here? This should not happen. Return empty string, just in case.
        $this->pool->emergencyHandler->downOneNestingLevel();
        return '';
    }
}
