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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

/**
 * Constant analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array data
 *   Array of constants from the class we are analysing.
 * @uses string classname
 *   The classname we are analysing, for code generation purpose.
 */
class ThroughConstants extends AbstractCallback
{
    /**
     * Simply iterate though object constants.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        // We do not need to check the recursionHandler, this is class
        // internal stuff. Is it even possible to create a recursion here?
        // Iterate through.
        foreach ($this->parameters['data'] as $k => &$v) {
            $output .= $this->pool->routing->analysisHub(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setData($v)
                    ->setName($k)
                    ->setCustomConnectorLeft($this->parameters['classname'] . '::')
            );
        }

        return $output;
    }
}
