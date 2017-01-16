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

/**
 * "Routing" for kreXX
 *
 * The analysisHub decides what to do next with the model.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class Routing extends AbstractRouting
{

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
                return $this->pool
                    ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessString')
                    ->process($model);
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
                    ->setType($type);
                $result = $this->pool->render->renderRecursion($model);
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            }
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);
        }


        // Object?
        // Closures are analysed separately.
        if (is_object($data) && !is_a($data, '\\Closure')) {
            $result = $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessObject')
                ->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Closure?
        if (is_a($data, '\\Closure')) {
            $result = $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessClosure')
                ->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Array?
        if (is_array($data)) {
            $result = $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessArray')
                ->process($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Resource?
        if (is_resource($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessResource')
                ->process($model);
        }

        // String?
        if (is_string($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessString')
                ->process($model);
        }

        // Float?
        if (is_float($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessFloat')
                ->process($model);
        }

        // Integer?
        if (is_int($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessInteger')
                ->process($model);
        }

        // Boolean?
        if (is_bool($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessBoolean')
                ->process($model);
        }

        // Null ?
        if (is_null($data)) {
            $this->pool->emergencyHandler->downOneNestingLevel();
             return $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Process\\ProcessNull')
                ->process($model);
        }

        // Still here? This should not happen. Return empty string, just in case.
        $this->pool->emergencyHandler->downOneNestingLevel();
        return '';
    }
}