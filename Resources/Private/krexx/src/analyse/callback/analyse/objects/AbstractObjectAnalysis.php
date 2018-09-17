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

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;

/**
 * Abstract class for the object analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 */
abstract class AbstractObjectAnalysis extends AbstractCallback
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * The parameters from the objects callback class.
     *
     * @var array
     */
    protected $parameters = array();

    /**
     * Gets the properties from a reflection property of the object.
     *
     * @param array $refProps
     *   The list of the reflection properties.
     * @param ReflectionClass $ref
     *   The reflection of the object we are currently analysing.
     * @param string $label
     *   The additional part of the template file.
     *
     * @return string
     *   The generated markup.
     */
    protected function getReflectionPropertiesData(array $refProps, ReflectionClass $ref, $label)
    {
        // We are dumping public properties direct into the main-level, without
        // any "abstraction level", because they can be accessed directly.
        /** @var Model $model */
        $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
            ->addParameter('data', $refProps)
            ->addParameter('ref', $ref)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties')
            );

        if (strpos(strtoupper($label), 'PUBLIC') === false) {
            // Protected or private properties.
            return $this->pool->render->renderExpandableChild(
                $model->setName($label)
                    ->setType('class internals')
            );
        }

        // Public properties.
        // We render them directly in the object "root", so we call
        // the render directly.
        return $this->dispatchEventWithModel('analysisEnd', $model)->renderMe();
    }

    /**
     * Simple sorting callback for reflections.
     *
     * @param \Reflector $reflectionA
     *   The first reflection.
     * @param \Reflector $reflectionB
     *   The second reflection.
     * @return int
     */
    protected function reflectionSorting(\Reflector $reflectionA, \Reflector $reflectionB)
    {
        /** @var \ReflectionMethod | \ReflectionProperty $reflectionA */
        /** @var \ReflectionMethod | \ReflectionProperty $reflectionB */
        return strcmp($reflectionA->getName(), $reflectionB->getName());
    }
}
