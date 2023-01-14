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

namespace Brainworxx\Krexx\Tests\Unit\Service\Reflection;

use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Service\Reflection\UndeclaredProperty;
use Brainworxx\Krexx\Tests\Fixtures\ComplexMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\InheritDocFixture;
use Brainworxx\Krexx\Tests\Fixtures\InterfaceFixture;
use Brainworxx\Krexx\Tests\Fixtures\PublicFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
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
     *
     * @throws \ReflectionException
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
        $this->assertEquals($expectation, $this->retrieveValueByReflection('objectArray', $reflection));
        $this->assertInstanceOf(OriginalReflectionClass::class, $reflection);
        $this->assertSame($fixture, $reflection->getData());
    }

    /**
     * Simple getter tester.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::getData
     *
     * @throws \ReflectionException
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
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::isPropertyUnset
     *
     * @throws \ReflectionException
     */
    public function testRetrieveValue()
    {
        $normal = 'normal';
        $fixture = new PublicFixture();
        $fixture->{50} = 'special';
        $fixture->notSoSpecial = $normal;
        unset($fixture->value2);
        $notSoSpecial = 'notSoSpecial';

        $reflection = new ReflectionClass($fixture);
        $expectations = [
            'value1' => 'overwritten Value',
            'someValue' => 'whatever',
            'value2' => null,
            'value3' => '3',
            'value4' => 4,
            'value5' => 'dont\'t look at me!',
            'static' => 'static stuff',
            50 => 'special',
            $notSoSpecial => $normal
        ];

        foreach ($expectations as $name => $expectation) {
            if ($name === 'value5') {
                // That is a private in a deeper class.
                $refProperty = $reflection->getParentClass()->getProperty($name);
            } elseif ($name === 50) {
                // This one is dynamically declared.
                $refProperty = new UndeclaredProperty($reflection, 50);
            } elseif ($name === $notSoSpecial) {
                $refProperty = new UndeclaredProperty($reflection, $notSoSpecial);
            } else {
                $refProperty = $reflection->getProperty($name);
            }

            $this->assertEquals($expectation, $reflection->retrieveValue($refProperty));
            if ($name === 'value2') {
                $this->assertTrue($reflection->isPropertyUnset($refProperty));
            } else {
                $this->assertFalse($reflection->isPropertyUnset($refProperty));
            }
        }
    }

    /**
     * Test the retrieval of the actually implemented interfaces of this class.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::getInterfaces
     *
     * @throws \ReflectionException
     */
    public function testGetInterfaces()
    {
        $fixture = new PublicFixture();
        $reflection = new ReflectionClass($fixture);
        $this->assertEmpty($reflection->getInterfaces(), 'There are no interfaces in there.');

        $fixture = new InheritDocFixture();
        $reflection = new ReflectionClass($fixture);
        $this->assertEmpty($reflection->getInterfaces(), 'There are only some underlying interfaces.');

        $fixture = new ComplexMethodFixture();
        $reflection = new ReflectionClass($fixture);
        $interfaces = $reflection->getInterfaces();
        $this->assertCount(1, $interfaces, 'There is only one direct interface in here.');
        $this->assertArrayHasKey(InterfaceFixture::class, $interfaces);
    }

    /**
     * Test the retrieval of the traits.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::getTraits
     *
     * @throws \ReflectionException
     */
    public function testGetTraits()
    {
        $fixture = new ComplexMethodFixture();
        $reflection = new ReflectionClass($fixture);
        $result = $reflection->getTraits();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(ReflectionClass::class, array_shift($result));

        $fixture = new SimpleFixture();
        $reflection = new ReflectionClass($fixture);
        $this->assertEmpty($reflection->getTraits());
    }

    /**
     * Test the retrieval and caching of the parent class.
     *
     * @covers \Brainworxx\Krexx\Service\Reflection\ReflectionClass::getParentClass
     *
     * @throws \ReflectionException
     */
    public function testGetParentClass()
    {
        $fixture = new SimpleFixture();
        $reflection = new ReflectionClass($fixture);
        $this->assertFalse($reflection->getParentClass());

        $fixture = new ComplexMethodFixture();
        $reflection = new ReflectionClass($fixture);
        $result = $reflection->getParentClass();
        $this->assertInstanceOf(ReflectionClass::class, $result);
        $reflection = new ReflectionClass($fixture);
        $this->assertSame($result, $reflection->getParentClass(), 'Test the caching.');
    }
}
