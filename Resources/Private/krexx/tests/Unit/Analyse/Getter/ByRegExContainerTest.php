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

use Brainworxx\Krexx\Analyse\Getter\ByRegExContainer;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\ContainerFixture;
use Exception;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Krexx;

class ByRegExContainerTest extends AbstractGetter
{
    public function setUp(): void
    {
        parent::setUp();
        $this->testSubject = new ByRegExContainer(Krexx::$pool);
    }

    /**
     * The class to test should not be able to retrieve any of these.
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::extractValue
     */
    public function testRetrieveItSimple()
    {
        $instance = new GetterFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getSomething'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                // The simple ByMethodName analysis should not be able to tackle this one.
                'reflection' => $classReflection->getMethod('isGood'),
                'prefix' => 'is',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('hasValue'),
                'prefix' => 'has',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
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
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::extractValue
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
     * Retrieving the value fom a value-container.
     *
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::retrieveIt
     * @covers \Brainworxx\Krexx\Analyse\Getter\ByRegExContainer::extractValue
     */
    public function testRetrieveItContainer()
    {
        $instance = new ContainerFixture();
        $classReflection = new ReflectionClass($instance);
        $fixture = [
            [
                'reflection' => $classReflection->getMethod('getValue'),
                'prefix' => 'get',
                'expectation' => 1,
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getSomething'),
                'prefix' => 'get',
                'expectation' => 'more stuff',
                'propertyName' => null,
                'hasResult' => true
            ],
            [
                'reflection' => $classReflection->getMethod('getStatic'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getWrongContainer'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getBadFormatting'),
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
                'reflection' => $classReflection->getMethod('getSomethingElse'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
            [
                'reflection' => $classReflection->getMethod('getBadComments'),
                'prefix' => 'get',
                'expectation' => null,
                'propertyName' => null,
                'hasResult' => false
            ],
        ];

        $this->validateResults($fixture, $classReflection);
    }
}
