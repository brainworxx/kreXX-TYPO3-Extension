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

namespace Brainworxx\Krexx\Analyse\Caller;

/**
 * Array keys for the backtrace and/or caller finder.
 */
interface BacktraceConstInterface
{
    /**
     * Contains the filename where it was called.
     *
     * @var string
     */
    public const TRACE_FILE = 'file';

    /**
     * Contains the original, unfiltered path to the TRACE_FILE.
     *
     * @deprecated
     *   Since 6.0.0, will be removed.
     *
     * @var string
     */
    public const TRACE_ORG_FILE = 'originalFile';

    /**
     * The line number from the call.
     *
     * @var string
     */
    public const TRACE_LINE = 'line';

    /**
     * Variable name of the kreXX call.
     *
     * @var string
     */
    public const TRACE_VARNAME = 'varname';

    /**
     * When analysing an exception, the classname of the exception.
     * Otherwise, a human-readable text that describes what was analysed.
     *
     * @var string
     */
    public const TRACE_TYPE = 'type';

    /**
     * Function name that was called during single backtrace step.
     *
     * @var string
     */
    public const TRACE_FUNCTION = 'function';

    /**
     * Name of the class from a single backtrace step.
     *
     * @var string
     */
    public const TRACE_CLASS = 'class';

    /**
     * Instance in a backtrace step, where the object is stored from where the
     * step originated.
     *
     * @var string
     */
    public const TRACE_OBJECT = 'object';

    /**
     * The arguments from the last function call.
     *
     * @var string
     */
    public const TRACE_ARGS = 'args';

    /**
     * Not an array key. Literal for the frontend, saying that this is a backtrace.
     *
     * @var string
     */
    public const TRACE_BACKTRACE = 'backtrace';

    /**
     * The system date, when this backtrace was made.
     * @var string
     */
    public const TRACE_DATE = 'date';

    /**
     * The url that was called to generate this backtrace.
     *
     * @var string
     */
    public const TRACE_URL = 'url';

    /**
     * The 'level' of the backtrace:
     *   - error
     *     From a \Throwable.
     *   - debug
     *     From a debug backtrace.
     *
     * @var string
     */
    public const TRACE_LEVEL = 'level';

    /**
     * The time format pattern.
     *
     * @var string
     */
    public const TIME_FORMAT = 'd-m-Y H:i:s';
}
