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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DebugMethodFixture;
use Brainworxx\Krexx\Tests\Fixtures\GetterFixture;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use ReflectionMethod;

class GetterTest extends AbstractHelper
{
    const TEST_STRING = 'some name';

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Getter::analysisEnd';


    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter
     */
    protected $getter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->getter = new Getter(Krexx::$pool);
        // Prevent getting deeper into the rabbit hole.
        Krexx::$pool->rewrite = [
            ThroughGetter::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Test without any methods at all.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::populateGetterLists
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::retrieveMethodList
     *
     * @throws \ReflectionException
     */
    public function testCallMeEmpty()
    {
        // Setup the events.
        $this->mockEventService(
            [$this->startEvent, $this->getter]
        );

        // Set up fixture, without any methods at all.
        $getterFixture = new SimpleFixture();
        $fixture = [
            'data' => $getterFixture,
            'name' => static::TEST_STRING,
            'ref' => new ReflectionClass($getterFixture)
        ];

        // Test for empty result.
        $this->assertEquals(
            '',
            $this->getter->setParameters($fixture)->callMe()
        );

        // Test for no callbacks and no parameters.
        // Was it called?
        $this->assertEquals(0, CallbackCounter::$counter);
        $this->assertEquals([], CallbackCounter::$staticParameters);
    }

    /**
     * Test without any getter methods.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::populateGetterLists
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::retrieveMethodList
     *
     * @throws \ReflectionException
     */
    public function testCallMeWithoutGetter()
    {
        // Setup the events.
        $this->mockEventService(
            [$this->startEvent, $this->getter]
        );

        // Set up fixture, without any methods at all.
        $getterFixture = new DebugMethodFixture();
        $fixture = [
            'data' => $getterFixture,
            'name' => static::TEST_STRING,
            'ref' => new ReflectionClass($getterFixture)
        ];

        // Test for empty result.
        $this->assertEquals(
            '',
            $this->getter->setParameters($fixture)->callMe()
        );

        // Test for no callbacks and no parameters.
        // Was it called?
        $this->assertEquals(0, CallbackCounter::$counter);
        $this->assertEquals([], CallbackCounter::$staticParameters);
    }

    /**
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::populateGetterLists
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::retrieveMethodList
     *
     * @throws \ReflectionException
     */
    public function testCallMeInScope()
    {
        // Setup the events.
        $this->mockEventService(
            [$this->startEvent, $this->getter],
            [$this->endEvent, $this->getter]
        );

        // Set up fixture, without any methods at all.
        $getterFixture = new GetterFixture();
        $fixture = [
            'data' => $getterFixture,
            'name' => static::TEST_STRING,
            'ref' => new ReflectionClass($getterFixture)
        ];

        // Set the scope!
        Krexx::$pool->scope->setScope('$this');

        // Run the test
        $this->getter->setParameters($fixture)
            ->callMe();


        // Test for no callbacks and no parameters.
        // Was it called?
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check the expected result:
        $expectedResult = [
            0 => [
                'ref' => $fixture['ref'],
                'normalGetter' => [
                    new ReflectionMethod($getterFixture, 'getProtectedStuff'),
                    new ReflectionMethod($getterFixture, 'getSomething')
                ],
                'isGetter' => [new ReflectionMethod($getterFixture, 'isGood')],
                'hasGetter' => [new ReflectionMethod($getterFixture, 'hasValue')]
            ]
        ];
        $this->assertEquals($expectedResult, CallbackCounter::$staticParameters);
    }

    /**
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::populateGetterLists
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Getter::retrieveMethodList
     *
     * @throws \ReflectionException
     */
    public function testCallMeOutOfScope()
    {
        // Setup the events.
        $this->mockEventService(
            [$this->startEvent, $this->getter],
            [$this->endEvent, $this->getter]
        );

        // Set up fixture, without any methods at all.
        $getterFixture = new GetterFixture();
        $fixture = [
            'data' => $getterFixture,
            'name' => static::TEST_STRING,
            'ref' => new ReflectionClass($getterFixture)
        ];

        // Run the test
        $this->getter->setParameters($fixture)
            ->callMe();


        // Test for no callbacks and no parameters.
        // Was it called?
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check the expected result:
        $expectedResult = [
            0 => [
                'ref' => $fixture['ref'],
                'normalGetter' => [new ReflectionMethod($getterFixture, 'getSomething')],
                'isGetter' => [new ReflectionMethod($getterFixture, 'isGood')],
                'hasGetter' => [new ReflectionMethod($getterFixture, 'hasValue')]
            ]
        ];
        $this->assertEquals($expectedResult, CallbackCounter::$staticParameters);
    }
}
