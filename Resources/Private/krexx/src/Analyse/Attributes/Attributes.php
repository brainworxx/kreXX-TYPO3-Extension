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

namespace Brainworxx\Krexx\Analyse\Attributes;

use Brainworxx\Krexx\Service\Factory\Pool;
use Reflector;
use Throwable;

class Attributes
{
    /**
     * This is the pool.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected Pool $pool;

    /**
     * Inject the pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Retrieve the attributes from a reflection.
     *
     * Should be used in conjunction with the meta iterator.
     *
     * @param Reflector $reflection
     * @return array
     */
    protected function getAttributes(Reflector $reflection): array
    {
        $result = [];
        try {
            foreach ($reflection->getAttributes() as $reflectionAttribute) {
                $result[$reflectionAttribute->getName()] = $reflectionAttribute->getArguments();
            }
        } catch (Throwable $exception) {
        }

        return $result;
    }

    /**
     * Flat variant of the attribute list
     *
     * @param \Reflector $reflection
     * @return string
     */
    public function getFlatAttributes(Reflector $reflection): string
    {
        $attributes = $this->getAttributes($reflection);
        if (empty($attributes)) {
            return '';
        }

        $result = '';
        $encoder = $this->pool->encodingService;
        foreach ($attributes as $attributeName => $parameterList) {
            $result .= $encoder->encodeString($attributeName);
            $result .= $this->generateParameterList($parameterList);
            $result .= '<br>';
        }

        return trim($result, '<br>');
    }

    /**
     * Flatten the parameter list.
     *
     * @param array $parameterList
     *   The parameter list.
     *
     * @return string
     *   The flattened parameterlist.
     */
    protected function generateParameterList(array $parameterList): string
    {
        if (empty($parameterList)) {
            return '()';
        }

        $encoder = $this->pool->encodingService;
        $result = ' (';
        foreach ($parameterList as $parameter) {
            if (is_string($parameter)) {
                $result .= $encoder->encodeString($parameter) . ', ';
            } else {
                $result .= $parameter . ', ';
            }
        }

        return trim($result, ', ') . ')';
    }
}
