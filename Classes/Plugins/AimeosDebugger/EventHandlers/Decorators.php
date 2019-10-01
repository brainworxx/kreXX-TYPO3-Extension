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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughClassList;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughMethods;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionClass;
use Exception;
use Throwable;
use ReflectionMethod;
use Aimeos\Admin\JQAdm\Common\Decorator\Iface as JQAdmDecoratorInterface;
use Aimeos\Admin\JsonAdm\Common\Decorator\Iface as JsonAdmDecoratorInterface;
use Aimeos\Client\Html\Common\Decorator\Iface as HtmlDecoratorInterface;
use Aimeos\Client\JsonApi\Common\Decorator\Iface as JsonApiDecoratorInterface;
use Aimeos\Controller\Frontend\Common\Decorator\Iface as FrontendDecoratorInterface;
use Aimeos\Controller\Jobs\Common\Decorator\Iface as JobsDecoratorInterface;
use Aimeos\MShop\Common\Manager\Decorator\Iface as ManagerDecoratorInterface;
use Aimeos\MShop\Service\Provider\Decorator\Base as ServiceProviderDecoratorBase;
use ReflectionException;

/**
 * Resolving Aimeos magical decorator class methods.
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers
 */
class Decorators implements EventHandlerInterface, ConstInterface
{
    /**
     * List of classes that have potentially implemented this.
     *
     * @var array
     */
    protected $classList = [
        JQAdmDecoratorInterface::class,
        JsonAdmDecoratorInterface::class,
        HtmlDecoratorInterface::class,
        JsonApiDecoratorInterface::class,
        FrontendDecoratorInterface::class,
        JobsDecoratorInterface::class,
        ManagerDecoratorInterface::class,
        ServiceProviderDecoratorBase::class
    ];

    /**
     * List of possible internal names of the recipient class.
     *
     * @var array
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
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $result = '';
        $params = $callback->getParameters();

        // Get a first impression.
        $data = $params[static::PARAM_DATA];
        if ($this->checkClassName($data) === false) {
            // Early return, we skip this one.
            return $result;
        }

        // Retrieve all piled up receiver objects. We may have decorators
        // inside of decorators.
        $allReceivers = [];
        $methods = $this->retrieveMethods($params, $allReceivers);

        if (empty($methods) === false) {
            // Got to dump them all!
            $result .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass(Model::class)
                    ->setName('Decorator Methods')
                    ->setType('class internals decorator')
                    ->addParameter(static::PARAM_DATA, $methods)
                    ->setHelpid('aimeosDecoratorsInfo')
                    ->injectCallback($this->pool->createClass(ThroughMethods::class))
            );
        }

        // Do a normal analysis of all receiver objects.
        if (empty($allReceivers) === false) {
            $this->pool->codegenHandler->setAllowCodegen(false);
            $result .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass(Model::class)
                    ->setName('Decorator Objects')
                    ->setType('class internals decorator')
                    ->addParameter(static::PARAM_DATA, $allReceivers)
                    ->injectCallback($this->pool->createClass(ThroughClassList::class))
            );
            $this->pool->codegenHandler->setAllowCodegen(true);
        }

        return $result;
    }

    /**
     * Retrieve the methods and the receiving class from the decorator.
     *
     * @param array $params
     *   The parameters from the original callback.
     * @param array $allReceivers
     *   By value of all known recievers. We can only have one return value,
     *   but we retrieve two different values.
     *
     * @return array
     *   The  methods wqe need to analyse.
     */
    protected function retrieveMethods(array $params, array &$allReceivers)
    {
        $receiver = $this->retrieveReceiverObject($params[static::PARAM_DATA], $params[static::PARAM_REF]);
        $methods = [];
        while ($receiver !== false) {
            $allReceivers[] = $receiver;
            try {
                $ref = new ReflectionClass($receiver);
            } catch (ReflectionException $e) {
                // We skip this one.
                continue;
            }

            $receiver = $this->retrieveReceiverObject($receiver, $ref);
            // Get all reflection methods together, from all the receivers.
            // The receivers in front overwrite the methods from the receivers
            // in the back.
            $methods = array_merge($this->retrievePublicMethods($ref), $methods);
        }

        return $methods;
    }

    /**
     * Only some classes have this implemented. We check only these.
     *
     * Checking every other class if they have implemented __call, and then
     * parsing the source code, if the implementation fits the bill is not
     * something we will do at this early stage.
     *
     * @param mixed $data
     *   The class we are currently analysing.
     *
     * @return boolean
     *   Whether we have found a potential class.
     */
    protected function checkClassName($data)
    {
        foreach ($this->classList as $className) {
            if (is_a($data, $className) && method_exists($data, '__call')) {
                return true;
            }
        }

        // Nothing found. We will skip this one.
        return false;
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
        // First, we need to get the name of the object we need to retrieve.
        // Get the __call() source code.
        try {
            $methodRef = $ref->getMethod('__call');
        } catch (ReflectionException $e) {
            return false;
        }

        $source = $this->pool->fileService->readFile(
            $methodRef->getFileName(),
            $methodRef->getStartLine(),
            $methodRef->getStartLine() + 5
        );

        // Check if we are passing methods, at all.
        if (strpos($source, 'call_user_func') === false) {
            return false;
        }

        // Still here? Now for the serious stuff.
        $objectName = false;
        foreach ($this->internalObjectNames as $name => $needle) {
            if (strpos($source, $needle) !== false) {
                $objectName = $name;
                break;
            }
        }
        if (empty($objectName)) {
            // Unable to retrieve the object name.
            return false;
        }

        // Now to get the object.
        try {
            // The property is a private property somewhere deep withing the
            // object inheritance. We might need to go deep into the rabbit hole
            // to actually get it.
            $parentReflection = $ref;
            while (!empty($parentReflection)) {
                if ($parentReflection->hasProperty($objectName)) {
                    $propertyRef = $parentReflection->getProperty($objectName);
                    $propertyRef->setAccessible(true);
                    $receiver = $propertyRef->getValue($data);
                    if (is_object($receiver)) {
                        return $receiver;
                    }
                }
                // Going deeper!
                $parentReflection = $parentReflection->getParentClass();
            }
        } catch (Throwable $e) {
            // Do nothing.
        } catch (Exception $e) {
            // Do nothing.
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
    protected function retrievePublicMethods(ReflectionClass $ref)
    {
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        $result = [];
        foreach ($methods as $refMethod) {
            $result[$refMethod->name] = $refMethod;
        }

        return $result;
    }
}
