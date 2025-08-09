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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Comment;

use Brainworxx\Krexx\Analyse\Comment\Attributes;
use Brainworxx\Krexx\Tests\Fixtures\AttributeFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Attributes::class, 'getAttributes')]
#[CoversMethod(Attributes::class, 'flattenArgument')]
#[CoversMethod(Attributes::class, 'handleArray')]
#[CoversMethod(Attributes::class, 'indent')]
class AttributesTest extends AbstractHelper
{
    public function testGetAttributes(): void
    {
        if (version_compare(phpversion(), '8.1.0', '<')) {
            $this->markTestSkipped('Wrong PHP version.');
        }

        $attributes = new Attributes();
        $reflectionClass = new \ReflectionClass(AttributeFixture::class);
        $reflectionProperty = $reflectionClass->getProperty('foo');
        $reflectionMethod = $reflectionClass->getMethod('getFoo');

        // Test class attributes
        $classAttributes = $attributes->getAttributes($reflectionClass);
        $expectations = "#[someAttribute]
#[Phobject(
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
        'enum' => Brainworxx\Krexx\Tests\Fixtures\SuitEnumFixture::Clubs
    ],
)]
#[Brainworxx\Krexx\Tests\Fixtures\Phobject\Attributes\Stuff(
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
    'stdClass',
    'DateTime',
    [],
    stdClass::class,
)]";
        $this->assertEquals($expectations, $classAttributes);

        // Test property attributes
        $propertyAttributes = $attributes->getAttributes($reflectionProperty);
        $expectations = "#[Brainworxx\Krexx\Tests\Fixtures\Phobject\Attributes\Stuff(
    'foo',
    'bar',
)]";
        $this->assertEquals($expectations, $propertyAttributes);

        // Test method attributes
        $methodAttributes = $attributes->getAttributes($reflectionMethod);
        $expectations = "#[Brainworxx\Krexx\Tests\Fixtures\Phobject\Attributes\Stuff(
    'grault',
    'garply',
)]";
        $this->assertEquals($expectations, $methodAttributes);

        // Test with a class that has no attributes
        $reflectionClassWithoutAttributes = new \ReflectionClass(SimpleFixture::class);
        $noAttributes = $attributes->getAttributes($reflectionClassWithoutAttributes);
        $expectation = '';
        $this->assertEquals($expectation, $noAttributes, 'No attributes should return an empty string');
    }
}
