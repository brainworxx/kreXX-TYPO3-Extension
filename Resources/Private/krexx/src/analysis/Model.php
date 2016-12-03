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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Storage;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

/**
 * Model for the view rendering
 *
 * @package Brainworxx\Krexx\Analyse
 */
class Model
{
    /**
     * Here we store all relevant data.
     *
     * @var Storage
     */
    protected $storage;

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
     * When the long result of the analysis, used if "normal" does not
     * provide enough room.
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
     * The first connector.
     *
     * @var string
     */
    protected $connector1 = '';

    /**
     * The second connector.
     *
     * @var string
     */
    protected $connector2 = '';

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
     * Injects the storage.
     *
     * @param Storage $storage
     *   The storage, where we store the classes we need.
     */
    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
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
     * @return Model
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
     * @return Model
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
     * @return Model
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
     * @return Model
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
     * @return mixed
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
     * @return Model
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
        return $this->type;
    }

    /**
     * Setter for the helpid.
     *
     * @param string $helpid
     *   The ID of the help text.
     *
     * @return Model
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
     * Setter for connector1.
     *
     * @param string $connector1
     *   The first connector.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setConnector1($connector1)
    {
        $this->connector1 = $connector1;
        return $this;
    }

    /**
     * Getter got connector1.
     *
     * @return string
     *   The first connector.
     */
    public function getConnector1()
    {
        return $this->connector1;
    }

    /**
     * Setter for connector2.
     *
     * @param string $connector2
     *   The second connector.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setConnector2($connector2)
    {
        $this->connector2 = $connector2;
        return $this;
    }

    /**
     * Getter for connector2.
     *
     * @return string
     *   The second connector.
     */
    public function getConnector2()
    {
        return $this->connector2;
    }

    /**
     * Setter for json.
     *
     * @param array $json
     *   More analysis data.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function setJson($json)
    {
        $this->json = $json;
        return $this;
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
        $this->json[$key] = $value;
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
     * @return Model
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
     * @return Model
     *   $this, for chaining.
     */
    public function addParameter($name, &$value)
    {
        $this->parameters[$name] = $value;
        return $this;
    }

    /**
     * Initializes the callback for the renderMe method
     *
     * @param string $name
     *   The name and part of the namespace of the callback class.
     *
     * @return Model
     *   $this, for chaining.
     */
    public function initCallback($name)
    {
        $classname = 'Brainworxx\\Krexx\\Analyse\\Callback\\' . $name;
        $this->callback = new $classname($this->storage);
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
     * @return Model
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
     */
    public function setMultiLineCodeGen($multiLineCodeGen)
    {
        $this->multiLineCodeGen = $multiLineCodeGen;
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
     * Setter fro the $isCallback.
     *
     * @param boolean $isCallback
     */
    public function setIsCallback($isCallback)
    {
        $this->isCallback = $isCallback;
    }
}
