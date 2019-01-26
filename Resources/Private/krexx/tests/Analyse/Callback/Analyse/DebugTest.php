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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Debug;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class DebugTest extends AbstractTest
{
    /**
     * Testing if the debug method output gets routed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Debug
     */
    public function testCallMe()
    {
        $debug = new Debug(\Krexx::$pool);
        $data = ['data' => 'just some string'];
        $debug->setParams($data);

        // Test if start event has fired
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Debug::callMe::start', $debug],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Debug::analysisEnd', $debug]
        );

        // Test and prevent the routing of out test data.
        $routingMock = $this->createMock(Routing::class);
        $routingMock->expects($this->once())
            ->method('analysisHub')
            ->with($this->anything())
            ->will($this->returnValue('some markup'));
        \Krexx::$pool->routing = $routingMock;

        // Run the test.
        $debug->callMe();
    }
}
