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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Code;

/**
 * Generating the connectors for code generation.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class Connectors implements ConnectorsConstInterface
{
    /**
     * List of the combinations of connectors.
     *
     * @var array
     */
    protected $connectorArray  = [
        self::CONNECTOR_NOTHING => ['', ''],
        self::CONNECTOR_METHOD => ['->', '()'],
        self::CONNECTOR_STATIC_METHOD => ['::', '()'],
        self::CONNECTOR_NORMAL_ARRAY => ['[', ']'],
        self::CONNECTOR_ASSOCIATIVE_ARRAY => ['[\'', '\']'],
        self::CONNECTOR_CONSTANT => ['::', ''],
        self::CONNECTOR_NORMAL_PROPERTY => ['->', ''],
        self::CONNECTOR_STATIC_PROPERTY => ['::', ''],
        self::CONNECTOR_SPECIAL_CHARS_PROP => ['->{\'', '\'}'],
    ];

    /**
     * The name of the language here. Will be used as the source generation
     * button inside the SmokyGrey skin.
     *
     * @var string
     */
    protected $language = 'php';

    /**
     * Parameters, in case we are connecting a method or closure.
     *
     * @var string
     */
    protected $params;

    /**
     * The type of connectors we are rendering.
     *
     * @see constants above
     *
     * @var int
     */
    protected $type = 0;

    /**
     * Special snowflake connectorLeft. will be uses in case it is set.
     *
     * @var string
     */
    protected $customConnectorLeft;

    /**
     * The return type of a method. Not used for code generation.
     *
     * @var string
     */
    protected $returnType = '';

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
     * Getter for the connection parameters.
     *
     * @return string
     *   The connection parameters.
     */
    public function getParameters(): string
    {
        return $this->params;
    }

    /**
     * Setter for the type we are rendering, using the class constants.
     *
     * @param string $type
     *   The type, @see constants above
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Getting the connectorLeft, according to the type.
     *
     * @return string
     *   The PHP connector, what else?
     */
    public function getConnectorLeft(): string
    {
        if ($this->customConnectorLeft === null) {
            return $this->connectorArray[$this->type][0];
        }

        return $this->customConnectorLeft;
    }

    /**
     * Getting the connectorLeft, according to the type.
     *
     * @param int $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   The PHP connector, what else?
     */
    public function getConnectorRight($cap): string
    {
        if (
            empty($this->params) === true ||
            ($this->type !== static::CONNECTOR_METHOD && $this->type !== static::CONNECTOR_STATIC_METHOD)
        ) {
            return $this->connectorArray[$this->type][1];
        }

        // Copy the parameters, we will need the original ones later.
        // This one is only for the quick preview.
        $parameters = $this->params;
        // Capping the parameters for a better readability.
        if ($cap > 0 && strlen($parameters) > $cap) {
            $parameters = substr($parameters, 0, $cap) . ' . . . ';
        }

        return '(' . $parameters . ')';
    }

    /**
     * Sets the special snowflake connectorLeft.
     *
     * @param string $customConnectorLeft
     *   The string we want to set.
     */
    public function setCustomConnectorLeft($customConnectorLeft)
    {
        $this->customConnectorLeft = $customConnectorLeft;
    }

    /**
     * Getter for the language value (php)
     *
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * Setter for the return type.
     *
     * @param string $returnType
     */
    public function setReturnType(string $returnType)
    {
        $this->returnType = $returnType;
    }

    /**
     * Getter for the return type.
     *
     * @return string
     */
    public function getReturnType(): string
    {
        return $this->returnType;
    }
}
