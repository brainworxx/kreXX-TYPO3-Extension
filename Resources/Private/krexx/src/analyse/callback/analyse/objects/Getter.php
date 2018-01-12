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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

/**
 * Analysis of all getter methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses mixed data
 *   The class we are currently analsysing.
 * @uses \ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class Getter extends AbstractObjectAnalysis
{
    /**
     * Dump the possible result of all getter methods
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $data = $this->parameters['data'];
        /** @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];
        // Get all public methods.
        $methodList = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        if ($this->pool->scope->isInScope() === true) {
            // Looks like we also need the protected and private methods.
            $methodList = array_merge(
                $methodList,
                $ref->getMethods(\ReflectionMethod::IS_PRIVATE | \ReflectionMethod::IS_PROTECTED)
            );
        }

        if (empty($methodList) === true) {
            // There are no getter methods in here.
            return '';
        }

        // Filter them.
        /** @var \ReflectionMethod $method */
        foreach ($methodList as $key => $method) {
            if (strpos($method->getName(), 'get') === 0) {
                // We only dump those that have no parameters.
                /** @var \ReflectionMethod $method */
                $parameters = $method->getParameters();
                if (!empty($parameters)) {
                    unset($methodList[$key]);
                }
            } else {
                unset($methodList[$key]);
            }
        }

        if (empty($methodList) === true) {
            // There are no getter methods in here.
            return '';
        }

        // Got some getters right here.
        // We need to set at least one connector here to activate
        // code generation, even if it is a space.
        return $this->pool->render->renderExpandableChild(
            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName('Getter')
                ->setType('class internals')
                ->setHelpid('getterHelpInfo')
                ->addParameter('ref', $ref)
                ->addParameter('methodList', $methodList)
                ->addParameter('data', $data)
                ->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter')
                )
        );
    }
}
