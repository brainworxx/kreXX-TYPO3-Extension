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

namespace Brainworxx\Krexx\Analyse\Getter;

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Scanning the source code by regex for a possible object that may deliver the
 * value that the getter returns.
 */
class ByRegExDelegate extends ByRegExContainer
{
    /**
     * We try to parse something like:
     * return $this->object->getSomething();
     *
     * The expectation is this:
     * object->getSomething
     *
     * @var array|string[]
     */
    protected array $firstPattern = ['return $this->', '();'];

    /**
     * We split the result from the firstPattern by this one.
     *
     * container['value'
     * will split into
     * object and getSomething
     *
     * @var string
     */
    protected string $secondPattern = '->';

    /**
     * @var \Brainworxx\Krexx\Analyse\Getter\AbstractGetter[]
     */
    protected array $getterAnalyser;

    /**
     * The current prefix
     *
     * @var string
     */
    protected string $currentPrefix;

    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->getterAnalyser[] = $this->pool->createClass(ByMethodName::class);
        $this->getterAnalyser[] = $this->pool->createClass(ByRegExProperty::class);
        $this->getterAnalyser[] = $this->pool->createClass(ByRegExContainer::class);
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ) {
        $this->currentPrefix = $currentPrefix;
        return parent::retrieveIt($reflectionMethod, $reflectionClass, $currentPrefix);
    }

    /**
     * We are parsing the result a little bit different, since we are looking
     * for a delegate object, and not a ReflectionProperty.
     *
     * We are looking for something like this:
     * return $this->myObject->getStuff();
     *
     * {@inheritDoc}
     */
    protected function extractValue(array $parts, ReflectionClass $reflectionClass)
    {
        try {
            $delegateReflection = $this->retrieveReflectionClass($parts, $reflectionClass);
            if ($delegateReflection === null) {
                return null;
            }

            // Now, let's ask the others.
            $reflectionMethod = $delegateReflection->getMethod($parts[1]);
            foreach ($this->getterAnalyser as $analyser) {
                $value = $analyser->retrieveIt($reflectionMethod, $delegateReflection, $this->currentPrefix);
                if ($analyser->hasResult()) {
                    $this->foundSomething = true;
                    return $value;
                }
            }
        } catch (ReflectionException $exception) {
        }

        return null;
    }

    /**
     * Retrieve the reflection of the object that is getting called.
     *
     * @param array $parts
     *   The parts from the regex scanner.
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass
     *   The reflection of the class that we are analysing
     *
     * @throws \ReflectionException
     *
     * @return \Brainworxx\Krexx\Service\Reflection\ReflectionClass|null
     *   Reflection of the class that is getting called inside the class that we
     *   are analysing.
     */
    protected function retrieveReflectionClass(
        array $parts,
        ReflectionClass $reflectionClass
    ): ?ReflectionClass {
        // The propertyName now may look like this:
        // myObject->getStuff()
        if (
            count($parts) !== 2
            || !$reflectionClass->hasProperty($parts[0])
        ) {
            // This is not the code I am looking for.
            return null;
        }

        $object = $reflectionClass->retrieveValue($reflectionClass->getProperty($parts[0]));
        if (!is_object($object)) {
            return null;
        }

        $delegateReflection = new ReflectionClass($object);
        if (!$delegateReflection->hasMethod($parts[1])) {
            return null;
        }

        return $delegateReflection;
    }
}
