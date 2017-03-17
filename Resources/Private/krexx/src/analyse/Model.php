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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Code\Connectors;

/**
 * Model for the view rendering
 *
 * @package Brainworxx\Krexx\Analyse
 */
class Model
{
    /**
     * The object/string/array/whatever we are analysing right now
     *
     * @var mixed
     */
    protected $data;

    /**
     * The name/key of it.
     *
     * @var string|int
     */
    protected $name = '';

    /**
     * The short result of the analysis.
     *
     * @var string
     */
    protected $normal = '';

    /**
     * Additional data that gets added to the type. Normally something like
     * 'protected static final'
     *
     * @var string
     */
    protected $additional = '';

    /**
     * The type of the variable we are analysing, in a string.
     *
     * @var string
     */
    protected $type = '';

    /**
     * The ID of the help text.
     *
     * @var string
     */
    protected $helpid = '';

    /**
     * Additional data, we are sending to the FE vas a json, hence the name.
     *
     * Right now, only the smokygrey skin makes use of this.
     *
     * @var array
     */
    protected $json = array();

    /**
     * A unique ID for the dom. We use this one for recursion resolving via JS.
     *
     * @var string
     */
    protected $domid = '';

    /**
     * Parameters for the renderMe method.
     *
     * Should be used in the extending classes.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Callback for the renderMe() method.
     *
     * @var AbstractCallback
     */
    protected $callback;

    /**
     * Info, if we have "extra" data to render.
     *
     * @see render->renderSingleChild()
     *
     * @var bool
     */
    protected $hasExtra = false;

    /**
     * Are we dealing with multiline code generation?
     *
     * @var string
     */
    protected $multiLineCodeGen = '';

    /**
     * Defines if the content of the variable qualifies as a callback.
     *
     * @var bool
     */
    protected $isCallback = false;

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
            'Brainworxx\\Krexx\\Service\\Code\\Connectors'
        );
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
        if (is_object($this->callback)) {
            $this->callback->setParams($this->parameters);
            return $this->callback->callMe();
        } else {
            return '';
        }
    }

    /**
     * Setter for the data.
     *
     * @param mixed $data
     *   The current variable we are rendering.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setData(&$data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Getter for the data.
     *
     * @return mixed
     *   The variable, we are currently analysing.
     */
    public function &getData()
    {
        return $this->data;
    }

    /**
     * Setter for the name.
     *
     * @param int|string $name
     *   The name/key we are analysing.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Getter for the name.
     *
     * @return int|string
     *   The name/key we are analysing.
     */
    public function &getName()
    {
        return $this->name;
    }

    /**
     * Setter for normal.
     *
     * @param string $normal
     *   The short result of the analysis.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setNormal($normal)
    {
        $this->normal = $normal;
        return $this;
    }

    /**
     * Getter for normal.
     *
     * @return string
     *   The short result of the analysis.
     */
    public function getNormal()
    {
        return $this->normal;
    }

    /**
     * Setter for additional.
     *
     * @param string $additional
     *   The long result of the analysis.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setAdditional($additional)
    {
        $this->additional = $additional;
        return $this;
    }

    /**
     * Getter for additional
     *
     * @return string
     *   The long result of the analysis.
     */
    public function getAdditional()
    {
        return $this->additional;
    }

    /**
     * Setter for the type.
     *
     * @param string $type
     *   The type of the variable we are analysing.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Getter for the type.
     *
     * @return string
     *   The type of the variable we are analysing
     */
    public function getType()
    {
        return $this->additional . $this->type;
    }

    /**
     * Setter for the helpid.
     *
     * @param string $helpid
     *   The ID of the help text.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setHelpid($helpid)
    {
        $this->helpid = $helpid;
        return $this;
    }

    /**
     * Getter for the help id.
     *
     * @return string
     *   The ID of the help text.
     */
    public function getHelpid()
    {
        return $this->helpid;
    }

    /**
     * Getter got connector1.
     *
     * @return string
     *   The first connector.
     */
    public function getConnector1()
    {
        return $this->connectorService->getConnector1();
    }

    /**
     * Getter for connector2.
     *
     * @return string
     *   The second connector.
     */
    public function getConnector2()
    {
        return $this->connectorService->getConnector2();
    }

    /**
     * We simply add more info to our info json.
     *
     * @param $key
     *   The array key.
     * @param $value
     *   The value we want to set.
     *
     * @return $this
     *   $this for chaining.
     */
    public function addToJson($key, $value)
    {
        // Remove leftover linebreaks.
        $this->json[$key] = preg_replace("/\r|\n/", "", $value);
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

    /**
     * Setter for domid.
     *
     * @param string $domid
     *   The dom id, of cause.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setDomid($domid)
    {
        $this->domid = $domid;
        return $this;
    }

    /**
     * Getter for domid.
     *
     * @return string
     *   The dom id, of cause.
     */
    public function getDomid()
    {
        return $this->domid;
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
     * Getter for the hasExtras property.
     *
     * @return bool
     *   Info for the render class, if we need to render the extras part.
     */
    public function getHasExtras()
    {
        return $this->hasExtra;
    }

    /**
     * "Setter" for the hasExtras property.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function hasExtras()
    {
        $this->hasExtra = true;
        return $this;
    }

    /**
     * Getter for the multiline code generation.
     *
     * @return string
     */
    public function getMultiLineCodeGen()
    {
        return $this->multiLineCodeGen;
    }

    /**
     * Setter for the multiline code generation.
     *
     * @param string $multiLineCodeGen
     *   The constant from the Codegen class.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setMultiLineCodeGen($multiLineCodeGen)
    {
        $this->multiLineCodeGen = $multiLineCodeGen;
        return $this;
    }

    /**
     * Getter for the $isCallback.
     *
     * @return boolean
     */
    public function getIsCallback()
    {
        return $this->isCallback;
    }

    /**
     * Setter for the $isCallback.
     *
     * @param boolean $isCallback
     */
    public function setIsCallback($isCallback)
    {
        $this->isCallback = $isCallback;
    }

     /**
     * Setter for the $params. It is used in case we are connection a method or
     * closure.
     *
     * @param string $params
     *   The parameters as a sting.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setConnectorParameters($params)
    {
        $this->connectorService->setParameters($params);
        return $this;
    }

    /**
     * Getter for the connection parameters.
     *
     * @return string
     *   The connection parameters.
     */
    public function getConnectorParameters()
    {
        return $this->connectorService->getParameters();
    }

    /**
     * Setter for the type we are rendering, using the class constants.
     *
     * @param string $type
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function setConnectorType($type)
    {
        $this->connectorService->setType($type);
        return $this;
    }

    /**
     * Sets a special and custom connector1. Only used for constants code
     * generation.
     *
     * @param string $string
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setCustomConnector1($string)
    {
        $this->connectorService->setCustomConnector1($string);
        return $this;

    }

    /**
     * Getter for the Language of the connector service.
     *
     * @return string
     */
    public function getConnectorLanguage()
    {
        return $this->connectorService->getLanguage();
    }

    /**
     * Getter for all parameters for the internal callback.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }
}
