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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessOther;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Service\Factory\Pool;
use Closure;
use __PHP_Incomplete_Class;

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
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray
     */
    protected $processArray;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean
     */
    protected $processBoolean;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure
     */
    protected $processClosure;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat
     */
    protected $processFloat;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger
     */
    protected $processInteger;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull
     */
    protected $processNull;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject
     */
    protected $processObject;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource
     */
    protected $processResource;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString
     */
    protected $processString;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessOther
     */
    protected $processOther;

    /**
     * Inject the pool and create all the routing classes.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->processArray = $pool->createClass(ProcessArray::class);
        $this->processBoolean = $pool->createClass(ProcessBoolean::class);
        $this->processClosure = $pool->createClass(ProcessClosure::class);
        $this->processFloat = $pool->createClass(ProcessFloat::class);
        $this->processInteger = $pool->createClass(ProcessInteger::class);
        $this->processNull = $pool->createClass(ProcessNull::class);
        $this->processObject = $pool->createClass(ProcessObject::class);
        $this->processResource = $pool->createClass(ProcessResource::class);
        $this->processString = $pool->createClass(ProcessString::class);
        $this->processOther = $pool->createClass(ProcessOther::class);

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
        $result = '';

        if (is_string($data) === true) {
            // String?
            $result =  $this->processString->process($model);
        } elseif (is_int($data) === true) {
            // Integer?
            $result =  $this->processInteger->process($model);
        } elseif ($data === null) {
            // Null?
            $result =  $this->processNull->process($model);
        } elseif (is_array($data) === true ||
            is_object($data) === true ||
            $data instanceof __PHP_Incomplete_Class
        ) {
            // Handle the complex types.
            $this->pool->emergencyHandler->upOneNestingLevel();
            $result = $this->handleNoneSimpleTypes($data, $model);
            $this->pool->emergencyHandler->downOneNestingLevel();
        } elseif (is_bool($data) === true) {
            // Boolean?
            $result =  $this->processBoolean->process($model);
        } elseif (is_float($data) === true) {
            // Float?
            $result =  $this->processFloat->process($model);
        } else {
            // Resource?
            // The is_resource can not identify closed stream resource types.
            // And the get_resource_type() throws a warning, in case this is not a
            // resource.
            set_error_handler(function () {
                // Do nothing. We need to catch a possible warning.
            });
            if (get_resource_type($data) !== null) {
                $result =  $this->processResource->process($model);
            }
            restore_error_handler();
        }

        if (empty($result) === true) {
            // Tell the dev that we can not analyse this one.
            $result = $this->processOther->process($model);
        }

        return $result;
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
            return $this->handleNestedTooDeep($data, $model);
        }

        // Render recursion.
        if ($this->pool->recursionHandler->isInHive($data) === true) {
            return $this->handleRecursion($data, $model);
        }

        // Looks like we are good.
        return $this->preprocessNoneSimpleTypes($data, $model);
    }

    /**
     * This none simple type was analysed before.
     *
     * @param $data
     *   The object / array we are analysing.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function handleRecursion($data, Model $model)
    {
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

    /**
     * This none simple type was nested too deep.
     *
     * @param $data
     *   The object / array we are analysing.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The already prepared model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function handleNestedTooDeep($data, Model $model)
    {
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
        if (is_object($data) === true || $data instanceof __PHP_Incomplete_Class) {
            // Object?
            // Remember that we've been here before.
            $this->pool->recursionHandler->addToHive($data);

            // We need to check if this is an object first.
            // When calling is_a('myClass', 'anotherClass') the autoloader is
            // triggered, trying to load 'myClass', although it is just a string.
            if ($data instanceof Closure) {
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
