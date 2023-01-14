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

namespace Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\Callbacks;

use Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughClassList;
use Brainworxx\Includekrexx\Tests\Unit\Plugins\AimeosDebugger\AimeosTestTrait;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\PluginConfigInterface;
use Brainworxx\Krexx\Tests\Fixtures\SimpleFixture;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RoutingNothing;
use StdClass;

class ThroughClassListTest extends AbstractTest
{
    use AimeosTestTrait;

    /**
     * Test the passing of a bunch of objects into the analysis hub.
     *
     * @covers \Brainworxx\Includekrexx\Plugins\AimeosDebugger\Callbacks\ThroughClassList::callMe
     */
    public function testCallMe()
    {
        $this->skipIfAimeosIsNotInstalled();

        $routeNothing = new RoutingNothing(Krexx::$pool);
        Krexx::$pool->routing = $routeNothing;

        $throughClassList = new ThroughClassList(Krexx::$pool);
        $this->mockEventService(
            [ThroughClassList::class . PluginConfigInterface::START_EVENT, $throughClassList],
            [ThroughClassList::class . '::' . CallbackConstInterface::EVENT_MARKER_ANALYSES_END, $throughClassList],
            [ThroughClassList::class . '::' . CallbackConstInterface::EVENT_MARKER_ANALYSES_END, $throughClassList],
            [ThroughClassList::class . '::' . CallbackConstInterface::EVENT_MARKER_ANALYSES_END, $throughClassList]
        );
        $key1 = 'class 1';
        $key2 = 'class 2';
        $key3 = 'class 3';
        $fixture = [
            CallbackConstInterface::PARAM_DATA => [
                $key1 => new StdClass(),
                $key2 => new SimpleFixture(),
                $key3 => 'new FirstClass()'
            ]
        ];

        // Retrieving the models, and asserting them.
        $throughClassList->setParameters($fixture)->callMe();
        $this->assertSame($fixture[CallbackConstInterface::PARAM_DATA][$key1], $routeNothing->model[0]->getData());
        $this->assertEquals($key1, $routeNothing->model[0]->getName());
        $this->assertSame($fixture[CallbackConstInterface::PARAM_DATA][$key2], $routeNothing->model[1]->getData());
        $this->assertEquals($key2, $routeNothing->model[1]->getName());
        $this->assertSame($fixture[CallbackConstInterface::PARAM_DATA][$key3], $routeNothing->model[2]->getData());
        $this->assertEquals($key3, $routeNothing->model[2]->getName());
    }
}
