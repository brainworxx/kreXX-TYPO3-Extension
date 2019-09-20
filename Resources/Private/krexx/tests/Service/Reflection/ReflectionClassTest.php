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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Service\Reflection;

use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use ReflectionClass as OriginalReflectionClass;
use stdClass;

class ReflectionClassTest extends AbstractTest
{
    /**
     * Testing the array casting of an object as well as creating the actual
     * reflection.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::__construct
     */
    public function testConstruct()
    {
        $fixture = new stdClass();
        $fixture->valueOne = 'qwer';
        $fixture->valueTwo = 'asdf';

        $expectation = [
            'valueOne' => 'qwer',
            'valueTwo' => 'asdf',
        ];

        $reflection = new ReflectionClass($fixture);
        $this->assertAttributeEquals($expectation, 'objectArray', $reflection);
        $this->assertInstanceOf(OriginalReflectionClass::class, $reflection);
        $this->assertAttributeSame($fixture, 'data', $reflection);
    }

    /**
     * Simple getter tester.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::getData
     */
    public function testGetData()
    {
        $fixture = new stdClass();
        $reflection = new ReflectionClass($fixture);

        $this->assertSame($fixture, $reflection->getData());
    }

    /**
     * It may not look like it, but this is the most important part of kreXX.
     * Here we retrieve the values from objects.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::retrieveValue
     */
    public function testRetreiveValue()
    {
        $fixture = new PublicFixture();
        unset($fixture->value2);

        $reflection = new ReflectionClass($fixture);
        $expectations = [
            'value1' => 'overwritten Value',
            'someValue' => 'whatever',
            'value2' => null,
            'value3' => '3',
            'value4' => 4,
            'value5' => 'dont\'t look at me!',
            'static' => 'static stuff',
        ];

        foreach ($expectations as $name => $expectation) {
            if ($name === 'value5') {
                // That is a private in a deeper class.
                $refProperty = $reflection->getParentClass()->getProperty($name);
            } else {
                $refProperty = $reflection->getProperty($name);
            }

            $this->assertEquals($expectation, $reflection->retrieveValue($refProperty));
            if ($name === 'value2') {
                $this->assertTrue($refProperty->isUnset);
            }
        }
    }
}
