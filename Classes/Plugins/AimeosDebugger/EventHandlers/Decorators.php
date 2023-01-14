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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughClassList;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughMethods;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;

/**
 * Resolving Aimeos magical decorator class methods.
 */
class Decorators extends AbstractEventHandler implements CallbackConstInterface
{
    /**
     * List of possible internal names of the recipient class.
     *
     * @var string[]
     */
    protected $internalObjectNames = [
        'controller' => '$this->controller,',
        'manager' => '$this->manager,',
        'object' => '$this->object,',
        'view' => '$this->view,',
        'delegate' => '$this->delegate,',
        'client' => '$this->client,',
    ];

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Resolving the possible methods from the decorator pattern.
     *
     * @param AbstractCallback $callback
     *   The original callback.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, Model $model = null): string
    {
        $result = '';
        $params = $callback->getParameters();

        // Get a first impression.
        if (!$this->checkClassName($params[static::PARAM_REF])) {
            // Early return, we skip this one.
            return $result;
        }

        // Retrieve all piled up receiver objects. We may have decorators
        // inside of decorators.
        $allReceivers = [];
        $methods = $this->retrieveMethods($params, $allReceivers);

        if (!empty($methods)) {
            // Got to dump them all!
            $result .= $this->pool->render->renderExpandableChild($this->pool->createClass(Model::class)
                ->setName($this->pool->messages->getHelp('aimeosUndecoratedMeth'))
                ->setType('class internals decorator')
                ->addParameter(static::PARAM_DATA, $methods)
                ->setHelpid('aimeosDecoratorsInfo')
                ->injectCallback($this->pool->createClass(ThroughMethods::class)));
        }

        // Do a normal analysis of all receiver objects.
        if (!empty($allReceivers)) {
            $this->pool->codegenHandler->setCodegenAllowed(false);
            $result .= $this->pool->render->renderExpandableChild($this->pool->createClass(Model::class)
                ->setName($this->pool->messages->getHelp('aimeosDecoratedObj'))
                ->setType('class internals decorator')
                ->addParameter(static::PARAM_DATA, $allReceivers)
                ->injectCallback($this->pool->createClass(ThroughClassList::class)));
            $this->pool->codegenHandler->setCodegenAllowed(true);
        }

        return $result;
    }

    /**
     * Retrieve the methods and the receiving class from the decorator.
     *
     * @param array $params
     *   The parameters from the original callback.
     * @param object[] $allReceivers
     *   By value of all known receivers. We can only have one return value,
     *   but we retrieve two different values.
     *
     * @return string[]
     *   The  methods we need to analyse.
     */
    protected function retrieveMethods(array $params, array &$allReceivers): array
    {
        $receiver = $this->retrieveReceiverObject($params[static::PARAM_DATA], $params[static::PARAM_REF]);
        $methods = [];
        while ($receiver !== false) {
            $methods = $this->retrievePublicMethods($params[static::PARAM_REF]);
            $allReceivers[] = $receiver;

            try {
                $ref = new ReflectionClass($receiver);
            } catch (ReflectionException $e) {
                // We skip this one.
                return  $methods;
            }

            // Get the not-decorated methods on the way.
            $methods = array_diff_key(
                $this->retrievePublicMethods($ref),
                $methods
            );

            // Going deeper.
            $receiver = $this->retrieveReceiverObject($receiver, $ref);
        }

        return $methods;
    }

    /**
     * Only some classes have this implemented. We check only these.
     *
     * @param ReflectionClass $reflectionClass
     *   The class we are currently analysing.
     *
     * @return bool
     *   Whether we have found a potential class.
     */
    protected function checkClassName(ReflectionClass $reflectionClass): bool
    {
        // We only check Aimeos classes.
        do {
            if (strpos($reflectionClass->getNamespaceName(), 'Aimeos') === 0) {
                return true;
            }
            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass !== false);
        // Nothing found. We will skip this one.
        return false;
    }

    /**
     * Retrieve the recipient object name from the aimeos object.
     *
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     *
     * @return string
     *   Either a false, or the object that receives all method calls.
     */
    protected function retrieveReceiverObjectName(ReflectionClass $ref): string
    {
        // First, we need to get the name of the object we need to retrieve.
        // Get the __call() source code.
        try {
            $methodRef = $ref->getMethod('__call');
        } catch (ReflectionException $e) {
            return '';
        }

        $source = $this->pool->fileService->readFile(
            $methodRef->getFileName(),
            $methodRef->getStartLine(),
            $methodRef->getStartLine() + 5
        );

        // Check if we are passing methods, at all.
        if (strpos($source, 'call_user_func') === false) {
            return '';
        }

        // Still here? Now for the serious stuff.
        foreach ($this->internalObjectNames as $name => $needle) {
            if (strpos($source, $needle) !== false) {
                return $name;
            }
        }

        return '';
    }

    /**
     * Retrieve the recipient object from the aimeos object.
     *
     * @param mixed $data
     *   The aimeos object we need to get the receiver class from.
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     *
     * @return false|object
     *   Either a false, or the object that receives all method calls.
     */
    protected function retrieveReceiverObject($data, ReflectionClass $ref)
    {
        $objectName = $this->retrieveReceiverObjectName($ref);
        if (empty($objectName)) {
            // Unable to retrieve the object name.
            return false;
        }

        // Now to get the object.
        // The property is a private property somewhere deep withing the
        // object inheritance. We might need to go deep into the rabbit hole
        // to actually get it.
        $parentReflection = $ref;
        while (!empty($parentReflection)) {
            $receiver = $this->retrieveProperty($parentReflection, $objectName, $data);
            if (is_object($receiver)) {
                return $receiver;
            }

            // Going deeper!
            $parentReflection = $parentReflection->getParentClass();
        }

        // Still here?
        return false;
    }

    /**
     * Retrieve a name based array of the public methods of a reflection.
     *
     * @param \ReflectionClass $ref
     *   The reflection from where we want to retrieve the method list.
     *
     * @return array
     *   Name based array with the methods names.
     */
    protected function retrievePublicMethods(ReflectionClass $ref): array
    {
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        $result = [];
        foreach ($methods as $refMethod) {
            $result[$refMethod->name] = $refMethod;
        }

        return $result;
    }
}
