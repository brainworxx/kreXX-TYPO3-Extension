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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Declaration;

use ReflectionClass;
use ReflectionProperty;
use Reflector;

class PropertyDeclaration extends AbstractDeclaration
{
    /**
     * Retrieve the declaration pace of a property.
     *
     * @param \ReflectionProperty $reflection
     *   A reflection of the property we ara analysing.
     *
     * @return string
     */
    public function retrieveDeclaration(Reflector $reflection): string
    {
        $messages = $this->pool->messages;
        // Early returns for simple cases.
        if (isset($reflection->isUndeclared)) {
            return $messages->getHelp('metaUndeclared');
        }

        $reflectionClass = $reflection->getDeclaringClass();
        if ($reflectionClass->isInternal()) {
            return $messages->getHelp('metaPredeclared');
        }

        $traits = $reflectionClass->getTraits();
        if (!empty($traits)) {
            // Update the declaring class reflection from the traits.
            $reflectionClass = $this->retrieveDeclaringClassFromTraits($traits, $reflection, $reflectionClass);
        }
        $result = '';
        if ($reflectionClass !== null) {
            $result = $reflectionClass->getFileName() .
                $this->pool->render->renderLinebreak() .
                ($reflectionClass->isTrait() ? $messages->getHelp('metaInTrait') : $messages->getHelp('metaInClass')) .
                $reflectionClass->name;
        }

        return $result;
    }

    /**
     * Retrieve the named property type, if possible.
     *
     * @param \ReflectionProperty $refProperty
     * @return string
     */
    public function retrieveNamedPropertyType(ReflectionProperty $refProperty): string
    {
        if (method_exists($refProperty, 'hasType') && $refProperty->hasType()) {
            return trim($this->retrieveNamedType($refProperty->getType()));
        }

        return '';
    }

    /**
     * Retrieve the declaration name from traits.
     *
     * A class can not redeclare a property from a trait that it is using.
     * Hence, if one of the traits has the same property that we are
     * analysing, it is probably declared there.
     * Traits on the other hand can redeclare their properties.
     * I'm not sure how to get the actual declaration place, when dealing
     * with several layers of traits. We will not parse the source code
     * for an answer.
     *
     * @param \ReflectionClass[] $traits
     *   The traits of that class.
     * @param \ReflectionProperty $refProperty
     *   Reflection of the property we are analysing.
     * @param \ReflectionClass $originalRef
     *   The original reflection class for the declaration.
     *
     * @return \ReflectionClass|null
     *   Either the reflection class of the trait, or null when we are unable to
     *   retrieve it.
     */
    protected function retrieveDeclaringClassFromTraits(
        array $traits,
        ReflectionProperty $refProperty,
        ReflectionClass $originalRef
    ): ?ReflectionClass {
        $propertyName = $refProperty->name;

        foreach ($traits as $trait) {
            if ($trait->hasProperty($propertyName)) {
                if (count($trait->getTraitNames()) > 0) {
                    // Multiple layers of traits!
                    return null;
                }
                // From a trait.
                return $trait;
            }
        }

        // Return the original reflection class.
        return $originalRef;
    }
}
