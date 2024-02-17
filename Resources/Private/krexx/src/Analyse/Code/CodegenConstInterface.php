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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Code;

/**
 * Constants for the code generation.
 */
interface CodegenConstInterface
{
    /**
     * Meta stuff can not be reached in the code. Stops the code generation
     * right in its tracks.
     *
     * @var string
     */
    public const CODEGEN_TYPE_META_CONSTANTS = 'metaConstants';

    /**
     * Code generation for a public property or method
     * Simple concatenation.
     *
     * @var string
     */
    public const CODEGEN_TYPE_PUBLIC = 'public';

    /**
     * Wraps an iterator_to_array around one of the generation values.
     *
     * @var string
     */
    public const CODEGEN_TYPE_ITERATOR_TO_ARRAY = 'iteratorToArray';

    /**
     * Wraps a json_decode around one of the generation values.
     *
     * @var string
     */
    public const CODEGEN_TYPE_JSON_DECODE = 'jsonDecode';

    /**
     * Wraps a base64_decode around one of the generation values.
     *
     * @var string
     */
    public const CODEGEN_TYPE_BASE64_DECODE = 'base64Decode';

    /**
     * Wraps an array_values around one of the generation values.
     *
     * @var string
     */
    public const CODEGEN_TYPE_ARRAY_VALUES_ACCESS = 'arrayValuesAccess';

    /**
     * Returns an empty string. Does not stop the generation. It will resume
     * after this value.
     *
     * @var string
     */
    public const CODEGEN_TYPE_EMPTY = 'empty';

    /**
     * "Additional" json key, that contains the source code type hint.
     *
     * @var string
     */
    public const CODEGEN_TYPE_HINT = 'Type hint';

    /**
     * The equivalent of a stop bit for the code generation.
     *
     * @var string
     */
    public const CODEGEN_STOP_BIT = ';stop;';
}
