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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Debug method result analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse
 *
 * @uses mixed data
 *   The result from one single configured debug method.
 */
class Debug extends AbstractCallback
{

    /**
     * Iterate though the result of the polled debug methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        // This could be anything, we need to route it.
        return $this->dispatchStartEvent() .
            $this->pool->routing->analysisHub(
                $this->dispatchEventWithModel(
                    static::EVENT_MARKER_ANALYSES_END,
                    $this->pool->createClass(Model::class)
                        ->setData($this->parameters[static::PARAM_DATA])
                        ->setName('result')
                )
            );
    }
}
