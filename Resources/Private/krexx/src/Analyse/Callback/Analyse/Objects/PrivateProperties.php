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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use ReflectionProperty;

/**
 * Analysis of private properties.
 *
 * @uses array data
 *   The class we are currently analysing.
 * @uses string name
 *   The name of the object we are analysing
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection of the class we are currently analysing.
 */
class PrivateProperties extends AbstractObjectAnalysis
{
    /**
     * Dumping all private properties.
     *
     * @return string
     *   The generated HTML markup
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        $refProps = [];
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref */
        $ref = $this->parameters[static::PARAM_REF];
        // We need to keep the original reference intact.
        $reflectionClass = $ref;

        // The main problem here is, that you only get the private properties of
        // the current class, but not the inherited private properties.
        // We need to get all parent classes and then poll them for private
        // properties to get the whole picture.
        do {
            $refProps = [...$refProps, ...$reflectionClass->getProperties(ReflectionProperty::IS_PRIVATE)];
            // And now for the parent class.
            $reflectionClass = $reflectionClass->getParentClass();
        } while (is_object($reflectionClass));

        if (empty($refProps)) {
            return $output;
        }

        usort($refProps, [$this, static::REFLECTION_SORTING]);

        return $output .
            $this->getReflectionPropertiesData(
                $refProps,
                $ref,
                $this->pool->messages->getHelp('privateProperties')
            );
    }
}
