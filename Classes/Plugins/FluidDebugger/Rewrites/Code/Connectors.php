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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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
 */
class Connectors extends OrgConnectors
{
    /**
     * Update the connectors array.
     */
    public function __construct()
    {
        $fluid = ['.', ''];
        $fluidParameters = ['.', '(@param@)'];
        $this->connectorArray[static::CONNECTOR_METHOD] = $fluidParameters;
        $this->connectorArray[static::CONNECTOR_STATIC_METHOD] = $fluidParameters;
        $this->connectorArray[static::CONNECTOR_NORMAL_ARRAY] = $fluid;
        $this->connectorArray[static::CONNECTOR_ASSOCIATIVE_ARRAY] = $fluid;
        $this->connectorArray[static::CONNECTOR_CONSTANT] = $fluid;
        $this->connectorArray[static::CONNECTOR_NORMAL_PROPERTY] = $fluid;
        $this->connectorArray[static::CONNECTOR_STATIC_PROPERTY] = $fluid;
        $this->connectorArray[static::CONNECTOR_SPECIAL_CHARS_PROP] = $fluid;
    }

    /**
     * {@inheritdoc}
     */
    protected $language = 'fluid';

    /**
     * Do nothing. There is no second connector in fluid.
     *
     * @param int $cap
     *   Maximum length of all parameters. 0 means no cap.
     *
     * @return string
     *   Return an empty string.
     */
    public function getConnectorRight(int $cap): string
    {
        // No params, no cookies!
        if (empty($this->params)) {
            return '';
        }

        return parent::getConnectorRight($cap);
    }
}
