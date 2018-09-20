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
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Array analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array data
 *   The array want to iterate.
 * @uses boolean multiline
 *   Do we need a multiline code generation?
 */
class ThroughArray extends AbstractCallback
{
    /**
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray';

    /**
     * Renders the expendable around the array analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->pool->render->renderSingeChildHr() .
            $this->dispatchStartEvent();

        $recursionMarker = $this->pool->recursionHandler->getMarker();
        // Iterate through.
        foreach ($this->parameters['data'] as $key => &$value) {
            // We will not output our recursion marker.
            // Meh, the only reason for the recursion marker
            // in arrays is because of the $GLOBAL array, which
            // we will only render once.
            if ($key === $recursionMarker) {
                continue;
            }

            /** @var Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model');

            // Are we dealing with multiline code generation?
            if ($this->parameters['multiline'] === true) {
                // Here we tell the Codegen service that we need some
                // special handling.
                $model->setMultiLineCodeGen(Codegen::ITERATOR_TO_ARRAY);
            }

            if (is_string($key) === true) {
                $model->setData($value)
                    ->setName($this->pool->encodingService->encodeString($key))
                    ->setConnectorType(Connectors::ASSOCIATIVE_ARRAY);
            } else {
                $model->setData($value)
                    ->setName($key)
                    ->setConnectorType(Connectors::NORMAL_ARRAY);
            }

            $output .= $this->pool->routing->analysisHub($model);
        }

        return $output . $this->pool->render->renderSingeChildHr();
    }
}
