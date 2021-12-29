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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\ConstInterface as AimeosConstInterface;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionMethod;
use Aimeos\MShop\Common\Item\Iface;

/**
 * Resolving the Aimeos getter:
 * $this->values[$this->prefix . 'somekey']
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
class Getter extends AbstractEventHandler implements CallbackConstInterface
{
    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * The names of the internal storages if Aimeos items.
     *
     * @var array
     */
    protected $aimeosDataStorages = [
        AimeosConstInterface::AIMEOS_B_DATA,
        AimeosConstInterface::AIMEOS_DATA,
        AimeosConstInterface::AIMEOS_VALUES
    ];

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Some special resolving of Aimeos getter
     *
     * @param AbstractCallback $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, Model $model = null): string
    {
        // We will only act, if we have no value so far.
        // Also, we only do this for Aimeos items.
        $params = $callback->getParameters();
        $data = $params[static::PARAM_REF]->getData();

        if (
            $params[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND] === false ||
            $params[ThroughGetter::CURRENT_PREFIX] !== 'get' ||
            is_a($data, Iface::class) === false
        ) {
            // Early return.
            return '';
        }

        // The key should be the lowercase getter name,
        // without the get, plus some prefix, separated with a dot.
        // 'getCustomerId' should be 'some.key.customerid'
        /** @var \ReflectionMethod $reflectionMethod */
        $reflectionMethod = $params[static::PARAM_ADDITIONAL][static::PARAM_REFLECTION_METHOD];
        $values = $this->retrieveValueArray($params, $reflectionMethod);
        if (empty($values) === true) {
            // There is nothing to retrieve here.
            // Not-so-Early return.
            return '';
        }

        $this->assignResultsToModel($values, $model, $callback);

        // This will not get used by the event itself here.
        // Return an empty string.
        return '';
    }

    /**
     * Assign a possible value to the model and update the parameters in the callback.
     *
     * @param array $values
     *   Array of possible values.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback $callback
     *   Our original callback.
     */
    protected function assignResultsToModel(array $values, Model $model, AbstractCallback $callback): void
    {
        $params = $callback->getParameters();
        /** @var \ReflectionMethod $reflectionMethod */
        $reflectionMethod = $params[static::PARAM_ADDITIONAL][static::PARAM_REFLECTION_METHOD];
        $possibleKey = $this->retrievePossibleKey($reflectionMethod->name);

        foreach ($values as $key => $possibleResult) {
            $keyParts = explode('.', $key);
            if ($keyParts[count($keyParts) - 1] === $possibleKey) {
                // We've got ourselves a result.
                $params[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND] = false;
                // Update the parameters in the callback . . .
                $callback->setParameters($params);

                // Update the model.
                $model->setData($possibleResult);
                if ($possibleResult === null) {
                    // A NULL value might mean that the values does not
                    // exist, until the getter computes it.
                    $model->addToJson(
                        $this->pool->messages->getHelp('metaHint'),
                        $this->pool->messages->getHelp('getterNull')
                    );
                }

                break;
            }
        }
    }

    /**
     * Retrieve the possible key of the return value from the getter name.
     *
     * @param string $methodName
     *   The name og the getter method.
     *
     * @return string
     *   The possible key.
     */
    protected function retrievePossibleKey(string $methodName): string
    {
        $possibleKey = strtolower(substr($methodName, 3));

        // Not all stored data keys can be derived directly from the getter name.
        if ($possibleKey === 'timemodified') {
            $possibleKey = 'mtime';
        } elseif ($possibleKey === 'timecreated') {
            $possibleKey = 'ctime';
        }

        return $possibleKey;
    }

    /**
     * Retrieve the data array from the class.
     *
     * @param array $params
     *   The parameters from the callback
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the getter we are analysing.
     *
     * @return array
     *   The values array from the class.
     */
    protected function retrieveValueArray(array $params, ReflectionMethod $reflectionMethod): array
    {
        $result = [];
        // Retrieve the value array from the class.
        // Everything sits in a private array, so we do not need to walk
        // through the whole class structure.
        $reflectionClass = $reflectionMethod->getDeclaringClass();
        $data = $params[static::PARAM_REF]->getData();

        foreach ($this->aimeosDataStorages as $propertyName) {
            $value = $this->retrieveProperty($reflectionClass, $propertyName, $data);
            if (is_array($value)) {
                $result = array_merge($result, $value);
            }
        }

        return $result;
    }
}
