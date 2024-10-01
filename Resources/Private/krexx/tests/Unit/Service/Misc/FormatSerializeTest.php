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

class FormatSerializeTest extends AbstractHelper
{
    /**
     * Test the pretty print of a serialized string.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::prettyPrint
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::assert
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parse
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseArrayOrObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseSerializableObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseString
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::read
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::readTo
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
     *
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::prettyPrint
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::assert
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parse
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseArrayOrObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseSerializableObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseString
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::read
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::readTo
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
     *
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::prettyPrint
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::assert
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parse
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseArrayOrObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseSerializableObject
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::parseString
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::read
     * @covers \Brainworxx\Krexx\Service\Misc\FormatSerialize::readTo
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
