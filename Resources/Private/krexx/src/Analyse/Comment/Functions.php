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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

use ReflectionClass;
use Reflector;

/**
 * Getting the comment from functions.
 *
 * @package Brainworxx\Krexx\Analyse\Comment
 */
class Functions extends AbstractComment
{
    /**
     * Get the prettified comment from a function.
     *
     * @param \Reflector $reflectionFunction
     *   The reflection of the function with the comment.
     * @param \ReflectionClass|null $reflectionClass
     *   Nothing, null. We do not have a hosting class.
     *
     * @return string
     *   The prettified comment.
     */
    public function getComment(Reflector $reflectionFunction, ReflectionClass $reflectionClass = null)
    {
        // Do some static caching. The comment will not change during a run.
        static $cache = [];
        /** @var \ReflectionFunction $reflectionFunction */
        $cachingKey = $reflectionFunction->getName();

        if (isset($cache[$cachingKey]) === true) {
            return $cache[$cachingKey];
        }

        // Cache not found. We need to generate this one.
        $cache[$cachingKey] = $this->pool->encodingService->encodeString(
            $this->prettifyComment($reflectionFunction->getDocComment())
        );
        return $cache[$cachingKey];
    }
}
