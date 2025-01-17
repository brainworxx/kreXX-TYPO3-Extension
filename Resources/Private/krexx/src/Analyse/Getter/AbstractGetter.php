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
use ReflectionMethod;
use ReflectionProperty;

abstract class AbstractGetter
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
     * This is the pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected Pool $pool;

    /**
     * Inject the pool,
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * We retrieve the possible return value of the gatter, without calling it.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the getter we are analysing.
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass
     *   The reflection of the class we are analysing.
     * @return mixed
     *   We retrieve the value.
     */
    abstract public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    );

    /**
     * What the method says. Have we found something?
     *
     * @return bool
     */
    public function hasResult(): bool
    {
        return $this->foundSomething;
    }

    /**
     * @return \ReflectionProperty|null
     */
    public function getReflectionProperty(): ?ReflectionProperty
    {
        return $this->reflectionProperty;
    }

    /**
     * Searching for stuff via regex. Type casting inside the code will be ignored.
     *
     * @param string[] $searchArray
     *   The search definition.
     * @param string $haystack
     *   The haystack, obviously. Aka "the code".
     *
     * @return string[]|int[]
     *   The findings.
     */
    protected function findIt(array $searchArray, string $haystack): array
    {
        // Some people cast their stuff before returning it.
        // Remove it from the code before passing it to the regex.
        $haystack = str_replace(['(int)', '(string)', '(float)', '(bool)'], '', $haystack);
        $haystack = str_replace('  ', ' ', $haystack);

        $findings = [];
        preg_match_all(
            str_replace(
                ['###0###', '###1###'],
                [preg_quote($searchArray[0]), preg_quote($searchArray[1])],
                '/(?<=###0###).*?(?=###1###)/'
            ),
            $haystack,
            $findings
        );

        // Return the file name as well as stuff from the path.
        return $findings[0];
    }
}
