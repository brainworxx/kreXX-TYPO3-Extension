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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Comment;

use Brainworxx\Krexx\Analyse\ConstInterface;
use ReflectionClass;
use Reflector;

/**
 * Retrieve the return type of methods / functions.
 *
 * @package Brainworxx\Krexx\Analyse\Comment
 */
class ReturnType extends AbstractComment implements ConstInterface
{
    /**
     * The allowed types from the comment.
     *
     * Because, there may be a lot of BS in the comment.
     *
     * @var array
     */
    protected $allowedTypes = [
        'int',
        'integer',
        'string',
        'mixed',
        'void',
        'object',
        'resource',
        'bool',
        'boolean',
        'array',
        '[]',
        'null',
        'float',
        'double',
        'number'
    ];

    /**
     * Retrieve the return type from a method. Comment parsing as a fallback
     *
     * @param \Reflector $refMethod
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass $reflectionClass
     *   Reflection of t he hosting class. A lot of return types are $this, so
     *   we can make use of it here.
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    public function getComment(Reflector $refMethod, ReflectionClass $reflectionClass = null): string
    {
        // Get a first impression by the reflection.
        $result = $this->retrieveTypeByReflection($refMethod);
        if ($result !== '') {
            return $this->pool->encodingService->encodeString($result);
        }

        // Fallback to the comments parsing.
        $docComment = $refMethod->getDocComment();
        if (empty($docComment) || preg_match('/(?<=@return ).*$/m', $docComment, $matches) === 0) {
            // No comment.
            return '';
        }

        $result = strtok($matches[0] . ' ', ' ');
        if ($result === '$this' && $reflectionClass !== null) {
            return $this->pool->encodingService->encodeString('\\' . $reflectionClass->getName());
        }

        if (
            // Inside the whitelist
            in_array($result, $this->allowedTypes) === true ||
            // Looks like a class name with namespace.
            strpos($result, '\\') === 0 ||
            // Multiple types.
            strpos($result, '|') !== false
        ) {
            return $this->pool->encodingService->encodeString($result);
        }

        // Nothing of value was found.
        return '';
    }

    /**
     * Simply ask the reflection method for it's return value.
     *
     * @param \Reflector $refMethod
     *   The reflection of the method we are analysing
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    protected function retrieveTypeByReflection(Reflector $refMethod): string
    {
        $result = '';
        /** @var \ReflectionMethod $refMethod */
        $returnType = $refMethod->getReturnType();
        if ($returnType === null) {
            // Nothing found, early return.
            return $result;
        }

        if (method_exists($returnType, 'getName') === true) {
            // 7.1 or later. We alo need to check for nullable types.
            $nullable = $returnType->allowsNull() === true ? '?' : '';
            $result = $returnType->getName();
        } else {
            // Must be 7.0.
            $result = $returnType->__toString();
            $nullable = '';
        }

        if (in_array($result, $this->allowedTypes) === false && strpos($result, '\\') !== 0) {
            // Must be e un-namespaced class name.
            $result = '\\' . $result;
        }


        return $nullable . $result;
    }
}
