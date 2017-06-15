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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Going through an array with 2000 objects can create more than 1GB of
 * Output. Afaik, there is no browser that can actually display this kind
 * of garbage. Our soluition is simple:
 * We only display the name and the type of the object. Everything else
 * will be omitted.
 * We also do not use recursion handling, because assigning 2000 recursions
 * to the frontend would slow donw the brower considerately. Also, the info
 * we are providing here should be as huge as the info aout a recursion,
 * measured in MB.
 *
 * @uses array data
 *   The array want to iterate.
 * @uses bool multiline
 *   Do we need a multiline code generation?
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 */
class ThroughLargeArray extends AbstractCallback
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
        $recursionMarker = $this->pool->recursionHandler->getMarker();
        $output .= $this->pool->render->renderSingeChildHr();

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
            if ($this->parameters['multiline']) {
                // Here we tell the Codegen service that we need some
                // special handling.
                $model->setMultiLineCodeGen(Codegen::ITERATOR_TO_ARRAY);
            }

            // Handling string keys of the array.
            if (is_string($key)) {
                $model->setName($this->pool->encodingService->encodeString($key))
                    ->setConnectorType(Connectors::ASSOCIATIVE_ARRAY);
            } else {
                $model->setName($key)
                    ->setConnectorType(Connectors::NORMAL_ARRAY);
            }

            if (is_object($value)) {
                // We will not go too deep here, and say only what it is.
                $model->setType('simplified class analysis')
                    ->setNormal(get_class($value));

                $output .= $this->pool->render->renderSingleChild($model);
            } elseif (is_array($value)) {
                // Adding another array to the output may be as bad as a
                // complete object analysis.
                $model->setType('simplified array analysis')
                    ->setNormal('count: ' . count($value));

                $output .= $this->pool->render->renderSingleChild($model);
            } else {
                // We handle the simple type normally with the analysis hub.
                $output .= $this->pool->routing->analysisHub($model->setData($value));
            }
        }
        $output .= $this->pool->render->renderSingeChildHr();

        return $output;
    }
}
