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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;
use stdClass;

class ThroughArrayTest extends AbstractTest
{
    /**
     * Injecting the routing nothing class.
     */
    public function setUp()
    {
        parent::setUp();
        Krexx::$pool->routing = new RoutingNothing(Krexx::$pool);
    }

    /**
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray::callMe
     */
    public function testCallMe()
    {
        // Listen for the start event.
        $throughArray = new ThroughArray(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray::callMe::start', $throughArray]
        );

        // Prepare a fixture with recursion marker
        $recursionHandler = $this->createMock(Recursion::class);
        $recursionHandler->expects($this->once())
            ->method('getMarker')
            ->will($this->returnValue('recursion marker'));
        Krexx::$pool->recursionHandler = $recursionHandler;


        $fixture = [
           'multiline' => true,
           'data' => [
               0 => 'some value',
               'recursion marker' => true,
               'one' => new stdClass()
           ]
        ];

        // Run the test.
        $throughArray->setParameters($fixture)
            ->callMe();

        // Check the result
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = Krexx::$pool->routing->model;
        $this->assertEquals(2, count($models));

        // Test for multiline
        $this->assertEquals(Codegen::CODEGEN_TYPE_ITERATOR_TO_ARRAY, $models[0]->getCodeGenType());
        $this->assertEquals(Codegen::CODEGEN_TYPE_ITERATOR_TO_ARRAY, $models[1]->getCodeGenType());
        // Test the names
        $this->assertEmpty($models[0]->getName());
        $this->assertEquals('one', $models[1]->getName());
        // Test the connectors.
        $this->assertEquals('[', $models[0]->getConnectorLeft());
        $this->assertEquals(']', $models[0]->getConnectorRight());
        $this->assertEquals('[\'', $models[1]->getConnectorLeft());
        $this->assertEquals('\']', $models[1]->getConnectorRight());
    }

    /**
     * Testing the special handling of a PHP bug.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray::callMe
     */
    public function testCallMeInaccessibleArray()
    {
        // Listen for the start event.
        $throughArray = new ThroughArray(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray::callMe::start', $throughArray]
        );

        $arrayCast = new stdClass();
        $arrayCast->{5} = 'hidden';
        $fixture = [
            'multiline' => false,
            'data' => (array) $arrayCast
        ];

        // Run the test.
        $throughArray->setParameters($fixture)
            ->callMe();

        // Check the result
        /** @var \Brainworxx\Krexx\Analyse\Model[] $models */
        $models = Krexx::$pool->routing->model;
        $this->assertEquals(1, count($models));
        // This bug may or may not be fixed on the used PHP version.
        // Hence, we need to test it
        if (array_key_exists(5, $fixture['data'])) {
            $this->assertEquals(Codegen::CODEGEN_TYPE_PUBLIC, $models[0]->getCodeGenType());
        } else {
            $this->assertEquals(Codegen::CODEGEN_TYPE_ARRAY_VALUES_ACCESS, $models[0]->getCodeGenType());
        }
        $this->assertEquals(5, $models[0]->getName());
    }
}
