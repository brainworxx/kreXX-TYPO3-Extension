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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

/**
 * Constants used in the routing/processing.
 *
 * All of them are literals for the frontend.
 */
interface ProcessConstInterface
{
    /**
     * Tell the developer: This value is a sting.
     *
     * @var string
     */
    public const TYPE_STRING = 'string';

    /**
     * Tell the developer: This value is an integer.
     *
     * @var string
     */
    public const TYPE_INTEGER = 'integer';

    /**
     * Tell the developer: This value is an array.
     *
     * @var string
     */
    public const TYPE_ARRAY = 'array';

    /**
     * Tell the developer: This value is an object.
     *
     * @var string
     */
    public const TYPE_OBJECT = 'object';

    /**
     * Tell the developer: This value is a stack frame from a backtrace.
     *
     * @var string
     */
    public const TYPE_STACK_FRAME = 'stack frame';

    /**
     * Tell the developer: This value is a boolean.
     *
     * @var string
     */
    public const TYPE_BOOL = 'boolean';

    /**
     * Tell the developer: This value is a closure.
     *
     * @var string
     */
    public const TYPE_CLOSURE = 'closure';

    /**
     * Tell the developer: This value is a float.
     * @var string
     */
    public const TYPE_FLOAT = 'float';

    /**
     * Tell the developer: This value is null.
     *
     * @var string
     */
    public const TYPE_NULL = 'null';

    /**
     * Tell the developer: This value is a class.
     *
     * @var string
     */
    public const TYPE_CLASS = 'class';

    /**
     * Tell the developer: This value is a resource.
     *
     * @var string
     */
    public const TYPE_RESOURCE = 'resource';
}
