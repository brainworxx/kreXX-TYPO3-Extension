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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Brainworxx\Krexx\Tests\Fixtures\DeepGetterFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\View\Skins\RenderHans;
use ReflectionMethod;

class ThroughGetterTest extends AbstractHelper
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
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::analyseRegexResult
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::retrievePropertyByName
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::convertToSnakeCase
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::findIt
     *
     * @throws \ReflectionException
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();

        // Test the events.
        // Some events are called multiple times, so we can not rely on the
        // mockEventService method.
        $throughGetter = new ThroughGetter(Krexx::$pool);
        $eventServiceMock = $this->createMock(Event::class);
        $eventServiceMock->expects($this->exactly(52))
            ->method('dispatch')
            ->with(...$this->withConsecutive(
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::callMe::start', $throughGetter],
                 // getMyPropertyOne
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyTwo
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyThree
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyFour
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyFive
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertySix
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertySeven
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyEight
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyPropertyNine
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // _getMyPropertyTen
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getMyStatic
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getNull
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getAnotherGetter
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // getLiterallyNoting
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // isMyPropertyTwelve
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // hasMyPropertyThirteen
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter],
                // hasMyPropertyOne
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::goThroughMethodList::end', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::resolving', $throughGetter],
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter::retrievePropertyValue::end', $throughGetter]

            ))->will($this->returnValue(''));
        Krexx::$pool->eventService = $eventServiceMock;


        // Prevent the further routing.
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);

        // Mock the render object.
        $renderMock = $this->createMock(RenderHans::class);
        $renderMock->expects($this->once())
            ->method('renderExpandableChild')
            ->will($this->returnValue(''));
        Krexx::$pool->render = $renderMock;

        // Create a fixture.
        $data = new DeepGetterFixture();
        $ref = new ReflectionClass($data);
        $fixture = [
          'normalGetter' => [
              new ReflectionMethod($data, 'getMyPropertyOne'),
              new ReflectionMethod($data, 'getMyPropertyTwo'),
              new ReflectionMethod($data, 'getMyPropertyThree'),
              new ReflectionMethod($data, 'getMyPropertyFour'),
              new ReflectionMethod($data, 'getMyPropertyFive'),
              new ReflectionMethod($data, 'getMyPropertySix'),
              new ReflectionMethod($data, 'getMyPropertySeven'),
              new ReflectionMethod($data, 'getMyPropertyEight'),
              new ReflectionMethod($data, 'getMyPropertyNine'),
              new ReflectionMethod($data, '_getMyPropertyTen'),
              new ReflectionMethod($data, 'getMyStatic'),
              new ReflectionMethod($data, 'getNull'),
              new ReflectionMethod($data, 'getAnotherGetter'),
              new ReflectionMethod($data, 'getLiterallyNoting'),
          ],
          'isGetter' => [
              new ReflectionMethod($data, 'isMyPropertyTwelve'),
          ],
          'hasGetter' => [
              new ReflectionMethod($data, 'hasMyPropertyThirteen'),
              new ReflectionMethod($data, 'hasMyPropertyOne'),
          ],
          'ref' => $ref,
          'data' => $data
        ];

        // Run the test.
        $throughGetter->setParameters($fixture)->callMe();

        // Get the models from RoutingNothing and assert their values.
        $models = Krexx::$pool->routing->model;
        $expectations = [
            //getMyPropertyOne
            0 => 'one',
            // getMyPropertyTwo
            1 => 'two',
            // getMyPropertyThree
            2 => 'three',
            // getMyPropertyFour
            3 => 'four',
            // getMyPropertyFive
            4 => 'five',
            // getMyPropertySix
            5 => 'six',
            // getMyPropertySeven
            6 => 'seven',
            // getMyPropertyEight
            7 => 'eight',
            // getMyPropertyNine
            8 => 'nine',
            // _getMyPropertyTen
            9 => 'ten',
            // getMyStatic
            10 => 'eleven',
            // getNull
            11 => null,
            // getAnotherGetter
            12 => 'eight',
            // isMyPropertyTwelve
            13 => true,
            // hasMyPropertyThirteen
            14 => false,
            // hasDynamicValue
            15 => true
        ];
        // The last one is missing.
        foreach ($expectations as $key => $result) {
            $this->assertEquals($result, $models[$key]->getData(), 'Count: ' . $key);
        }
    }
}
