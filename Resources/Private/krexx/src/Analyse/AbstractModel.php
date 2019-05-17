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

namespace Brainworxx\Krexx\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Housing everything that does not directly hold data.
 *
 * @package Brainworxx\Krexx\Analyse
 */
abstract class AbstractModel implements ConstInterface
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Callback for the renderMe() method.
     *
     * @var AbstractCallback
     */
    protected $callback;

    /**
     * Parameters for the renderMe method.
     *
     * Should be used in the extending classes.
     *
     * @var array
     */
    protected $parameters = [];

    /**
     * Additional data, we are sending to the FE vas a json, hence the name.
     *
     * Right now, only the smokygrey skin makes use of this.
     *
     * @var array
     */
    protected $json = [];

    /**
     * The connector service, used for source generation.
     *
     * @var Connectors
     */
    protected $connectorService;


    /**
     * Inject the pool and create the connector service.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->connectorService = $pool->createClass(
            Connectors::class
        );
        $this->pool = $pool;
    }

    /**
     * Inject the callback for the renderer
     *
     * @param AbstractCallback $object
     *   The callback.
     *
     * @return $this
     *   $this for chaining
     */
    public function injectCallback(AbstractCallback $object)
    {
        $this->callback = $object;
        return $this;
    }

    /**
     * Triggers the callback (if set).
     *
     * @return string
     */
    public function renderMe()
    {
        return $this->callback
            ->setParams($this->parameters)
            ->callMe();
    }

    /**
     * Simply add a parameter for the $closure.
     *
     * @param $name
     *   The name of the parameter.
     * @param $value
     *   The value of the parameter, by reference.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function addParameter($name, &$value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

        /**
     * Setter for the $helpId.
     *
     * @param string $helpId
     *   The ID of the help text.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setHelpid($helpId)
    {
        $this->addToJson(static::META_HELP, $this->pool->messages->getHelp($helpId));
        return $this;
    }

    /**
     * We simply add more info to our info json.
     * Leftover linebreaks will be removed.
     * If the value is empty, we will remove a possible previous entry to this key.
     *
     * @param string $key
     *   The array key.
     * @param string $value
     *   The value we want to set.
     *
     * @return $this
     *   $this for chaining.
     */
    public function addToJson($key, $value)
    {
        // Remove leftover linebreaks.
        $value = trim(preg_replace("/\r|\n/", "", $value));
        if ($value === '') {
            unset($this->json[$key]);
        } else {
            $this->json[$key] = $value;
        }

        return $this;
    }

    /**
     * Getter for json.
     *
     * @return array
     *   More analysis data.
     */
    public function getJson()
    {
        return $this->json;
    }
}
