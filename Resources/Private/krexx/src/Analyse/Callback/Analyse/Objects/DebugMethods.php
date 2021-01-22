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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Debug;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Poll all configured debug methods of a class.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses mixed data
 *   The class we are currently analysing.
 * @uses string name
 *   The name of the object we are analysing.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class DebugMethods extends AbstractObjectAnalysis implements
    CodegenConstInterface,
    ConnectorsConstInterface,
    ConfigConstInterface
{

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of cause, not stop a possible fatal in the function
     * itself.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        $data = $reflectionClass->getData();
        $output = $this->dispatchStartEvent();

        foreach (explode(',', $this->pool->config->getSetting(static::SETTING_DEBUG_METHODS)) as $funcName) {
            if (
                $this->checkIfAccessible($data, $funcName, $reflectionClass) === true &&
                // We ignore NULL values.
                ($result = $this->retrieveValue($data, $funcName)) !== null
            ) {
                $output .= $this->pool->render->renderExpandableChild(
                    $this->dispatchEventWithModel($funcName, $this->pool->createClass(Model::class)
                        ->setName($funcName)
                        ->setType(static::TYPE_DEBUG_METHOD)
                        ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC)
                        ->setNormal(static::UNKNOWN_VALUE)
                        ->setHelpid($funcName)
                        ->setConnectorType(static::CONNECTOR_METHOD)
                        ->addParameter(static::PARAM_DATA, $result)
                        ->injectCallback($this->pool->createClass(Debug::class)))
                );
                unset($result);
            }
        }

        return $output;
    }

    /**
     * Retrieve the vale from the debug method.
     *
     * @param object $object
     *   The object we are currently analysing.
     * @param string $methodName
     *   The debug method name we want to call.
     *
     * @return mixed
     *   Whatever the method would return.
     */
    protected function retrieveValue($object, string $methodName)
    {
        $result = null;
        // Add a try to prevent the hosting CMS from doing something stupid.
        set_error_handler(
            function () {
                // Do nothing.
            }
        );
        try {
            $result = $object->$methodName();
        } catch (Throwable $e) {
            // Do nothing.
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
    }

    /**
     * Check if we are allowed to access this class method as a debug method for this class.
     *
     * @param mixed $data
     *   The class that we are currently analysing.
     * @param string $funcName
     *   The name of the function that we want to call.
     * @param ReflectionClass $reflectionClass
     *   The reflection of the class that we are currently analysing.
     *
     * @return bool
     *   Whether or not we are allowed toi access this method.
     */
    protected function checkIfAccessible($data, string $funcName, ReflectionClass $reflectionClass): bool
    {
        // We need to check if:
        // 1.) Method exists. It may be protected though.
        // 2.) Method can be called. There may be a magical method, though.
        // 3.) It's not blacklisted.
        if (
            method_exists($data, $funcName) === true &&
            is_callable([$data, $funcName]) === true &&
            $this->pool->config->validation->isAllowedDebugCall($data, $funcName) === true
        ) {
            // We need to check if the callable function requires any parameters.
            // We will not call those, because we simply can not provide them.
            try {
                $ref = $reflectionClass->getMethod($funcName);
            } catch (ReflectionException $e) {
                return false;
            }

            foreach ($ref->getParameters() as $param) {
                if ($param->isOptional() === false) {
                    // We've got a required parameter!
                    // We will not call this one.
                    return false;
                }
            }

            return true;
        }

        return false;
    }
}
