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

class GetterRouting implements GetterInterface
{
    /**
     * @var AbstractGetter[]
     */
    protected array $getterAnalyser;

    /**
     * Indicate if we found something in the getter analyser.
     * NULL my actually mean NULL, but it can also mean "not found".
     *
     * @var bool
     */
    protected bool $foundSomething = false;

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
        $this->getterAnalyser[] = $this->pool->createClass(classname: ByRegExDelegate::class)->setRouting(routing: $this);
    }

    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ): mixed {
        foreach ($this->getterAnalyser as $getterAnalyser) {
            $result = $getterAnalyser->retrieveIt(
                reflectionMethod: $reflectionMethod,
                reflectionClass: $reflectionClass,
                currentPrefix: $currentPrefix
            );
            if ($this->foundSomething = $getterAnalyser->hasResult()) {
                return $result;
            }
        }

        return null;
    }

    public function hasResult(): bool
    {
        return $this->foundSomething;
    }
}
