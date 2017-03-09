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

namespace Brainworxx\Krexx\Service\Code;

/**
 * Generating the connectors for code generation.
 *
 * @package Brainworxx\Krexx\Service\Code
 */
class Connectors
{
    const METHOD = 'method';
    const STATIC_METHOD = 'staticMethod';
    const NORMAL_ARRAY = 'array';
    const ASSOCIATIVE_ARRAY = 'associativeArray';
    const CONSTANT = 'constant';
    const NORMAL_PROPERTY = 'property';
    const STATIC_PROPERTY = 'staticProperty';

    /**
     * Parameters, in case we are connectiong a method or closure.
     *
     * @var string
     */
    protected $params = '';

    /**
     * The type of connectors we are rendering.
     *
     * @see constants above
     *
     * @var string
     */
    protected $type = '';

    /**
     * Special snowflake connector1. will be uses in case it is set.
     *
     * @var string
     */
    protected $customConnector1 = '';

    /**
     * Setter for the $params. It is used in case we are connection a method or
     * closure.
     *
     * @param string $params
     *   The parameters as a sting.
     */
    public function setParameters($params)
    {
        $this->params = $params;
    }

    /**
     * Setter for the type we are rendering, using the class constants.
     *
     * @param string $type
     *   Tee type, @see constants above
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Getting the connector1, according to the type.
     *
     * @return string
     */
    public function getConnector1()
    {
        if (!empty($this->customConnector1)) {
            return $this->customConnector1;
        }

        switch ($this->type) {
            case '':
                return '';
                break;

            case $this::NORMAL_ARRAY:
                return '[';
                break;

            case $this::ASSOCIATIVE_ARRAY:
                return '[\'';
                break;

            case $this::NORMAL_PROPERTY:
                return '->';
                break;

            case $this::METHOD:
                return '->';
                break;

            case $this::STATIC_METHOD:
                return '::';
                break;

            case $this::STATIC_PROPERTY:
                return '::';
                break;

            case $this::CONSTANT:
                return '::';
                break;

            default:
                // Unknown type, return empty string.
                return '';
                break;
        }
    }

    /**
     * Getting the connector1, according to the type.
     *
     * @return string
     */
    public function getConnector2()
    {
        switch ($this->type) {
            case '':
                return '';
                break;

            case $this::NORMAL_ARRAY:
                return ']';
                break;

            case $this::ASSOCIATIVE_ARRAY:
                return '\']';
                break;

            case $this::METHOD:
                return '(' . $this->params . ')';
                break;

            case $this::STATIC_METHOD:
                return '(' . $this->params . ')';
                break;

            default:
                // Unknown type, return empty string.
                return '';
                break;
        }
    }

    /**
     * Sets the special snowflake connector1.
     *
     * @param string $customConnector1
     *   The string we want to set.
     */
    public function setCustomConnector1($customConnector1)
    {
        $this->customConnector1 = $customConnector1;
    }
}
