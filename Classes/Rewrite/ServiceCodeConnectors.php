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

use Brainworxx\Krexx\Service\Code\Connectors;

class Tx_Includekrexx_Rewrite_ServiceCodeConnectors extends Connectors
{

    /**
     * {@inheritdoc}
     */
    protected $language = 'fluid';

    /**
     * Everything is an array. Return the fluid point.
     *
     * @return string
     *  The fluid connector.
     */
    public function getConnector1()
    {
        switch ($this->type) {
            case '':
                return '';
                break;

            case $this::NORMAL_ARRAY:
                return '.';
                break;

            case $this::ASSOCIATIVE_ARRAY:
                return '.';
                break;

            case $this::NORMAL_PROPERTY:
                return '.';
                break;

            case $this::METHOD:
                return '.';
                break;

            case $this::STATIC_METHOD:
                return '.';
                break;

            case $this::STATIC_PROPERTY:
                return '.';
                break;

            case $this::CONSTANT:
                return '.';
                break;

            default:
                // Unknown type, return empty string.
                return '';
                break;
        }
    }

    /**
     * Do nothing. There is no secornd connector in fluid.
     *
     * @return string
     *   Return an empty string.
     */
    public function getConnector2()
    {
        // No params, no cookies!
        if (empty($this->params)) {
            return '';
        }

        switch ($this->type) {
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
}