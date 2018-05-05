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

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Analysing the __get() implementation in aimeos items.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe::start
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 */
class Properties extends AbstractCallback implements EventHandlerInterface
{

    public function callMe()
    {
       // Do nothing. This is a event observer, and not a callback.
    }

    /**
     * We add our magical properties right before the normal
     * public properties.
     *
     * @param AbstractCallback $params
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $params = $callback->getParameters();
        $data = $params['data'];
        $result = '';

        if (is_a($data, 'Aimeos\\MShop\\Common\\Item\\Iface')) {
            $result .= $this->extractValues('bdata', $params);
        } elseif (is_a($data, 'Aimeos\\MW\\Tree\\Node\\Iface')) {
            $result .= $this->extractValues('values', $params);
        } elseif (is_a($data, 'Aimeos\\MW\\View\\Iface')) {
            $result .= $this->extractValues('values', $params);
        }

        // Return the generated markup.
        return $result;
    }

    /**
     * Get the $this->values and then dump them.
     *
     * @param string $name
     *   The internal name of the array we need to extract
     * @param array $params
     *   The parameters from the original callback.
     *
     * @return string
     *   The generated markup.
     */
    protected function extractValues($name, array $params)
    {
        $result = array();
        $data = $params['data'];
        /** @var \ReflectionClass $ref */
        $ref = $params['ref'];

        try {
            // The property is a private property somewhere deep withing the
            // object inheritance. We might need to go deep into the rabbit hole
            // to actually get it.
            $parentReflection = $ref;
            while (!empty($parentReflection)) {
                if ($parentReflection->hasProperty($name)) {
                    $propertyRef = $parentReflection->getProperty($name);
                    $propertyRef->setAccessible(true);
                    $result = $propertyRef->getValue($data);
                    break;
                }
                // Going deeper!
                $parentReflection = $parentReflection->getParentClass();
            }
        } catch (\Exception $e) {
            // Do nothing.
        }

        // Huh, something went wrong here!
        if (empty($result) || is_array($result) === false) {
            return '';
        }
        return $this->dumpTheMagic($result);
    }

    /**
     * Dumping the array as if they are normal properties.
     *
     * @param array $array
     *   The array we dump as properties.
     *
     * @return string
     *   The generated DOM.
     */
    protected function dumpTheMagic(array $array)
    {
        $result = '';

        foreach ($array as $key => $value) {
            // Check for special stuff inside the key.
            if ($this->isPropertyNameNormal($key) === false) {
                $connectorType = Connectors::SPECIAL_CHARS_PROP;
            } else {
                $connectorType = Connectors::NORMAL_PROPERTY;
            }

            // Could be anything.
            // We need to route it though the analysis hub.
            $result .= $this->pool->routing->analysisHub(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($value)
                    ->setName($key)
                    ->setConnectorType($connectorType)
                    ->addToJson('hint', 'Aimeos magical property')
            );
        }

        return $result;
    }
}
