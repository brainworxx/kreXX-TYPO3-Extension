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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Tests\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;

class ThroughConstantsTest extends AbstractTest
{
    /**
     * Testing the constants iterator.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants::callMe
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();

        // Inject route nothing
        \Krexx::$pool->routing = new RoutingNothing(\Krexx::$pool);

        // Create a fixture
        $fixture = [
            'data' => [
                'CONST_1' => 'some value',
                'CONST_2' => 'more values',
                'CONST_3' => 'string',
                'CONST_4' => 21
            ],
            'classname' => 'first class'
        ];

        // Listen for the start event.
        $throughConstants = new ThroughConstants(\Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConstants::callMe::start', $throughConstants]
        );

        // Run the tests
        $throughConstants->setParams($fixture)->callMe();

        // Check the models from the route nothing
        $count = 0;
        $models = \Krexx::$pool->routing->model;

        foreach ($fixture['data'] as $name => $value) {
            $this->assertEquals($name, $models[$count]->getName());
            $this->assertEquals($value, $models[$count]->getData());
            $this->assertEquals($fixture['classname'] . '::', $models[$count]->getConnectorLeft());
            ++$count;
        }
    }
}
