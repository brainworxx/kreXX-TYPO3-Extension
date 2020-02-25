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

namespace Brainworxx\Krexx\Analyse;

/**
 * Interface with all the constants used all over the place.
 *
 * @package Brainworxx\Krexx\Analyse
 */
interface ConstInterface
{
    // Variable types. Css, array keys and frontend display.
    const TYPE_STRING = 'string ';
    const TYPE_INTEGER = 'integer';
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
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    const TYPE_STACK_FRAME = 'stack frame';
    const TYPE_BOOL = 'boolean';
    const TYPE_CLOSURE = 'closure';
    const TYPE_FLOAT = 'float';
    const TYPE_NULL = 'null';
    const TYPE_CLASS = 'class';
    const TYPE_RESOURCE = 'resource';

    // Callback parameters
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

    const UNKNOWN_VALUE = '. . .';
    const UNKNOWN_DECLARATION = 'unknownDeclaration';

    // Stuff from the backtrace.
    const TRACE_FILE = 'file';
    const TRACE_LINE = 'line';
    const TRACE_VARNAME = 'varname';
    const TRACE_TYPE = 'type';
    const TRACE_FUNCTION = 'function';
    const TRACE_CLASS = 'class';
    const TRACE_OBJECT = 'object';
    const TRACE_ARGS = 'args';
    const TRACE_BACKTRACE = 'backtrace';
    const TRACE_DATE = 'date';
    const TRACE_URL = 'url';

    // Stuff directly displayed in the FE, not just array keys.
    const META_DECLARED_IN = 'Declared in';
    const META_COMMENT = 'Comment';
    const META_SOURCE = 'Source';
    const META_NAMESPACE = 'Namespace';
    const META_PARAM_NO = 'Parameter #';
    const META_HELP = 'Help';
    const META_LENGTH = 'Length';
    const META_METHOD_COMMENT = 'Method comment';
    const META_HINT = 'Hint';
    const META_ENCODING = 'Encoding';
    const META_MIME_TYPE = 'Mimetype';
    const META_METHODS = 'Methods';
    const META_CLASS_DATA = 'Meta class data';
    const META_CLASS_NAME = 'Classname';
    const META_INTERFACES = 'Interfaces';
    const META_TRAITS = 'Traits';
    const META_INHERITED_CLASS = 'Inherited class';
    const META_PREDECLARED = 'n/a, is predeclared';

    // Stuff for the skin registration.
    const SKIN_CLASS = 'class';
    const SKIN_DIRECTORY = 'dir';

    const HEADLINE_EDIT_SETTINGS = 'Edit local settings';
    const HEADLINE_COOKIE_CONF = 'Cookie Configuration';

    const STYLE_HIDDEN = 'khidden';
    const STYLE_ACTIVE = 'kactive';
}
