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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Model\Callback\Iterate;

use Brainworxx\Krexx\Model\Callback\AbstractCallback;
use Brainworxx\Krexx\Model\Simple;

/**
 * Array analysis methods.
 *
 * @package Brainworxx\Krexx\Model\Callback\Iterate
 *
 * @uses array data
 *   The array want to iterate.
 */
class ThroughArray extends AbstractCallback
{
    /**
     * Renders the expendable around the array analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        $recursionMarker = $this->storage->recursionHandler->getMarker();
        $output .= $this->storage->render->renderSingeChildHr();

        // Iterate through.
        foreach ($this->parameters['data'] as $key => &$value) {
            // We will not output our recursion marker.
            // Meh, the only reason for the recursion marker
            // in arrays is because of the $GLOBAL array, which
            // we will only render once.
            if ($key === $recursionMarker) {
                continue;
            }
            if (is_string($key)) {
                $key = $this->storage->encodeString($key);
            }
            $model = new Simple($this->storage);
            if (is_string($key)) {
                $model->setData($value)
                    ->setName($key)
                    ->setConnector1('[\'')
                    ->setConnector2('\']');
            } else {
                $model->setData($value)
                    ->setName($key)
                    ->setConnector1('[')
                    ->setConnector2(']');
            }

            $output .= $this->storage->routing->analysisHub($model);
        }
        $output .= $this->storage->render->renderSingeChildHr();

        return $output;
    }
}
