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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Service\Flow\Recursion;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class ThroughArrayTest extends AbstractTest
{
    /**
     * Injecting the routing nothing class.
     */
    public function setUp()
    {
        parent::setUp();
        \Krexx::$pool->routing = new RoutingNothing(\Krexx::$pool);
    }

    /**
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray::callMe
     */
    public function testCallMe()
    {
        // Listen for the start event.
        $throughArray = new ThroughArray(\Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray::callMe::start', $throughArray]
        );

        // Prepare a fixture with recursion marker
        $recursionHandler = $this->createMock(Recursion::class);
        $recursionHandler->expects($this->once())
            ->method('getMarker')
            ->will($this->returnValue('recursion marker'));
        \Krexx::$pool->recursionHandler = $recursionHandler;


        $fixture = [
           'multiline' => true,
           'data' => [
               0 => 'some value',
               'recursion marker' => true,
               'one' => new \stdClass()
           ]
        ];

        // Run the test.
        $throughArray->setParams($fixture)
            ->callMe();

        // Check the result
        $models = \Krexx::$pool->routing->model;
        $this->assertEquals(2, count($models));

        // Test for multiline
        $this->assertEquals(1, $models[0]->getMultiLineCodeGen());
        $this->assertEquals(1, $models[1]->getMultiLineCodeGen());
        // Test the names
        $this->assertEquals(0, $models[0]->getName());
        $this->assertEquals('one', $models[1]->getName());
        // Test the connectors.
        $this->assertEquals('[', $models[0]->getConnectorLeft());
        $this->assertEquals(']', $models[0]->getConnectorRight());
        $this->assertEquals('[\'', $models[1]->getConnectorLeft());
        $this->assertEquals('\']', $models[1]->getConnectorRight());
    }
}
