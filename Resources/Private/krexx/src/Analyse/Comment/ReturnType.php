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
    public const ALLOWED_TYPES = [
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
        'number',
        'true',
        'false',
        'never',
        'static',
        'iterable',
        'self'
    ];

    /**
     * Retrieve the return type from a method. Comment parsing as a fallback
     *
     * @param \ReflectionMethod $reflection
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass|null $reflectionClass
     *   Reflection of the hosting class. A lot of return types are $this, so
     *   we can make use of it here.
     *
     * @return string
     *   The return type if possible, an empty string if not.
     */
    public function getComment(Reflector $reflection, ?ReflectionClass $reflectionClass = null): string
    {
        // Get a first impression by the reflection.
        $result = $this->pool->createClass(classname: MethodDeclaration::class)
            ->retrieveReturnType(reflection: $reflection);
        if ($result !== '') {
            return $this->pool->encodingService->encodeString(data: $result);
        }

        // Fallback to the comments parsing.
        $docComment = $reflection->getDocComment();
        if (
            !empty($docComment)
            && preg_match(pattern: '/(?<=@return ).*$/m', subject: $docComment, matches: $matches) > 0
        ) {
            $result = $this->retrieveReturnTypeFromComment(comment: $matches[0], reflectionClass: $reflectionClass);
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
    protected function retrieveReturnTypeFromComment(string $comment, ?ReflectionClass $reflectionClass = null): string
    {
        $resultToken = strtok(token: ' ', string: $comment . ' ');
        $result = '';
        if (str_starts_with(haystack: $resultToken, needle: '$this') && $reflectionClass instanceof \ReflectionClass) {
            // @return $this
            // And we know what $this actually is.
            $result = $this->pool->encodingService->encodeString(data: '\\' . $reflectionClass->getName());
        } elseif (
            // Inside the whitelist
            in_array(needle: $resultToken, haystack: static::ALLOWED_TYPES, strict: true)
            // Looks like a class name with namespace.
            || str_starts_with(haystack: $resultToken, needle: '\\')
            // Multiple types.
            || str_contains(haystack: $resultToken, needle: '|')
        ) {
            $result = $this->pool->encodingService->encodeString(data: $resultToken);
        }

        return $result;
    }
}
