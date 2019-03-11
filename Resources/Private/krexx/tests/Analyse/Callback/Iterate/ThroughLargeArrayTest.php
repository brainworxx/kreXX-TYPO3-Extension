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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class ThroughLargeArrayTest extends AbstractTest
{
    const RENDER_SINGLE_CHILD = 'renderSingleChild';
    /**
     * @var ThroughLargeArray
     */
    protected $throughLargeArray;

    /**
     * @var RoutingNothing
     */
    protected $routingMock;

    /**
     * @var RenderNothing
     */
    protected $renderMock;

    public function setUp()
    {
        parent::setUp();

        // Test start event
        $this->throughLargeArray = new ThroughLargeArray(\Krexx::$pool);
        $eventServiceMock = $this->createMock(Event::class);
        $eventServiceMock->expects($this->exactly(1))
            ->method('dispatch')
            ->withConsecutive(
                ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughLargeArray::callMe::start', $this->throughLargeArray]
            )
            ->will($this->returnValue(''));
        \Krexx::$pool->eventService = $eventServiceMock;

        // Mock the routing class.
        $this->routingMock = new RoutingNothing(\Krexx::$pool);
        \Krexx::$pool->routing = $this->routingMock;

        // Mock the render class.
        $this->renderMock = new RenderNothing(\Krexx::$pool);
        \Krexx::$pool->render = $this->renderMock;
    }

    protected function alwaysRun($fixture)
    {
        // Test if all models got set
        $this->assertTrue(count($this->renderMock->model[static::RENDER_SINGLE_CHILD]) === 2);
        $this->assertTrue(count($this->routingMock->model) === 1);

        // Test the types of the model
        $this->assertEquals('', $this->routingMock->model[0]->getType());
        $this->assertEquals(
            ConstInterface::TYPE_SIMPLE_ARRAY,
            $this->renderMock->model[static::RENDER_SINGLE_CHILD][0]->getType()
        );
        $this->assertEquals(
            ConstInterface::TYPE_SIMPLE_CLASS,
            $this->renderMock->model[static::RENDER_SINGLE_CHILD][1]->getType()
        );

        // Test of the simple type got the right value
        $this->assertEquals($fixture['data']['key1'], $this->routingMock->model[0]->getData());
    }


    /**
     * Testing the iteration through large arrays.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::handleKey
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::handleValue
     */
    public function testCallMeNormal()
    {
        // The fist fixture is not multiline.
        $fixture = [
            'multiline' => false,
            'data' => [
                'key1' => 'some string',
                'key2' => ['some', 'array'],
                'key3' => new \stdClass()
            ]
        ];

        // Run the test
        $this->throughLargeArray->setParams($fixture)
            ->callMe();

        // Test multiline generation
        $this->assertEquals(0, $this->routingMock->model[0]->getMultiLineCodeGen());
        $this->assertEquals(0, $this->renderMock->model[static::RENDER_SINGLE_CHILD][0]->getMultiLineCodeGen());
        $this->assertEquals(0, $this->renderMock->model[static::RENDER_SINGLE_CHILD][1]->getMultiLineCodeGen());

        $this->alwaysRun($fixture);
    }

    /**
     * Testing the iteration through large arrays.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::handleKey
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray::handleValue
     */
    public function testCallMeMultiline()
    {
        // The fist fixture is not multiline.
        $fixture = [
            'multiline' => true,
            'data' => [
                'key1' => 'some string',
                'key2' => ['some', 'array'],
                'key3' => new \stdClass()
            ]
        ];

        // Run the test
        $this->throughLargeArray->setParams($fixture)
            ->callMe();

        // Test multiline generation
        $this->assertEquals(Codegen::ITERATOR_TO_ARRAY, $this->routingMock->model[0]->getMultiLineCodeGen());
        $this->assertEquals(Codegen::ITERATOR_TO_ARRAY, $this->renderMock->model[static::RENDER_SINGLE_CHILD][0]->getMultiLineCodeGen());
        $this->assertEquals(Codegen::ITERATOR_TO_ARRAY, $this->renderMock->model[static::RENDER_SINGLE_CHILD][1]->getMultiLineCodeGen());

        $this->alwaysRun($fixture);
    }
}
