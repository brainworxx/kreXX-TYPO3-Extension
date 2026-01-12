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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Fixtures;

use DateTime;
use stdClass;

#[\someAttribute]
#[\Phobject(
    [
        'validation' => [
            'required' => FALSE,
            'maxFiles' => 1,
            'fileSize' => [
                'minimum' => '0K',
                'maximum' => '2M'
            ],
            'allowedMimeTypes' => [
                0 => 'image/jpeg',
                1 => 'image/png',
                2 => 'image/gif'
            ],
            'imageDimensions' => [
                'maxWidth' => 4096,
                'maxHeight' => 4096
            ]
        ],
        'uploadFolder' => '1:/user_upload/',
        'addRandomSuffix' => TRUE,
        'duplicationBehavior' => 'myInterface',
        'enum' => SuitEnumFixture::Clubs
    ],
)]
#[Phobject\Attributes\Stuff(
    'beep',
    NULL,
    123,
    4.56,
    TRUE,
    FALSE,
    [
        0 => 'foo',
        1 => 'bar'
    ],
    stdClass::class,
    DateTime::class,
    [],
    new \stdClass(),
)]
class AttributeFixture
{
    public function __construct(
        #[Phobject\Attributes\Stuff('foo', 'bar')]
        public string $foo = 'bar',
    ) {
    }

    #[Phobject\Attributes\Stuff('grault', 'garply')]
    public function getFoo(): string
    {
        return $this->foo;
    }
}