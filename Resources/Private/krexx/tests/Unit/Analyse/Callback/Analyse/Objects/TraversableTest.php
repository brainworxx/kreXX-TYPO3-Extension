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

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\MethodsFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Krexx;
use ArrayObject;

class TraversableTest extends AbstractTest
{
    const CHECK_NESTING = 'checkNesting';

    /**
     * @var string
     */
    protected $startEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Traversable::callMe::start';

    /**
     * @var string
     */
    protected $endEvent = 'Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Traversable::analysisEnd';

    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\PublicProperties
     */
    protected $traversable;

    /**
     * Create the class to test and inject the callback counter.
     *
     * {@inheritdoc}
     */
    public function krexxUp()
    {
        parent::krexxUp();

        // Create in instance of the class to test
        $this->traversable = new Traversable(Krexx::$pool);

        $this->mockEmergencyHandler();

        // Add the nesting level tests.
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('upOneNestingLevel');
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('downOneNestingLevel');
    }

    /**
     * Test, if we do not ignore the emergency handler.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     *
     * @throws \ReflectionException
     */
    public function testCallMeWithEmergency()
    {
        // Tell the emergency handler mock that we have a nesting level problem.
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method(static::CHECK_NESTING)
            ->will($this->returnValue(true));

        // Listen for the start event.
        $this->mockEventService(
            [$this->startEvent, $this->traversable]
        );

        // Create any fixture, we will not process it anyway, at least we should not.
        $data = new MethodsFixture();
        $fixture = [
            'data' => $data,
            'name' => 'some name',
            'ref' => new ReflectionClass($data)
        ];

        // Run the test.
        $this->traversable
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(0, CallbackCounter::$counter);

        // Prepare a second test.
        // Simulate an emergency break.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->any())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(true));

        Krexx::$pool->emergencyHandler = $emergencyMock;

        // Listen for the start event. Again.
        $this->mockEventService(
            [$this->startEvent, $this->traversable]
        );

        // Run the test.
        $this->traversable
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test, if the traversable analysis can handle some errors and warnings.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::retrieveTraversableData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::analyseTraversableResult
     *
     * @throws \ReflectionException
     */
    public function testCallMeWithErrors()
    {
        // Tell the emergency handler, that the nesting level is ok.
        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method(static::CHECK_NESTING)
            ->will($this->returnValue(false));

        // Listen for the start event.
        $this->mockEventService(
            [$this->startEvent, $this->traversable]
        );

        // Create any fixture that is not traversable should cause an error.
        $data = new MethodsFixture();
        $fixture = [
            'data' => $data,
            'name' => 'blargh',
            'ref' => new ReflectionClass($data)
        ];

        // Run the test.
        // The testing framework will also notice any thrown errors, warnings
        // or notices.
        $this->traversable
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(0, CallbackCounter::$counter);
    }

    /**
     * Test, if the normal array analysis is called.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::retrieveTraversableData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::analyseTraversableResult
     *
     * @throws \ReflectionException
     */
    public function testMeWithSmallArray()
    {
        // Tell the emergency handler, that the nesting level is ok.
        Krexx::$pool->emergencyHandler->expects($this->any())
            ->method(static::CHECK_NESTING)
            ->will($this->returnValue(false));

        // Listen for the start and end event.
        $this->mockEventService(
            [$this->startEvent, $this->traversable],
            [$this->endEvent, $this->traversable]
        );

        // Create a small iterateable fixture.
        $array = [
            'some' => 'array',
            'that' => 'is',
            2 => 'not',
            'long'
        ];
        $data = new ArrayObject($array);
        $fixture = [
            'data' => $data,
            'name' => 'whoopie',
            'ref' => new ReflectionClass($data)
        ];

        // Inject the callback counter
        Krexx::$pool->rewrite = [
            ThroughArray::class => CallbackCounter::class,
            ThroughLargeArray::class => 'some\\not\\existing\\class\to\trigger\\an\\error',
        ];

        // Run the test.
        // The testing framework will also notice any thrown errors, warnings
        // or notices.
        $this->traversable
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check the parameters.
        $this->assertEquals($array, CallbackCounter::$staticParameters[0]['data']);
        $this->assertEquals(false, CallbackCounter::$staticParameters[0]['multiline']);
    }

    /**
     * Test if the large array analysis is called.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::retrieveTraversableData
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable::analyseTraversableResult
     *
     * @throws \ReflectionException
     */
    public function testMeWithLargeArray()
    {
        // Tell the emergency handler, that the nesting level is ok.
        Krexx::$pool->emergencyHandler->expects($this->any())
            ->method(static::CHECK_NESTING)
            ->will($this->returnValue(false));

        // Listen for the start and end event.
        $this->mockEventService(
            [$this->startEvent, $this->traversable],
            [$this->endEvent, $this->traversable]
        );

        // Create a small iterateable fixture.
        $array = array_fill(0, 1000, 'whatever');
        $data = new ArrayObject($array);
        $fixture = [
            'data' => $data,
            'name' => 'tipsy',
            'ref' => new ReflectionClass($data)
        ];

        // Inject the callback counter
        Krexx::$pool->rewrite = [
            ThroughArray::class => 'some\\not\\existing\\class\\to\\trigger\\an\\error',
            ThroughLargeArray::class => CallbackCounter::class,
        ];

        // Run the test.
        // The testing framework will also notice any thrown errors, warnings
        // or notices.
        $this->traversable
            ->setParameters($fixture)
            ->callMe();

        // Check if the callback counter was called, at all.
        $this->assertEquals(1, CallbackCounter::$counter);

        // Check the parameters.
        $this->assertEquals($array, CallbackCounter::$staticParameters[0]['data']);
        $this->assertEquals(false, CallbackCounter::$staticParameters[0]['multiline']);
    }
}
