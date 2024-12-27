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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Attributes;

use Brainworxx\Krexx\Analyse\Attributes\Attributes;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\AttributesFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Krexx;
use Exception;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Attributes::class, '__construct')]
#[CoversMethod(Attributes::class, 'getFlatAttributes')]
#[CoversMethod(Attributes::class, 'getAttributes')]
#[CoversMethod(Attributes::class, 'generateParameterList')]
class AttributesTest extends AbstractHelper
{
    public function testConstruct()
    {
        $attributes = new Attributes(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $attributes));
    }

    public function testGetFlatAttributes()
    {
        $attributes = new Attributes(\Krexx::$pool);

        $fixture = new AttributesFixture();
        $reflectionClass = new ReflectionClass($fixture);
        $reflectionMethod = $reflectionClass->getMethod('testGetAttributes');
        $attributeResult = $attributes->getFlatAttributes($reflectionMethod);

        $reflectionProperty = $reflectionClass->getProperty('property');
        $propertyResult = $attributes->getFlatAttributes($reflectionProperty);
        $resultClass = $attributes->getFlatAttributes($reflectionClass);

        $fixture = new Exception();
        $reflectionClass = new ReflectionClass($fixture);
        $exceptionResult = $attributes->getFlatAttributes($reflectionClass);

        if (method_exists(ReflectionClass::class, 'getAttributes')) {
            $this->assertSame('Attribute()<br>Brainworxx\Krexx\Analyse\Attributes\Attributes(foo, bar, 5)', $resultClass);
            $this->assertEquals(
                'Brainworxx\Krexx\Tests\Fixtures\AttributesFixture(stuff, bob)',
                $attributeResult
            );
            $this->assertEmpty($exceptionResult, 'There are no properties.');
            $this->assertEquals('Brainworxx\Krexx\Tests\Fixtures\Property()', $propertyResult);
        } else {
            $this->assertEmpty($attributeResult, 'Wrong PHP Version ?!?');
            $this->assertEmpty($exceptionResult, 'Wrong PHP Version ?!?');
            $this->assertEmpty($propertyResult, 'Wrong PHP Version ?!?');
        }
    }

    /**
     * Test the attribute retrieval with errors.
     */
    public function testGetFlatAttributesWithErrors()
    {
        if (version_compare(phpversion(), '8.0.0', '<=')) {
            $this->markTestSkipped('Wrong PHP version.');
        }
        $attributes = new Attributes(\Krexx::$pool);
        $reflectorMock = $this->createMock(\ReflectionClass::class);
        $reflectorMock->expects($this->once())
            ->method('getAttributes')
            ->willThrowException(new \Exception());

        $this->assertEquals('', $attributes->getFlatAttributes($reflectorMock));
    }
}
