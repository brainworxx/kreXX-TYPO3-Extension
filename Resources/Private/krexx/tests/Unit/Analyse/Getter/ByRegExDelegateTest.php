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

use Brainworxx\Krexx\Analyse\Getter\ByRegExDelegate;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DelegateGetterFixture;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ByRegExDelegate::class, '__construct')]
#[CoversMethod(ByRegExDelegate::class, 'retrieveIt')]
#[CoversMethod(ByRegExDelegate::class, 'extractValue')]
#[CoversMethod(ByRegExDelegate::class, 'retrieveReflectionClass')]
class ByRegExDelegateTest extends AbstractGetter
{
    public function setUp(): void
    {
        parent::setUp();
        $this->testSubject = new ByRegExDelegate(Krexx::$pool);
    }

    /**
     * Test the initialization of the getter analysers
     */
    public function testConstruct()
    {
        $this->assertNotEmpty($this->retrieveValueByReflection('getterAnalyser', $this->testSubject));
    }

    /**
     * The class to test should not be able to retrieve any of these.
     */
    public function testRetrieveIt()
    {
        $instance = new DelegateGetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getMyPropertyOne'),
                'prefix' => 'get',
                'expectation' => 'one',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyTwo'),
                'prefix' => 'get',
                'expectation' => 'two',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyThree'),
                'prefix' => 'get',
                'expectation' => 'three',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFour'),
                'prefix' => 'get',
                'expectation' => 'four',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyFive'),
                'prefix' => 'get',
                'expectation' => 'five',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySix'),
                'prefix' => 'get',
                'expectation' => 'six',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertySeven'),
                'prefix' => 'get',
                'expectation' => 'seven',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyEight'),
                'prefix' => 'get',
                'expectation' => 'eight',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getMyPropertyNine'),
                'prefix' => 'get',
                'expectation' => 'nine',
                'propertyName' => null,
                'hasResult' => true
            ],

            // Adding stuff that should not yield any results.
            [
                'reflection' => $classReflection->getMethod('getNull'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getAnError'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getAnotherError'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getLiterallyNoting'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getSomethingFromFalse'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getWrongMethod'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
        ];

        $this->validateResults($fixture, $classReflection);
    }
}
