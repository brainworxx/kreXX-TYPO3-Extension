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

namespace Brainworxx\Krexx\Analyse\Callback;

/**
 * Array key constants for the callback. As well as literals for the frontend.
 */
interface CallbackConstInterface
{
    /**
     * Array key for $this->parameters.
     *
     * Here we store the variable, that we are currently analysing
     *
     * @var string
     */
    public const PARAM_DATA = 'data';

    /**
     * Array key for $this->parameters.
     *
     * Reflection of the object that we are analysing.
     *
     * @var string
     */
    public const PARAM_REF = 'ref';

    /**
     * Array key for $this->parameters.
     *
     * Name of the variable, that we are analysing.
     *
     * @var string
     */
    public const PARAM_NAME = 'name';

    /**
     * Array key for $this->parameters.
     *
     * Retrieved value from the getter analysis.
     *
     * @var string
     */
    public const PARAM_VALUE = 'value';

    /**
     * Array key for $this->parameters.
     *
     * Used in the meta analysis. Name of the metadata, if available.
     *
     * @var string
     */
    public const PARAM_META_NAME = 'metaname';

    /**
     * Array key for $this->parameters.
     *
     * Parameter for the array analysis. Historical name, where we were planing
     * for multiline code analysis. Now holds the info, if we do complicate
     * stuff to reach this value.
     *
     * @var string
     */
    public const PARAM_MULTILINE = 'multiline';

    /**
     * Array key for $this->parameters.
     *
     * The classname of the object we ara analysing, if available.
     *
     * @var string
     */
    public const PARAM_CLASSNAME = 'classname';

    /**
     * Array key for $this->parameters.
     *
     * List of strings, containing the method names of the "normal" getter,
     * which start with "get".
     *
     * @var string
     */
    public const PARAM_NORMAL_GETTER = 'normalGetter';

    /**
     * Array key for $this->parameters.
     *
     * List of strings, containing the method names of the "is" getter,
     * which start with "is".
     *
     * @var string
     */
    public const PARAM_IS_GETTER = 'isGetter';

    /**
     * Array key for $this->parameters.
     *
     * List of strings, containing the method names of the "has" getter,
     * which start with "has".
     *
     * @var string
     */
    public const PARAM_HAS_GETTER = 'hasGetter';

    /**
     * Array key for $this->parameters.
     *
     * Contains an array with additional infos.
     *
     * @var string
     */
    public const PARAM_ADDITIONAL = 'additional';

    /**
     * Array key for $this->parameters.
     *
     * Contains a boolean, informing the ThroughGetter event subscriber, if there
     * was a result so far.
     *
     * @var string
     */
    public const PARAM_NOTHING_FOUND = 'nothingFound';

    /**
     * Array key for $this->parameters.
     *
     * Reflection of the method, that we are currently analysing.
     *
     * @var string
     */
    public const PARAM_REFLECTION_METHOD = 'refMethod';

    /**
     * Array key for $this->parameters.
     *
     * Reflection of the property we are analysing.
     *
     * @var string
     */
    public const PARAM_REFLECTION_PROPERTY = 'refProperty';

    /**
     * Array key for $this->parameters.
     *
     * Contains the code generation type.
     * @see
     *   CodegenConstInterface::CODEGEN_TYPE_META_CONSTANTS
     *   CodegenConstInterface::CODEGEN_TYPE_PUBLIC
     *   CodegenConstInterface::CODEGEN_TYPE_ITERATOR_TO_ARRAY
     *   CodegenConstInterface::CODEGEN_TYPE_JSON_DECODE
     *   CodegenConstInterface::CODEGEN_TYPE_ARRAY_VALUES_ACCESS
     *   CodegenConstInterface::CODEGEN_TYPE_EMPTY
     *
     * @var string
     */
    public const PARAM_CODE_GEN_TYPE = 'codeGenType';

    /**
     * Frontend literal.
     *
     * @var string
     */
    public const TYPE_PHP = 'PHP';

    /**
     * Frontend literal.
     *
     * @deprecated
     *   Since 6.0.0. Will be removed.
     *
     * @var string
     */
    public const TYPE_INTERNALS = 'class internals';

    /**
     * Frontend literal.
     *
     * @deprecated
     *   Since 6.0.0. Will be removed.
     *
     * @var string
     */
    public const TYPE_DEBUG_METHOD = 'debug method';

    /**
     * Frontend literal.
     *
     * @var string
     */
    public const TYPE_FOREACH = 'foreach';

    /**
     * Frontend literal.
     *
     * @var string
     */
    public const TYPE_CONFIG = 'config';

    /**
     * Frontend literal.
     *
     * @deprecated
     *   Since 6.0.0. Will be removed.
     *
     * @var string
     */
    public const TYPE_UNKNOWN = 'unknown';

    /**
     * Frontend literal.
     *
     * @deprecated
     *   Since 6.0.0. Will be removed.
     *
     * @var string
     */
    public const TYPE_SIMPLE_CLASS = 'simplified class analysis';

    /**
     * Frontend literal.
     *
     * @deprecated
     *   Since 6.0.0. Will be removed.
     *
     * @var string
     */
    public const TYPE_SIMPLE_ARRAY = 'simplified array analysis';

    /**
     * Frontend literal.
     *
     * @var string
     */
    public const TYPE_REFLECTION = 'reflection';

    /**
     * Frontend literal.
     *
     * @var string
     */
    public const TYPE_METHOD = ' method';

    /**
     * Part of the event system.
     *
     * Marks the last event in this run, for this instance
     *
     * @var string
     */
    public const EVENT_MARKER_END = '::end';

    /**
     * Part of the event system.
     *
     * Marks the end of the analysis of this specific value.
     *
     * @var string
     */
    public const EVENT_MARKER_ANALYSES_END = 'analysisEnd';

    /**
     * Part of the event system.
     *
     * Marks a recursion in the event system.
     *
     * @var string
     */
    public const EVENT_MARKER_RECURSION = 'recursion';

    /**
     * Literal for the frontend and the JavaScript
     *
     * Placeholder for an unknown value. Or a value that has been omitted.
     * Also tells the JavaScript, that source generation beyond this point is
     * not allowed.
     *
     * @var string
     */
    public const UNKNOWN_VALUE = '. . .';
}
