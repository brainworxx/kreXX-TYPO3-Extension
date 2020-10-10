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

namespace Brainworxx\Krexx\View;

/**
 * Array keys that are directly rendered into the frontend.
 *
 * @package Brainworxx\Krexx\View
 */
interface ViewConstInterface
{
    /**
     * @var string
     */
    const META_DECLARED_IN = 'Declared in';

    /**
     * @var string
     */
    const META_COMMENT = 'Comment';

    /**
     * @var string
     */
    const META_SOURCE = 'Source';

    /**
     * @var string
     */
    const META_NAMESPACE = 'Namespace';

    /**
     * @var string
     */
    const META_PARAM_NO = 'Parameter #';

    /**
     * @var string
     */
    const META_HELP = 'Help';

    /**
     * @var string
     */
    const META_LENGTH = 'Length';

    /**
     * @var string
     */
    const META_METHOD_COMMENT = 'Method comment';

    /**
     * @var string
     */
    const META_HINT = 'Hint';

    /**
     * @var string
     */
    const META_ENCODING = 'Encoding';

    /**
     * @var string
     */
    const META_MIME_TYPE = 'Mimetype';

    /**
     * @var string
     */
    const META_METHODS = 'Methods';

    /**
     * @var string
     */
    const META_CLASS_DATA = 'Meta class data';

    /**
     * @var string
     */
    const META_CLASS_NAME = 'Classname';

    /**
     * @var string
     */
    const META_INTERFACES = 'Interfaces';

    /**
     * @var string
     */
    const META_TRAITS = 'Traits';

    /**
     * @var string
     */
    const META_INHERITED_CLASS = 'Inherited class';

    /**
     * @var string
     */
    const META_PREDECLARED = 'n/a, is predeclared';

    /**
     * @var string
     */
    const META_IN_TRAIT =  'in trait: ';

    /**
     * @var string
     */
    const META_IN_LINE = 'in line: ';

    /**
     * @var string
     */
    const META_IN_CLASS = 'in class: ';

    /**
     * @var string
     */
    const META_PRETTY_PRINT = 'Pretty print';

    /**
     * @var string
     */
    const META_DECODED_JSON = 'Decoded json';

    /**
     * @var string
     */
    const META_DECODED_XML = 'Decoded xml';

    /**
     * @var string
     */
    const META_CONTENT = 'Content';

    /**
     * @var string
     */
    const META_TIMESTAMP = 'Timestamp';

    /**
     * @var string
     */
    const META_RETURN_TYPE = 'Return type';

    /**
     * Css class name.
     *
     * @var string
     */
    const STYLE_HIDDEN = 'khidden';

    /**
     * Css class name.
     *
     * @var string
     */
    const STYLE_ACTIVE = 'kactive';
}
