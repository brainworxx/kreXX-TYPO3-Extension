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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse;

/**
 * Interface with all the constants used in Routing ans Callback
 *
 * @package Brainworxx\Krexx\Analyse
 */
interface ConstInterface
{
    const TYPE_STRING = 'string ';
    const TYPE_INTEGER = 'integer';
    const TYPE_PHP = 'PHP';
    const TYPE_INTERNALS = 'class internals';
    const TYPE_DEBUG_METHOD = 'debug method';
    const TYPE_FOREACH = '´foreach';
    const TYPE_CONFIG = 'config';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_SIMPLE_CLASS = 'simplified class analysis';
    const TYPE_SIMPLE_ARRAY = 'simplified array analysis';
    const TYPE_REFLECTION = 'reflection';
    const TYPE_METHOD = ' method';
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_STACK_FRAME = 'stack frame';
    const TYPE_BOOL = 'boolean';
    const TYPE_CLOSURE = 'closure';
    const TYPE_FLOAT = 'float';
    const TYPE_NULL = 'null';
    const TYPE_CLASS = 'class';
    const TYPE_RESOURCE = 'resource';

    const PARAM_DATA = 'data';
    const PARAM_REF = 'ref';
    const PARAM_NAME = 'name';
    const PARAM_MULTILINE = 'multiline';
    const PARAM_CLASSNAME = 'classname';
    const PARAM_NORMAL_GETTER = 'normalGetter';
    const PARAM_IS_GETTER = 'isGetter';
    const PARAM_HAS_GETTER = 'hasGetter';
    const PARAM_REF_METHOD = 'reflectionMethod';
    const PARAM_ADDITIONAL = 'additional';
}
