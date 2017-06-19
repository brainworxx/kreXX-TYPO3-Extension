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

use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Special connectors for fluid. Used by the code generation.
 */
class Tx_Includekrexx_Rewrite_ServiceCodeConnectors extends Connectors
{

    /**
     * Diffeerent connectors for fluid.
     *
     * {@inheritdoc}
     */
    protected $connctorArray = array(
        self::NOTHING => array('', ''),
        self::METHOD => array('.', '(@param@)'),
        self::STATIC_METHOD => array('.', '(@param@)'),
        self::NORMAL_ARRAY => array('.', ''),
        self::ASSOCIATIVE_ARRAY => array('.', ''),
        self::CONSTANT => array('.', ''),
        self::NORMAL_PROPERTY => array('.', ''),
        self::STATIC_PROPERTY => array('.', ''),
        // We do not support 'special' chars in function names.
        self::SPECIAL_CHARS_PROP => array('.', ''),
    );

    /**
     * {@inheritdoc}
     */
    protected $language = 'fluid';

    /**
     * Do nothing. There is no secornd connector in fluid.
     *
     * @param integer $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   Return an empty string.
     */
    public function getConnector2($cap)
    {
        // No params, no cookies!
        if (empty($this->params)) {
            return '';
        }

        return parent::getConnector2($cap);
    }
}