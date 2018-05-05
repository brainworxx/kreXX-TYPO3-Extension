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

namespace Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Handling the magical functions, used in Aimeos.
 *
 * Some Aimeos classes pass not existing class methods on to a private object
 * they hold, making debugging somewhat challenging. We try to remedy this by
 * analysing the available methods and getters of said object.
 *
 * @event Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods::callMe::start
 *
 * @uses mixed data
 *   The class we are currently analysing
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 */
class MagicalFunctions implements EventHandlerInterface
{
    /**
     * List of classes that have potentially implemented this.
     *
     * @var array
     */
    protected $classList = array(
        'Aimeos\\Controller\\Frontend\\Base',
        'Aimeos\\Client\\JsonApi\\Base',
        'Aimeos\\Client\\Html\\Base',
        'Aimeos\\Admin\\JsonAdm\\Base',
        'Aimeos\\Admin\\JQAdm\\Base',
        'Aimeos\\MW\\View\\Helper\\Base',
        'Aimeos\\MW\\View\\Iface',
        'Aimeos\\MShop\\Service\\Provider\\Base',
        'Aimeos\\MW\\Common\\Manager\\Base',
        'Aimeos\\Controller\\Jobs\\Common\\Decorator\\Base',
    );

    /**
     * List of possible internal names of the recipient class.
     *
     * @var array
     */
    protected $internalObjectNames = array(
        'controller' => '$this->controller,',
        'manager' => '$this->manager,',
        'object' => '$this->object,',
        'view' => '$this->view,',
        'delegate' => '$this->delegate,',
        'client' => '$this->client,',
    );

    /**
     * Our pool, what else?
     *
     * @var Pool
     */
    protected $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * The "magic" starts here.
     *
     * @param array $params
     *   The parameters from the analyse class
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @throws \ReflectionException
     *
     * @return string
     *   The generated markup.
     */
    public function handle(array $params, Model $model = null)
    {
        $result = '';
        $data = $params['data'];

        if ($this->checkClassName($data) === false) {
            // Early return, we skip this one.
            return $result;
        }

        $receiver = $this->retrieveReceiverObject($data, $params['ref']);
        if (empty($receiver)) {
            // Unable to get anything from the class.
            // There is nothing more we can do here.
            return $result;
        }


        // Now that we have an object, we must analyse its public methods
        // and getter methods.
        // We will simply abuse the already existing analysis classes for this.
        $receiverParams = array(
            'data' => $receiver,
            'ref' => new \ReflectionClass($receiver),
            // The aimeos class name will get set as additional data info.
            'aimeos object' => get_class($receiver),
            'aimeos name' => 'Aimeos Magical Methods'
        );

        // We may be facing receivers within receivers within receivers.
        // Hence, we need to keep track of the already rendered methods.
        $receiverParams['this run'] = get_class_methods($receiver);
        if (isset($params['methods done']) === false) {
            $receiverParams['methods done'] = array();
        }
        // Create a whitelist of leftover methods.
        $lookupArray = array_flip($receiverParams['methods done']);
        foreach ($receiverParams['this run'] as $methodName) {
            // @todo hier weitermachen
        }


        // Dump the methods of the receiver object.
        $result .= $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Methods')
            ->setParams($receiverParams)
            ->callMe();

        // Dump the getter of the receiver object.
        $receiverParams['aimeos name'] = 'Aimeos Magical Getter';
        $result .= $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Getter')
            ->setParams($receiverParams)
            ->callMe();



        return $result;
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
     * Retrieve the recipent object from the aimeos object.
     *
     * @param mixed $data
     *   The aimeos object we need to get the receiver class from.
     * @param \ReflectionClass $ref
     *   The reflection of the class we are analysing.
     *
     * @return mixed
     *   Either a false, or the object that receives all method calls.
     */
    protected function retrieveReceiverObject($data, \ReflectionClass $ref)
    {
        // First, we need to get the name of the object we need to retrieve.
        // Get the __call() source code.
        $methodRef = $ref->getMethod('__call');

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
                    return $propertyRef->getValue($data);
                }
                // Going deeper!
                $parentReflection = $parentReflection->getParentClass();
            }
        } catch (\Exception $e) {
            // Do nothing.
        }

        // Still here?
        return false;
    }
}