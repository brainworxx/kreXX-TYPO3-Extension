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

use ReflectionMethod;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;

/**
 * Scanning the source code by regex for a possible value in an array that the
 * getter may return.
 */
class ByRegExContainer extends AbstractGetter
{
    /**
     * We try to parse something like:
     * return $this->container['value'];
     *
     * The expectation is this:
     * container['value'
     *
     * @var array|string[]
     */
    protected array $firstPattern = ['return $this->', '];'];

    /**
     * We split the result from the firstPattern by this one.
     *
     * container['value'
     * will split into
     * container and 'value'
     *
     * @var string
     */
    protected string $secondPattern = '[';

    /**
     * {@inheritDoc}
     */
    public function retrieveIt(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $reflectionClass,
        string $currentPrefix
    ) {
        $this->foundSomething = false;
        if ($reflectionMethod->isInternal()) {
            // There is no code for internal methods.
            return null;
        }

        // Read the sourcecode into a string.
        $sourcecode = $this->pool->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );

        // Identify the container.
        // We are looking for something like this:
        // $this->container['key'];
        $results = $this->findIt($this->firstPattern, $sourcecode);
        if (empty($results)) {
            return null;
        }

        // We take the first one that we get.
        // There may others in there, but when the developer uses static
        // caching, this is where the value should be.
        $parts = explode($this->secondPattern, $results[0]);
        if (count($parts) !== 2) {
            return null;
        }

        return $this->extractValue($parts, $reflectionClass);
    }

    /**
     * Extract the value from the parsed source code.
     *
     * @param array $parts
     *   The parsed source code, organised in parts.
     * @return mixed|null
     *   The extracted value. Null means that we were unable to find anything
     *   with certainty.
     */
    protected function extractValue(array $parts, ReflectionClass $reflectionClass)
    {
        // There may (or may not) be gibberish in there, but it does not matter.
        $containerName = $parts[0];
        if (!$reflectionClass->hasProperty($containerName)) {
            return null;
        }

        $key = trim($parts[1], '\'"');
        $container = $reflectionClass->retrieveValue($reflectionClass->getProperty($containerName));
        if (!isset($container[$key])) {
            return null;
        }

        $this->foundSomething = true;
        return $container[$key];
    }
}
