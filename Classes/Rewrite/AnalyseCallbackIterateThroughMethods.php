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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Code\ReflectionParameterWrapper;

class Tx_Includekrexx_Rewrite_AnalyseCallbackIterateThroughMethods extends ThroughMethods
{

    /**
     * Simply start to iterate through the methods.
     *
     * Change: We set the multiline code generation to VHS.
     * Change: We add the name of the parameter fore the VHS code generation
     *         into the 'paramArray'
     *
     * @return string
     *   The rendered markup.
     */
    public function callMe()
    {
        $result = '';
        /** @var \ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters['ref'];

        $commentAnalysis = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Methods');

        // Deep analysis of the methods.
        /* @var \ReflectionMethod $reflectionMethod */
        foreach ($this->parameters['data'] as $reflectionMethod) {
            $methodData = array();

            // Get the comment from the class, it's parents, interfaces or traits.
            $methodComment = $commentAnalysis->getComment($reflectionMethod, $reflectionClass);
            if (!empty($methodComment)) {
                $methodData['comments'] = $methodComment;
            }

            // Get declaration place.
            $declaringClass = $reflectionMethod->getDeclaringClass();
            $methodData['declared in'] = $this->getDeclarationPlace($reflectionMethod, $declaringClass);

            // Get parameters.
            $paramList = '';
            $paramArray = array();
            foreach ($reflectionMethod->getParameters() as $key => $reflectionParameter) {
                ++$key;
                /** @var ReflectionParameterWrapper $reflectionParameterWrapper */
                $reflectionParameterWrapper = $this->pool
                    ->createClass('Brainworxx\\Krexx\\Analyse\\Code\\ReflectionParameterWrapper')
                    ->setReflectionParameter($reflectionParameter);

                $methodData['Parameter #' . $key] = $reflectionParameterWrapper->toString();
                $paramList .= $reflectionParameterWrapper->toString() . ', ';
                $paramArray[] = $reflectionParameter->getName();
            }

            // Remove the ',' after the last char.
            $paramList = trim($paramList, ', ');

            // Get declaring keywords.
            $methodData['declaration keywords'] = $this->getDeclarationKeywords(
                $reflectionMethod,
                $declaringClass,
                $reflectionClass
            );

            // Get the connector.
            if ($reflectionMethod->isStatic()) {
                $connectorType = Connectors::STATIC_METHOD;
            } else {
                $connectorType = Connectors::METHOD;
            }

            // Render it!
            $result .= $this->pool->render->renderExpandableChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setName($reflectionMethod->name)
                    ->setType($methodData['declaration keywords'] . ' method')
                    ->setConnectorType($connectorType)
                    ->setConnectorParameters($paramList)
                    ->addParameter('data', $methodData)
                    ->addParameter('paramArray', $paramArray)
                    ->setMultiLineCodeGen(\Tx_Includekrexx_Rewrite_ServiceCodeCodegen::VHS_CALL_VIEWHELPER)
                    ->injectCallback(
                        $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis')
                    )
            );
        }
        return $result;
    }
}
