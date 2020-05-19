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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Callback;

/**
 * Array kay constants for the callback
 *
 * @package Brainworxx\Krexx\Analyse\Callback
 */
interface CallbackConstInterface
{
    /**
     * Keys for the data that was assigned to the callback
     */
    const PARAM_DATA = 'data';
    const PARAM_REF = 'ref';
    const PARAM_NAME = 'name';
    const PARAM_VALUE = 'value';
    const PARAM_META_NAME = 'metaname';
    const PARAM_MULTILINE = 'multiline';
    const PARAM_CLASSNAME = 'classname';
    const PARAM_NORMAL_GETTER = 'normalGetter';
    const PARAM_IS_GETTER = 'isGetter';
    const PARAM_HAS_GETTER = 'hasGetter';
    const PARAM_REF_METHOD = 'reflectionMethod';
    const PARAM_ADDITIONAL = 'additional';
    const PARAM_NOTHING_FOUND = 'nothingFound';
    const PARAM_REFLECTION_METHOD = 'refMethod';
    const PARAM_REFLECTION_PROPERTY = 'refProperty';
    const PARAM_CODE_GEN_TYPE = 'codeGenType';

    const TYPE_PHP = 'PHP';
    const TYPE_INTERNALS = 'class internals';
    const TYPE_DEBUG_METHOD = 'debug method';
    const TYPE_FOREACH = 'foreach';
    const TYPE_CONFIG = 'config';
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_SIMPLE_CLASS = 'simplified class analysis';
    const TYPE_SIMPLE_ARRAY = 'simplified array analysis';
    const TYPE_REFLECTION = 'reflection';
    const TYPE_METHOD = ' method';

    /**
     * Marks the last part of an even, when that part is finished.
     */
    const EVENT_MARKER_END = '::end';
    const EVENT_MARKER_ANALYSES_END = 'analysisEnd';
    const EVENT_MARKER_RECURSION = 'recursion';


    const UNKNOWN_DECLARATION = 'unknownDeclaration';
    const UNKNOWN_VALUE = '. . .';
}
