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
     * @var AbstractGetter[]
     */
    protected array $getterAnalyser;

    /**
     * The current prefix
     *
     * @var string
     */
    protected string $currentPrefix;

    /**
     * We are going deep into the object structure.
     *
     * @var int
     */
    protected int $deep = 0;

    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
        $this->getterAnalyser[] = $this->pool->createClass(classname: ByMethodName::class);
        $this->getterAnalyser[] = $this->pool->createClass(classname: ByRegExProperty::class);
        $this->getterAnalyser[] = $this->pool->createClass(classname: ByRegExContainer::class);
        $this->getterAnalyser[] = $this;
    }

    /**
     * {@inheritDoc}
     */
    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): mixed {
        $this->currentPrefix = $currentPrefix;
        return parent::retrieveIt(
            reflectionMethod: $reflectionMethod,
            reflectionClass: $reflectionClass,
            currentPrefix: $currentPrefix
        );
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
    protected function extractValue(array $parts, ReflectionClass $reflectionClass): mixed
    {
        ++$this->deep;
        if ($this->deep > 10) {
            // We do not want to go too deep, since this may lead to an infinite loop.
            return null;
        }

        // The call may look like this:
        // $this->myObject?->getStuff();
        // We need to remove the question mark, since it is not part of the
        // object name.
        $parts[0] = trim(string: (string) $parts[0], characters: '?');
        try {
            $delegateReflection = $this->retrieveReflectionClass(parts: $parts, reflectionClass: $reflectionClass);
            if (!$delegateReflection instanceof ReflectionClass) {
                $this->deep = 0;
                return null;
            }

            // Now, let's ask the others.
            $reflectionMethod = $delegateReflection->getMethod(name: $parts[1]);
            foreach ($this->getterAnalyser as $analyser) {
                $value = $analyser->retrieveIt(
                    reflectionMethod: $reflectionMethod,
                    reflectionClass: $delegateReflection,
                    currentPrefix: $this->currentPrefix
                );
                if ($analyser->hasResult()) {
                    $this->foundSomething = true;
                    $this->deep = 0;
                    return $value;
                }
            }
        } catch (ReflectionException) {
        }

        return null;
    }

    /**
     * Retrieve the reflection of the object that is getting called.
     *
     * @param array $parts
     *   The parts from the regex scanner.
     * @param ReflectionClass $reflectionClass
     *   The reflection of the class that we are analysing
     *
     * @throws \ReflectionException
     *
     * @return ReflectionClass|null
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
            count(value: $parts) !== 2
            || !$reflectionClass->hasProperty(name: $parts[0])
        ) {
            // This is not the code I am looking for.
            return null;
        }

        $object = $reflectionClass->retrieveValue(refProperty: $reflectionClass->getProperty(name: $parts[0]));
        if (!is_object(value: $object)) {
            return null;
        }

        $delegateReflection = new ReflectionClass(data: $object);
        if (!$delegateReflection->hasMethod(name: $parts[1])) {
            return null;
        }

        return $delegateReflection;
    }
}
