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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\AimeosDebugger\EventHandlers;

use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Includekrexx\Plugins\AimeosDebugger\ConstInterface as AimeosConstInterface;
use ReflectionException;
use ReflectionClass;

abstract class AbstractEventHandler implements EventHandlerInterface, AimeosConstInterface
{
    /**
     * Retrieve a private or protected property byx using a reflection.
     *
     * @param \ReflectionClass $reflectionClass
     *   Reflection of the class with the property.
     * @param string $objectName
     *   Name of the property.
     * @param object $object
     *   The actual object with the property.
     *
     * @return mixed
     *   The property, if successful, or NULL if not successful.
     */
    protected function retrieveProperty(ReflectionClass $reflectionClass, string $objectName, $object)
    {
        try {
            if ($reflectionClass->hasProperty($objectName)) {
                $propertyRef = $reflectionClass->getProperty($objectName);
                $propertyRef->setAccessible(true);
                return $propertyRef->getValue($object);
            }
        } catch (ReflectionException $e) {
            // Do nothing.
        }

        // Unable to retrieve the value.
        return null;
    }
}
