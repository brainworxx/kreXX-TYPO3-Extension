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

namespace Brainworxx\Krexx\Analyse\Code;

/**
 * Generating the connectors for code generation.
 *
 * @package Brainworxx\Krexx\Analyse\Code
 */
class Connectors
{

    const NOTHING = 0;

    /**
     * connector1 = '->'
     * connector2 = '()'
     * or
     * connector2 = '(<small>' . $params . '</small>)'
     */
    const METHOD = 1;

    /**
     * connector1 = '::'
     * connector2 = '()'
     * or
     * connector2 = '(<small>' . $params . '</small>)'
     */
    const STATIC_METHOD = 2;

    /**
     * connector1 = '['
     * connector2 = ']'
     */
    const NORMAL_ARRAY = 3;

    /**
     * connector1 = '[\''
     * connector2 = '\']'
     */
    const ASSOCIATIVE_ARRAY = 4;

    /**
     * connector1 = '::'
     * connector2 = ''
     */
    const CONSTANT = 5;

    /**
     * connector1 = '->'
     * connector2 = ''
     */
    const NORMAL_PROPERTY = 6;

    /**
     * connector1 = '::'
     * connector2 = ''
     */
    const STATIC_PROPERTY = 7;

    /**
     * connector1 = '->{\''
     * connector2 = '\'}'
     */
    const SPECIAL_CHARS_PROP = 8;

    /**
     * List of the combinations of connectors.
     *
     * @var array
     */
    protected $connectorArray = array(
        self::NOTHING => array('', ''),
        self::METHOD => array('->', '(@param@)'),
        self::STATIC_METHOD => array('::', '(@param@)'),
        self::NORMAL_ARRAY => array('[', ']'),
        self::ASSOCIATIVE_ARRAY => array('[\'', '\']'),
        self::CONSTANT => array('::', ''),
        self::NORMAL_PROPERTY => array('->', ''),
        self::STATIC_PROPERTY => array('::', ''),
        self::SPECIAL_CHARS_PROP => array('->{\'', '\'}'),
    );

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
    protected $params = '';

    /**
     * The type of connectors we are rendering.
     *
     * @see constants above
     *
     * @var integer
     */
    protected $type = 0;

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
     * Getter for the connection parameters.
     *
     * @return string
     *   The connection parameters.
     */
    public function getParameters()
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
     * Getting the connector1, according to the type.
     *
     * @return string
     *   The PHP connector, what else?
     */
    public function getConnector1()
    {
        if (empty($this->customConnector1)) {
            return $this->connectorArray[$this->type][0];
        }

        return $this->customConnector1;

    }

    /**
     * Getting the connector1, according to the type.
     *
     * @param integer $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   The PHP connector, what else?
     */
    public function getConnector2($cap)
    {
        // Methods always have their parameters.
        if ($this->type === self::METHOD || $this->type === self::STATIC_METHOD) {
            if (!empty($this->params)) {
                // Copy the parameters, we will need the original ones later.
                // This one is only for the quick preview.
                $params = $this->params;
                // Capping the parameters for a better readability.
                if ($cap > 0 && strlen($params) > $cap) {
                    $params = substr($params, 0, $cap) . ' . . . ';
                }
                // We wrap them in a <small>, but only if we have any.
                $params = '<small>' . $params . '</small>';
            } else {
                $params = '';
            }
            return  str_replace('@param@', $params, $this->connectorArray[$this->type][1]);
        }

        return $this->connectorArray[$this->type][1];
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

    /**
     * Getter for the language value (php)
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }
}
