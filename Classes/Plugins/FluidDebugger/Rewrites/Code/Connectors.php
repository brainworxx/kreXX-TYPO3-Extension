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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code;

use Brainworxx\Krexx\Analyse\Code\Connectors as OrgConnectors;

/**
 * Special connectors for fluid. Used by the code generation.
 *
 * @package Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code
 */
class Connectors extends OrgConnectors
{

    protected $connectorArray = [
        Connectors::METHOD => ['.', '(@param@)'],
        Connectors::STATIC_METHOD => ['.', '(@param@)'],
        Connectors::NORMAL_ARRAY => ['.', ''],
        Connectors::ASSOCIATIVE_ARRAY => ['.', ''],
        Connectors::CONSTANT => ['.', ''],
        Connectors::NORMAL_PROPERTY => ['.', ''],
        Connectors::STATIC_PROPERTY => ['.', ''],
        Connectors::SPECIAL_CHARS_PROP => ['.', ''],
    ];

    /**
     * {@inheritdoc}
     */
    protected $language = 'fluid';

    /**
     * Do nothing. There is no second connector in fluid.
     *
     * @param integer $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   Return an empty string.
     */
    public function getConnectorRight($cap): string
    {
        // No params, no cookies!
        if (empty($this->params)) {
            return '';
        }

        return parent::getConnectorRight($cap);
    }
}
