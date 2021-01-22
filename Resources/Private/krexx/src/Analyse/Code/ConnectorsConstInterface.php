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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

interface ConnectorsConstInterface
{
    /**
     * connectorLeft = ''
     * connectorRight = ''
     * or
     * connectorRight = $params
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NOTHING = 0;

    /**
     * connectorLeft = '->'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const METHOD = 1;

    /**
     * connectorLeft = '::'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const STATIC_METHOD = 2;

    /**
     * connectorLeft = '['
     * connectorRight = ']'
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NORMAL_ARRAY = 3;

    /**
     * connectorLeft = '[\''
     * connectorRight = '\']'
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const ASSOCIATIVE_ARRAY = 4;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const CONSTANT = 5;

    /**
     * connectorLeft = '->'
     * connectorRight = ''
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const NORMAL_PROPERTY = 6;

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const STATIC_PROPERTY = 7;

    /**
     * connectorLeft = '->{\''
     * connectorRight = '\'}'
     *
     * @var int
     *
     * @deprecated
     *   Since 4.0.0. Use the prefixed constants.
     */
    const SPECIAL_CHARS_PROP = 8;

    /**
     * connectorLeft = ''
     * connectorRight = ''
     * or
     * connectorRight = $params
     *
     * @var string
     */
    const CONNECTOR_NOTHING = 'NOTHING';

    /**
     * connectorLeft = '->'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @var string
     */
    const CONNECTOR_METHOD = 'METHOD';

    /**
     * connectorLeft = '::'
     * connectorRight = '()'
     * or
     * connectorRight = '(' . $params . ')'
     *
     * @var string
     */
    const CONNECTOR_STATIC_METHOD = 'STATIC_METHOD';

    /**
     * connectorLeft = '['
     * connectorRight = ']'
     *
     * @var string
     */
    const CONNECTOR_NORMAL_ARRAY = 'NORMAL_ARRAY';

    /**
     * connectorLeft = '[\''
     * connectorRight = '\']'
     *
     * @var string
     */
    const CONNECTOR_ASSOCIATIVE_ARRAY = 'ASSOCIATIVE_ARRAY';

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @var string
     */
    const CONNECTOR_CONSTANT = 'CONSTANT';

    /**
     * connectorLeft = '->'
     * connectorRight = ''
     *
     * @var string
     */
    const CONNECTOR_NORMAL_PROPERTY = 'NORMAL_PROPERTY';

    /**
     * connectorLeft = '::'
     * connectorRight = ''
     *
     * @var string
     */
    const CONNECTOR_STATIC_PROPERTY = 'STATIC_PROPERTY';

    /**
     * connectorLeft = '->{\''
     * connectorRight = '\'}'
     *
     * @var string
     */
    const CONNECTOR_SPECIAL_CHARS_PROP = 'SPECIAL_CHARS_PROP';
}
