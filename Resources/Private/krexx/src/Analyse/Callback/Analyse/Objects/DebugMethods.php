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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionException;
use Throwable;

/**
 * Poll all configured debug methods of a class.
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
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of course, not stop a possible fatal in the function
     * itself.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        $data = $reflectionClass->getData();
        $functionNames = $this->pool->config->getSetting(name: static::SETTING_DEBUG_METHODS);
        foreach (explode(separator: ',', string: $functionNames) as $funcName) {
            if (
                $this->checkIfAccessible(data: $data, funcName: $funcName, reflectionClass: $reflectionClass) &&
                // We ignore NULL values.
                ($result = $this->retrieveValue(object: $data, methodName: $funcName)) !== null
            ) {
                $output .= $this->pool->render->renderExpandableChild(
                    model: $this->dispatchEventWithModel(
                        name: $funcName,
                        model: $this->pool->createClass(classname: Model::class)
                            ->setName(name: $funcName)
                            ->setType(type: $this->pool->messages->getHelp(key: 'debugMethod'))
                            ->setCodeGenType(codeGenType: static::CODEGEN_TYPE_PUBLIC)
                            ->setNormal(normal: static::UNKNOWN_VALUE)
                            ->setHelpid(helpId: $funcName)
                            ->setConnectorType(type: static::CONNECTOR_METHOD)
                            ->addParameter(name: static::PARAM_DATA, value: $result)
                            ->injectCallback(object: $this->pool->createClass(classname: Debug::class))
                    )
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
    protected function retrieveValue(object $object, string $methodName): mixed
    {
        $result = null;
        // Add a try to prevent the hosting CMS from doing something stupid.
        set_error_handler(callback: $this->pool->retrieveErrorCallback());
        try {
            $result = $object->$methodName();
        } catch (Throwable) {
            // Do nothing.
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        return $result;
    }

    /**
     * Check if we are allowed to access this class method as a debug method for this class.
     *
     * @param object $data
     *   The class that we are currently analysing.
     * @param string $funcName
     *   The name of the function that we want to call.
     * @param ReflectionClass $reflectionClass
     *   The reflection of the class that we are currently analysing.
     *
     * @return bool
     *   Whether we are allowed to access this method.
     */
    protected function checkIfAccessible(object $data, string $funcName, ReflectionClass $reflectionClass): bool
    {
        // We need to check if:
        // 1. Method exists. It may be protected though.
        // 2. Method can be called. There may be a magical method, though.
        // 3. It's not blacklisted.
        if (
            !method_exists(object_or_class: $data, method: $funcName) ||
            !is_callable(value: [$data, $funcName]) ||
            !$this->pool->config->validation->isAllowedDebugCall(data: $data, method: $funcName)
        ) {
            return false;
        }

        // We need to check if the callable function requires any parameters.
        // We will not call those, because we simply can not provide them.
        try {
            $ref = $reflectionClass->getMethod(name: $funcName);
            return $ref->getNumberOfRequiredParameters() === 0;
        } catch (ReflectionException) {
            return false;
        }
    }
}
