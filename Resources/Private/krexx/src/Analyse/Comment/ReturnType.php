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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use ReflectionClass;
use Reflector;

/**
 * Retrieve the return type of methods / functions.
 */
class ReturnType extends AbstractComment
{
    /**
     * The allowed types from the comment.
     *
     * Because, there may be a lot of BS in the comment.
     *
     * @var string[]
     */
    protected const ALLOWED_TYPES = [
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
     * @param \Reflector $reflection
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass|null $reflectionClass
     *   Reflection of the hosting class. A lot of return types are $this, so
     *   we can make use of it here.
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    public function getComment(Reflector $reflection, ReflectionClass $reflectionClass = null): string
    {
        // Get a first impression by the reflection.
        $result = $this->pool->createClass(MethodDeclaration::class)
            ->retrieveReturnType($reflection);
        if ($result !== '') {
            return $this->pool->encodingService->encodeString($result);
        }

        // Fallback to the comments parsing.
        $docComment = $reflection->getDocComment();
        if (
            !empty($docComment)
            && preg_match('/(?<=@return ).*$/m', $docComment, $matches) > 0
        ) {
            $result = $this->retrieveReturnTypeFromComment($matches[0], $reflectionClass);
        }

        return $result;
    }

    /**
     * Retrieve the return type from a comment string.
     *
     * @param string $comment
     *   The comment string.
     * @param \ReflectionClass|null $reflectionClass
     *   The reflection, which is used if the return comment is '$this'.
     *
     * @return string
     *   The return type.
     */
    protected function retrieveReturnTypeFromComment(string $comment, ReflectionClass $reflectionClass = null): string
    {
        $resultToken = strtok($comment . ' ', ' ');
        $result = '';
        if (strpos($resultToken, '$this') === 0 && $reflectionClass !== null) {
            // @return $this
            // And we know what $this actually is.
            $result = $this->pool->encodingService->encodeString('\\' . $reflectionClass->getName());
        } elseif (
            // Inside the whitelist
            in_array($resultToken, static::ALLOWED_TYPES, true) ||
            // Looks like a class name with namespace.
            strpos($resultToken, '\\') === 0 ||
            // Multiple types.
            strpos($resultToken, '|') !== false
        ) {
            $result = $this->pool->encodingService->encodeString($resultToken);
        }

        return $result;
    }

    /**
     * Simply ask the reflection method for it's return value.
     *
     * @param \Reflector $refMethod
     *   The reflection of the method we are analysing
     *
     * @deprecated since 5.0.0
     *   Was moved to the MethodDeclaration class.
     *
     * @codeCoverageIgnore
     *   We do not test depracated methods.
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    protected function retrieveTypeByReflection(Reflector $refMethod): string
    {
        return $this->pool->createClass(MethodDeclaration::class)
            ->retrieveNamedType($refMethod);
    }
}
