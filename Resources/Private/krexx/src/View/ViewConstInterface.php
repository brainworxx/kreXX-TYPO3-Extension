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
    const META_IN_TRAIT =  'in trait: ';
    const META_IN_LINE = 'in line: ';
    const META_IN_CLASS = 'in class: ';
    const META_PRETTY_PRINT = 'Pretty print';
    const META_DECODED_JSON = 'Decoded json';
    const META_DECODED_XML = 'Decoded xml';
    const META_CONTENT = 'Content';
    const META_TIMESTAMP = 'Timestamp';
    const META_RETURN_TYPE = 'Return type';

    const STYLE_HIDDEN = 'khidden';
    const STYLE_ACTIVE = 'kactive';
}
