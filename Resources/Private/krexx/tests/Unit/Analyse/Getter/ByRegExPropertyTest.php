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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Getter;

use Brainworxx\Krexx\Analyse\Getter\ByRegExProperty;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DeepGetterFixture;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Krexx;
use Exception;

class ByRegExPropertyTest extends AbstractGetter
{
    public function setUp(): void
    {
        parent::setUp();
        $this->testSubject = new ByRegExProperty(Krexx::$pool);
    }
    /**
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::__construct
     */
    public function testConstruct()
    {
        $byRegExProperty = new ByRegExProperty(Krexx::$pool);
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $byRegExProperty));
    }

    /**
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::findIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::analyseRegexResult
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrievePropertyByName
     */
    public function testRetrieveItSimple()
    {
        $instance = new GetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getSomething'),
                'prefix' => 'get',
                'expectation' => 'something',
                'propertyName' => 'something',
                'hasResult' => true
            ],
            [
                // The simple ByMethodName analysis should not be able to tackle this one.
                'reflection' => $classReflection->getMethod('isGood'),
                'prefix' => 'is',
                'expectation' => true,
                'propertyName' => 'isGood',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('hasValue'),
                'prefix' => 'has',
                'expectation' => false,
                'propertyName' => 'value',
                'hasResult' => true
            ],
            [
                 // There is no result whatsoever.
                'reflection' => $classReflection->getMethod('getProtectedStuff'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
        ];

        $this->validateResults($fixture, $classReflection);
    }

    /**
     * Test that we do not handle internal classes or methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::findIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::analyseRegexResult
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrievePropertyByName
     *
     */
    public function testRetrieveItInternal()
    {
        $instance = new Exception();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getCode'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ]
        ];

        $this->validateResults($fixture, $classReflection);
    }

    /**
     * Test the retrieval of the possible getter by the method name and by, deep
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrieveReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::findIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::analyseRegexResult
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExProperty::retrievePropertyByName
     */
    public function testRetrieveItDeep()
    {
        $instance = new DeepGetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getMyPropertyOne'),
                'prefix' => 'get',
                'expectation' => 'one',
                'propertyName' => 'myPropertyOne',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyTwo'),
                'prefix' => 'get',
                'expectation' => 'two',
                'propertyName' => '_myPropertyTwo',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyThree'),
                'prefix' => 'get',
                'expectation' => 'three',
                'propertyName' => 'MyPropertyThree',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFour'),
                'prefix' => 'get',
                'expectation' => 'four',
                'propertyName' => '_MyPropertyFour',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFive'),
                'prefix' => 'get',
                'expectation' => 'five',
                'propertyName' => 'mypropertyfive',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySix'),
                'prefix' => 'get',
                'expectation' => 'six',
                'propertyName' => '_mypropertysix',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySeven'),
                'prefix' => 'get',
                'expectation' => 'seven',
                'propertyName' => 'my_property_seven',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyEight'),
                'prefix' => 'get',
                'expectation' => 'eight',
                'propertyName' => '_my_property_eight',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyNine'),
                'prefix' => 'get',
                'expectation' => 'nine',
                'propertyName' => 'somethingDifferent',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('_getMyPropertyTen'),
                'prefix' => 'get',
                'expectation' => 'ten',
                'propertyName' => 'myPropertyTen',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyStatic'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getNull'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => 'null',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getAnotherGetter'),
                'prefix' => 'get',
                'expectation' => 'eight',
                'propertyName' => '_my_property_eight',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getLiterallyNoting'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('isMyPropertyTwelve'),
                'prefix' => 'is',
                'expectation' => true,
                'propertyName' => 'myPropertyTwelve',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('hasMyPropertyThirteen'),
                'prefix' => 'has',
                'expectation' => false,
                'propertyName' => 'myPropertyThirteen',
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('hasMyPropertyOne'),
                'prefix' => 'has',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ]
        ];
        $this->validateResults($fixture, $classReflection);
    }
}
