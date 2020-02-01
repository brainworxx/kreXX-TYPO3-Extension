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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DeepGetterFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\View\Render;
use Brainworxx\Krexx\Krexx;

class ThroughGetterTest extends AbstractTest
{
    /**
     * Test the creation of the comment analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::__construct
     */
    public function testConstruct()
    {
        $throughGetter = new ThroughGetter(Krexx::$pool);
        $this->assertInstanceOf(Methods::class, $this->retrieveValueByReflection('commentAnalysis', $throughGetter));
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $throughGetter));
    }

    /**
     * Testing the value retrieving in the getter analysis.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::goThroughMethodList
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::retrievePropertyValue
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::prepareResult
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::getReflectionProperty
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::preparePropertyName
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::getReflectionPropertyDeep
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::convertToSnakeCase
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::findIt
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::regexEscaping
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();

        // Test the events.
        // Some events are called multiple times, so we can not rely on the
        // mockEventService method.
        $throughGetter = new ThroughGetter(Krexx::$pool);
        $eventServiceMock = $this->createMock(Event::class);
        $eventServiceMock->expects($this->exactly(30))
            ->method('dispatch')
            ->withConsecutive(
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::callMe::start', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter]
            )
            ->will($this->returnValue(''));
        Krexx::$pool->eventService = $eventServiceMock;


        // Prevent the further routing.
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Mock the render object.
        $renderMock = $this->createMock(Render::class);
        $renderMock->expects($this->once())
            ->method('renderSingleChild')
            ->will($this->returnValue(''));
        Krexx::$pool->render = $renderMock;

        // Create a fixture.
        $data = new DeepGetterFixture();
        $ref = new ReflectionClass($data);
        $fixture = [
          'normalGetter' => [
              new \ReflectionMethod($data, 'getMyPropertyOne'),
              new \ReflectionMethod($data, 'getMyPropertyTwo'),
              new \ReflectionMethod($data, 'getMyPropertyThree'),
              new \ReflectionMethod($data, 'getMyPropertyFour'),
              new \ReflectionMethod($data, 'getMyPropertyFive'),
              new \ReflectionMethod($data, 'getMyPropertySix'),
              new \ReflectionMethod($data, 'getMyPropertySeven'),
              new \ReflectionMethod($data, 'getMyPropertyEight'),
              new \ReflectionMethod($data, 'getMyPropertyNine'),
              new \ReflectionMethod($data, 'getLiterallyNoting'),
          ],
          'isGetter' => [],
          'hasGetter' => [],
          'ref' => $ref,
          'data' => $data
        ];

        // Run the test.
        $throughGetter->setParameters($fixture)->callMe();

        // Get the models from RoutingNothing and assert their values.
        $models = Krexx::$pool->routing->model;
        $expectations = [
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine'
        ];
        // The last one is missing.
        foreach ($expectations as $key => $result) {
            $this->assertEquals($result, $models[$key]->getData());
        }
    }
}
