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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Aimeos\Base\View\Iface as BaseViewIface;
use Aimeos\MShop\Common\Item\Iface as ItemIface;
use Aimeos\MW\Tree\Node\Iface as NodeIface;
use Aimeos\MW\View\Iface as ViewIface;

/**
 * Analysing the __get() implementation in aimeos items.
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties::callMe::start
 *
 * @uses object data
 *   The class we are currently analysing.
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Properties extends AbstractEventHandler implements
    CallbackConstInterface,
    ConnectorsConstInterface,
    CodegenConstInterface
{
    /**
     * Our pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

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
     * We add our magical properties right before the normal
     * public properties.
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
        $params = $callback->getParameters();
        $data = $params[static::PARAM_DATA];
        $result = '';

        if ($data instanceof ItemIface) {
            $result .= $this->extractValues(static::AIMEOS_B_DATA, $params);
        } elseif (
            $data instanceof NodeIface || $data instanceof ViewIface || $data instanceof BaseViewIface
        ) {
            $result .= $this->extractValues(static::AIMEOS_VALUES, $params);
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
    protected function extractValues(string $name, array $params): string
    {
        $result = [];
        $data = $params[static::PARAM_DATA];
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $params[static::PARAM_REF];

        // The property is a private property somewhere deep withing the
        // object inheritance. We might need to go deep into the rabbit hole
        // to actually get it.
        $parentReflection = $ref;
        while (
            $parentReflection !== false
            && empty($result)
        ) {
            $result = $this->retrieveProperty($parentReflection, $name, $data);
            $parentReflection = $parentReflection->getParentClass();
        }

        // Huh, something went wrong here!
        if (empty($result) || !is_array($result)) {
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
    protected function dumpTheMagic(array $array): string
    {
        $result = '';

        foreach ($array as $key => $value) {
            // Could be anything.
            // We need to route it though the analysis hub.
            if ($this->pool->encodingService->isPropertyNameNormal($key)) {
                $connectorType = static::CONNECTOR_NORMAL_PROPERTY;
            } else {
                $connectorType = static::CONNECTOR_SPECIAL_CHARS_PROP;
            }

            $result .= $this->pool->routing->analysisHub(
                $this->pool->createClass(Model::class)
                    ->setData($value)
                    ->setName($key)
                    ->setConnectorType($connectorType)
                    ->setCodegenType(static::CODEGEN_TYPE_PUBLIC)
                    ->addToJson(
                        $this->pool->messages->getHelp('metaHint'),
                        $this->pool->messages->getHelp('aimeosMagicProp')
                    )
            );
        }

        return $result;
    }
}
