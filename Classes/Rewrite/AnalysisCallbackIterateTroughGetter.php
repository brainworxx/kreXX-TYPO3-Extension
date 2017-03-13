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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Code\Connectors;

/**
 * Analysing the getter methods, without the actual 'get' word in the method name.
 */
class Tx_Includekrexx_Rewrite_AnalysisCallbackIterateTroughGetter extends ThroughGetter
{
    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        /** @var \reflectionClass $ref */
        $ref = $this->parameters['ref'];

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($this->parameters['methodList'] as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($this
                ->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Methods')
                ->getComment($reflectionMethod, $ref));

            // Remove the 'get' from the name
            $getterName = lcfirst(substr($reflectionMethod->getName(), 3));

            /** @var Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($getterName)
                ->addToJson('method comment', $comments);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic()) {
                $model->setConnectorType(Connectors::STATIC_METHOD);
            } else {
                $model->setConnectorType(Connectors::METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue($reflectionMethod, $model);
        }

        return $output;
    }
}