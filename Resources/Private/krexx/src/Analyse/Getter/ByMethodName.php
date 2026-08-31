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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Getter;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class ByMethodName extends AbstractGetter
{
    /**
     * Have we found anything?
     *
     * @var bool
     */
    protected bool $foundSomething = false;

    /**
     * Reflection of the property that we are looking at, if available.
     *
     * @var \ReflectionProperty|null
     */
    protected ?ReflectionProperty $reflectionProperty = null;

    /**
     * {@inheritDoc}
     */
    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): mixed {
        $this->foundSomething = false;
        $this->reflectionProperty = null;
        $reflectionProperty = $this->retrieveReflectionProperty(
            reflectionMethod: $reflectionMethod,
            reflectionClass: $reflectionClass,
            currentPrefix: $currentPrefix
        );
        if (!$reflectionProperty instanceof \ReflectionProperty) {
            // Nothing was found.
            return null;
        }

        $this->foundSomething = true;
        $this->reflectionProperty = $reflectionProperty;
        return $this->prepareResult(
            refProp: $reflectionProperty,
            reflectionClass: $reflectionClass,
            currentPrefix: $currentPrefix
        );
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * We try to guess the corresponding property in the class.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function retrieveReflectionProperty(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): ?ReflectionProperty {
        // We may be facing different writing styles.
        // The property we want from getMyProperty() should be named myProperty,
        // but we can not rely on this.
        // Old php 4 coders sometimes add an underscore before a protected
        // property.

        // We will check:
        $names = [
            // myProperty
            $propertyName = $this->preparePropertyName(
                reflectionMethod: $reflectionMethod,
                currentPrefix: $currentPrefix
            ),
            // _myProperty
            '_' . $propertyName,
            // MyProperty
            ucfirst(string: $propertyName),
            // _MyProperty
            '_' . ucfirst(string: $propertyName),
            // myproperty
            strtolower(string: $propertyName),
            // _myproperty
            '_' . strtolower(string: $propertyName),
            // my_property
            $this->convertToSnakeCase(string: $propertyName),
            // _my_property
            '_' . $this->convertToSnakeCase(string: $propertyName)
        ];

        foreach ($names as $name) {
            if ($reflectionClass->hasProperty(name: $name)) {
                return $reflectionClass->getProperty(name: $name);
            }
        }

        // Nothing found.
        return null;
    }

    /**
     * Prepare the retrieved result for output.
     *
     * @param \ReflectionProperty $refProp
     *   The reflection of the property that it may return.
     * @param ReflectionClass $reflectionClass
     *   The reflection class, for the retrieval og the value.
     * @param string $currentPrefix
     *   The current prefix.
     *
     * @return mixed
     *   The value we retrieved.
     */
    protected function prepareResult(
        ReflectionProperty $refProp,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): mixed {
        // We've got ourselves a possible result.
        $value = $reflectionClass->retrieveValue(refProperty: $refProp);
        // If we are handling a getter, we retrieve the value itself
        // If we are handling an is'er of has'er, we return a boolean.
        if ($currentPrefix !== 'get' && !is_bool(value: $value)) {
            return $value !== null;
        }

        return $value;
    }

    /**
     * Get a first impression ot the possible property name for the getter.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   A reflection of the getter method we are analysing.
     *
     * @return string
     *   The first impression of the property name.
     */
    protected function preparePropertyName(ReflectionMethod $reflectionMethod, string $currentPrefix): string
    {
         // Get the name and remove the 'get' . . .
        $getterName = $reflectionMethod->getName();
        if (str_starts_with(haystack: $getterName, needle: $currentPrefix)) {
            return lcfirst(string: substr(string: $getterName, offset: strlen(string: $currentPrefix)));
        }

        // . . .  or the '_get'.
        if (str_starts_with(haystack: $getterName, needle: '_' . $currentPrefix)) {
            return lcfirst(string: substr(string: $getterName, offset: strlen(string: $currentPrefix) + 1));
        }

        // Still here?!? At least make the first letter lowercase.
        return lcfirst(string: $getterName);
    }

    /**
     * Converts a camel case string to snake case.
     *
     * @author Syone
     * @see https://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case/35719689#35719689
     *
     * @param string $string
     *   The string we want to transform into snake case
     *
     * @return string
     *   The de-camelized string.
     */
    protected function convertToSnakeCase(string $string): string
    {
        return strtolower(string: (string) preg_replace(
            pattern: ['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'],
            replacement: '$1_$2',
            subject: $string
        ));
    }

    public function __construct(protected Pool $pool)
    {
    }
}
