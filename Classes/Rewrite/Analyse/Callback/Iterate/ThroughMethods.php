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

namespace Brainworxx\Includekrexx\Rewrite\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods as OrgThroughMethods;
use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Fluid methods need some special handling.
 *
 * @package Brainworxx\Includekrexx\Rewrite\Analyse\Callback\Iterate
 */
class ThroughMethods extends OrgThroughMethods
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
                $paramList .= $methodData['Parameter #' . $key] = $this->pool
                    ->codegenHandler
                    ->parameterToString($reflectionParameter);
                $paramArray[] = $reflectionParameter->getName();                // xx
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
                    ->addParameter('paramArray', $paramArray)   // xx
                    ->setMultiLineCodeGen(\Brainworxx\Includekrexx\Rewrite\Service\Code\Codegen::VHS_CALL_VIEWHELPER) // xx
                    ->injectCallback(
                        $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis')
                    )
            );
        }
        return $result;
    }
}
