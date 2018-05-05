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
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Resolving the Aimeos getter:
 * $this->values[$this->prefix . 'somekey']
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 * @event
 *
 * @uses array normalGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'get'
 * @uses array isGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'is'
 * @uses array hasGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'has'
 * @uses \ReflectionClass $ref
 *   A reflection class of the object we are analysing.
 * @uses object $data
 *   The object we are currently analysing
 * @uses string $currentPrefix
 *   The current prefix we are analysing (get, is, has).
 *   Does not get set from the outside.
 * @uses array $additional
 *   Additional data from the event call.
 */
class Getter  implements EventHandlerInterface
{

    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Some special resolving of Aimeos getter
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
        // We will only act, if we have no value so far.
        // Also, we only do this for Aimeos items.
        $params = $callback->getParameters();
        $data = $params['data'];
        if ($params['additional']['nothingFound'] === false ||
            $params['currentPrefix'] !== 'get' ||
            is_a($data, 'Aimeos\\MShop\\Common\\Item\\Iface') === false
        ) {
            // Early return.
            return '';
        }

        // The key should be the lowercase getter name,
        // without the get, plus some prefix, separated with a dot.
        // 'getCustomerId' should be 'some.key.customerid'
        /** @var \ReflectionMethod $reflectionMethod */
        $reflectionMethod = $params['additional']['refMethod'];
        $possibleKey = strtolower(substr($reflectionMethod->name, 3));

        $values = $this->retrieveValueArray($params);

        if (empty($values) === true) {
            // There is nothing to retrieve here.
            // Not-so-Early return.
            return '';
        }

        // Going through the values and try to get something out of it.
        foreach ($values as $key => $possibleResult) {
            $keyParts = explode('.', $key);
            if ($keyParts[count($keyParts) - 1] === $possibleKey) {
                // We've got ourselves a result.
                $params['additional']['nothingFound'] = false;

                // Update the model.
                $model->setData($possibleResult);
                if ($possibleResult === null) {
                    // A NULL value might mean that the values does not
                    // exist, until the getter computes it.
                    $model->addToJson('hint', $this->pool->messages->getHelp('getterNull'));
                }

                break;
            }
        }

        // Update the parameters in the callback . . .
        $callback->setParams($params);

        // This will not get used by the event itself here.
        // Return an empty string.
        return '';
    }

    /**
     * @param array $params
     *   The parameters from the callback
     * @return array
     *   The values array from the class.
     */
    protected function retrieveValueArray(array &$params)
    {
        // Caching.
        // We might alreday have retrieved this value.
        if (isset($params['valueCache'])) {
            return $params['valueCache'];
        }

        $params['valueCache'] = array();
        // Retrieve the value array from the class.
        /** @var \ReflectionClass $reflectionClass */
        $reflectionClass = $params['ref'];
        $data = $params['data'];
        if ($reflectionClass->hasProperty('values')) {
            $reflectionProperty = $reflectionClass->getProperty('values');
            $reflectionProperty->setAccessible(true);
            $params['valueCache'] = $reflectionProperty->getValue($data);
        } elseif ($reflectionClass->hasProperty('bdata')) {
            $reflectionProperty = $reflectionClass->getProperty('bdata');
            $reflectionProperty->setAccessible(true);
            $params['valueCache'] = $reflectionProperty->getValue($data);
        }

        // Check for array.
        // If this is not an array, something went wrong. We need to make sure
        // that a possible wrong result does not kill off the rest of the
        // analysis.
        if (is_array($params['valueCache']) === false) {
            $params['valueCache'] = array();
        }

        return $params['valueCache'];
    }
}