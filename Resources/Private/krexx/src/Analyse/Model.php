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

/**
 * Model for the view rendering
 *
 * @package Brainworxx\Krexx\Analyse
 */
class Model extends AbstractModel
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
     * A unique ID for the dom. We use this one for recursion resolving via JS.
     *
     * @var string
     */
    protected $domid = '';

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
     * @var int
     */
    protected $multiLineCodeGen = 0;

    /**
     * Defines if the content of the variable qualifies as a callback.
     *
     * @var bool
     */
    protected $isCallback = false;

    /**
     * We need to know, if we are rendering the expandable child for the
     * constants. The code generation does special stuff there.
     *
     * @var bool
     */
    protected $isMetaConstants = false;

    /**
     * Is this a public property or method?
     *
     * @var bool
     */
    protected $isPublic = true;

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
     * Getter got connectorLeft.
     *
     * @return string
     *   The first connector.
     */
    public function getConnectorLeft()
    {
        return $this->connectorService->getConnectorLeft();
    }

    /**
     * Getter for connectorRight.
     *
     * @param integer $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   The second connector.
     */
    public function getConnectorRight($cap = 0)
    {
        return $this->connectorService->getConnectorRight($cap);
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
     * Getter for the hasExtra property.
     *
     * @return bool
     *   Info for the render class, if we need to render the extras part.
     */
    public function getHasExtra()
    {
        return $this->hasExtra;
    }

    /**
     * "Setter" for the hasExtra property.
     *
     * @param boolean $value
     *   The value we want to set.
     *
     * @return $this
     *   $this, for chaining.
     */
    public function setHasExtra($value)
    {
        $this->hasExtra = $value;
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
     * @param integer $multiLineCodeGen
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
     *   The value we want to set.
     *
     * @return $this
     *   $this for chaining.
     */
    public function setIsCallback($isCallback)
    {
        $this->isCallback = $isCallback;

        return $this;
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
     * Sets a special and custom connectorLeft. Only used for constants code
     * generation.
     *
     * @param string $string
     *
     * @return $this
     *   Return $this for chaining.
     */
    public function setCustomConnectorLeft($string)
    {
        $this->connectorService->setCustomConnectorLeft($string);
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

    /**
     * Getter for the isMetaConstants.
     *
     * @return bool
     *   True means that we are currently rendering the expandable child for
     *   the constants.
     */
    public function getIsMetaConstants()
    {
        return $this->isMetaConstants;
    }

    /**
     * Setter for the isMetaConstants.
     *
     * @param boolean $bool
     *   The value we want to set.
     * @return $this
     *   Return $this for chaining.
     */
    public function setIsMetaConstants($bool)
    {
        $this->isMetaConstants = $bool;
        return $this;
    }

    /**
     * Setter for the isProtectedPrivate.
     *
     * @param $bool
     *   The value we want to set.
     * @return $this
     *   Return $this for chaining.
     */
    public function setIsPublic($bool)
    {
        $this->isPublic = $bool;
        return $this;
    }

    /**
     * Getter for the isPublic.
     *
     * @return bool
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }
}
