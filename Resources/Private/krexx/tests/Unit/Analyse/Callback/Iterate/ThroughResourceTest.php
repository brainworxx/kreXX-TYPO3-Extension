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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResource;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use Brainworxx\Krexx\Krexx;

class ThroughResourceTest extends AbstractHelper
{
    public const  SOME_KEY = 'some_key';
    public const  SOME_VALUE = 'some_value';
    public const  ANOTHER_KEY = 'another key';
    public const  ANOTHER_VALUE = 'another value';
    public const  SOME_ARRAY = 'array';


    /**
     * Testing the analysis of a resource stream.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResource::callMe
     */
    public function testCallMe()
    {
        $throughResourceStream = new ThroughResource(Krexx::$pool);
        // Test start event.
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughResource::callMe::start', $throughResourceStream]
        );

        // Inject the nothing route
        $routeNothing = new RoutingNothing(Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;
        $this->mockEmergencyHandler();

        // Create a fixture.
        // The callback simply is iterating through an already existing
        // analysis, comming from stream_get_meta_data or curl_getinfo.
        // The only difference to an array analysis is the forbidden code
        // generation and the prettification of the array keys.
        $fixture = [
            ThroughResource::PARAM_DATA => [
                static::SOME_KEY => static::SOME_VALUE,
                static::ANOTHER_KEY => static::ANOTHER_VALUE,
                static::SOME_ARRAY => [
                    'deep' => 'stuff'
                ]
            ]
        ];

        // Run the test.
        $throughResourceStream
            ->setParameters($fixture)
            ->callMe();

        $models = $routeNothing->model;

        $this->assertEquals(static::SOME_VALUE, $models[0]->getData());
        $this->assertEquals(static::SOME_VALUE, $models[0]->getNormal());
        $this->assertEquals('some key', $models[0]->getName());

        $this->assertEquals(static::ANOTHER_VALUE, $models[1]->getData());
        $this->assertEquals(static::ANOTHER_VALUE, $models[1]->getNormal());
        $this->assertEquals(static::ANOTHER_KEY, $models[1]->getName());

        $this->assertEquals($fixture[ThroughResource::PARAM_DATA][static::SOME_ARRAY], $models[2]->getData());
        $this->assertEquals($fixture[ThroughResource::PARAM_DATA][static::SOME_ARRAY], $models[2]->getNormal());
        $this->assertEquals(static::SOME_ARRAY, $models[2]->getName());
    }
}
