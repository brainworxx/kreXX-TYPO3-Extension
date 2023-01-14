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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Factory\Pool;
use Reflector;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

/**
 * Base class for the retrieval of a declaration place
 */
abstract class AbstractDeclaration
{
    /**
     * We will not root-namespace these.
     *
     * @var string[]
     */
    protected const ALLOWED_TYPES = [
        'int',
        'string',
        'mixed',
        'void',
        'resource',
        'bool',
        'array',
        'null',
        'float',
    ];

    /**
     * Here we store all relevant data.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * Injects the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Retrieve the declaration place in the code.
     *
     * @param Reflector $reflection
     *   The reflection of the method, function or property we want to retrieve.
     *
     * @return string
     *   The human-readable declaration place
     */
    abstract public function retrieveDeclaration(Reflector $reflection): string;

    /**
     * Simply ask the reflection type for its type.
     *
     * @param ReflectionType $namedType
     *   The reflection of the method we are analysing
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    protected function retrieveNamedType(ReflectionType $namedType): string
    {
        $result = '';

        // Handling the normal types.
        if ($namedType instanceof ReflectionNamedType) {
            $result = $this->formatNamedType($namedType);
        } elseif ($namedType instanceof ReflectionUnionType) {
            // Union types have several types in them.
            foreach ($namedType->getTypes() as $singleNamedType) {
                $result .=  $this->formatNamedType($singleNamedType) . '|';
            }
            $result = trim($result, '|');
        }

        return ($namedType->allowsNull() ? '?' : '') . $result;
    }

    /**
     * Format the names type.
     *
     * @param ReflectionNamedType $namedType
     *   The names type.
     *
     * @return string
     *   The formatted name of the type
     */
    protected function formatNamedType(ReflectionNamedType $namedType): string
    {
        $result = $namedType->getName();
        if (!in_array($result, static::ALLOWED_TYPES, true) && strpos($result, '\\') !== 0) {
            // Must be e un-namespaced class name.
            $result = '\\' . $result;
        }

        return $result;
    }
}
