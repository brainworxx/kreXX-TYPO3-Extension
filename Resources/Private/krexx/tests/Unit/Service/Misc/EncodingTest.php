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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\Encoding;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class EncodingTest extends AbstractTest
{
    /**
     * @var Encoding
     */
    protected $encoding;

    protected function krexxUp()
    {
        parent::krexxUp();
        $this->encoding = new Encoding(Krexx::$pool);
    }

    /**
     * Testing the setting of the pool annd the assigning of the string encoder.
     *
     * We will not test the cheap mb_string polyfills.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::__construct
     */
    public function testConstruct()
    {
        $this->assertSame($this->encoding, Krexx::$pool->encodingService);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->encoding));
    }

    /**
     * Testing the early return with an empty string.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeString
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeCompletely
     */
    public function testEncodeStringEmpty()
    {
        $fixture = '';
        $this->assertEquals($fixture, $this->encoding->encodeString($fixture));
    }

    /**
     * Testing the encoding of strings, also with some special stuff.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeString
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeCompletely
     */
    public function testEncodeStringNormal()
    {
        $fixture = 'just another string <div> { @  ';
        $expected = 'just another string &lt;div&gt; &#123; &#64;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));

        $fixture = 'just another string <div> { @' . chr(9);
        $expected = 'just another string &lt;div&gt; &#123; &#64;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture, true));
    }

    /**
     * Testing the encoding of strings, where htmlentities fail.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeString
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::arrayMapCallbackCode
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::arrayMapCallbackNormal
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeCompletely
     */
    public function testEncodeStringBroken()
    {
        $fixture = substr('öÖäÄüÜ', 0, 3);
        $expected = '&#246;&#63;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture));

        $fixture = substr('öÖäÄüÜ', 0, 3) . chr(9);
        $expected = '&#246;&#63;&nbsp;&nbsp;';
        $this->assertEquals($expected, $this->encoding->encodeString($fixture, true));
    }

    /**
     * Testing the preparation of striong as code connectors.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::encodeStringForCodeGeneration
     */
    public function testEncodeStringForCodeGeneration()
    {
        $fixture = 'value';

        $specialChars = [
            '"' => '&quot;',
            '\'' => '\&amp;#039;',
            "\0" => '&#039; . &quot;\0&quot; . &#039;',
            "\xEF" => '&#039; . &quot;\xEF&quot; . &#039;',
            "\xBB" => '&#039; . &quot;\xBB&quot; . &#039;',
            "\xBF" => '&#039; . &quot;\xBF&quot; . &#039;'
        ];

        foreach ($specialChars as $original => $expeced) {
            $this->assertEquals(
                $fixture . $expeced,
                $this->encoding->encodeStringForCodeGeneration($fixture . $original)
            );
        }
    }

    /**
     * Testing the wrapper around the mb_strlen.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::mbStrLen
     */
    public function testMbStrLen()
    {
        $mbStrLen = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Misc\\', 'mb_strlen');
        $mbStrLen->expects($this->exactly(2))
            ->withConsecutive(
                ['string'],
                ['another string', 'some encoding']
            )
            ->will($this->returnValue(42));

        $this->assertEquals(42, $this->encoding->mbStrLen('string'));
        $this->assertEquals(42, $this->encoding->mbStrLen('another string', 'some encoding'));
    }

    /**
     * Testing the property name analysis.
     *
     * @covers \Brainworxx\Krexx\Service\Misc\Encoding::isPropertyNameNormal
     */
    public function testIsPropertyNameNormal()
    {
        $this->assertTrue($this->encoding->isPropertyNameNormal('getValue'));
        $this->assertFalse($this->encoding->isPropertyNameNormal('get value'));
        $this->assertTrue($this->encoding->isPropertyNameNormal('getValue'));
    }
}
