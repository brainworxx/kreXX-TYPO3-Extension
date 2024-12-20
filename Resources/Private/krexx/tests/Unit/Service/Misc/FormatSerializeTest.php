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

namespace Brainworxx\Krexx\Tests\Unit\Service\Misc;

use Brainworxx\Krexx\Service\Misc\FormatSerialize;
use Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture;
use Brainworxx\Krexx\Tests\Fixtures\SerializableFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FormatSerialize::class, 'prettyPrint')]
#[CoversMethod(FormatSerialize::class, 'assert')]
#[CoversMethod(FormatSerialize::class, 'parse')]
#[CoversMethod(FormatSerialize::class, 'parseArrayOrObject')]
#[CoversMethod(FormatSerialize::class, 'parseSerializableObject')]
#[CoversMethod(FormatSerialize::class, 'parseString')]
#[CoversMethod(FormatSerialize::class, 'read')]
#[CoversMethod(FormatSerialize::class, 'readTo')]
class FormatSerializeTest extends AbstractHelper
{
    /**
     * Test the pretty print of a serialized string.
     */
    public function testPrettyPrint()
    {
        $fixtureClass = new ComplexPropertiesFixture();
        $string = serialize($fixtureClass);

        $formatSerialize = new FormatSerialize();
        $result = $formatSerialize->prettyPrint($string);

        // Stuff that we expect in there.
        $expectationList = [
            '    ',
            '        ',
            'Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesFixture',
            'Brainworxx\Krexx\Tests\Fixtures\ComplexPropertiesInheritanceFixture',
            'inheritedProtected',
            'inherited public',
            'qwer',
            'asdf',
            'traitProperty',
            'dynamically declaration'
        ];

        foreach ($expectationList as $value) {
            $this->assertStringContainsString($value, $result);
        }
    }

    /**
     * We feed the pretty print with an invalid string.
     */
    public function testPrettyPrintFail()
    {
        $fixtureClass = new ComplexPropertiesFixture();
        $fixtureClass->wat = 'böf';
        $string = htmlspecialchars(serialize($fixtureClass));

        $formatSerialize = new FormatSerialize();
        $this->assertNull(
            $formatSerialize->prettyPrint(htmlspecialchars(serialize($string))),
            'The htmlspecialchars should break the serialization. We expect NULL on fail.'
        );
    }

    /**
     * Again with a serializable class.
     */
    public function testPrettyPrintSerializable()
    {
        $fixtureClass = new SerializableFixture();
        $string = serialize($fixtureClass);
        $formatSerialize = new FormatSerialize();
        $result = $formatSerialize->prettyPrint($string);

        // Stuff that we expect in there.
        $this->assertStringContainsString('just a string', $result);
    }
}
