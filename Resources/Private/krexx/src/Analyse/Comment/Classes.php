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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Comment;

use ReflectionClass;
use Reflector;

/**
 * Get the class comment.
 */
class Classes extends AbstractComment
{
    /**
     * Get the prettified class comment.
     *
     * @param Reflector $reflection
     *   The actual reflection class.
     * @param \ReflectionClass|null $reflectionClass
     *   Not used.
     *
     * @return string
     *   The comment.
     */
    public function getComment(Reflector $reflection, ReflectionClass $reflectionClass = null): string
    {
        static $cache = [];
        $name = $reflection->getName();

        if (isset($cache[$name]) === false) {
            $cache[$name] = $this->pool->encodingService->encodeString(
                $this->prettifyComment($reflection->getDocComment())
            );
        }

        return $cache[$name];
    }
}
