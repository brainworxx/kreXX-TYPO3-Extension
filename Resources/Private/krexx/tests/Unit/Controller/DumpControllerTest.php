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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\View\Output\Browser;

class DumpControllerTest extends AbstractController
{
    /**
     * Testing of the dump action, with too many calls before.
     *
     * @covers \Brainworxx\Krexx\Controller\DumpController::dumpAction
     */
    public function testBacktraceActionWithMaxCall()
    {
        $dumpController = new DumpController(Krexx::$pool);

        // Add some mox to the mix.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkMaxCall')
            ->will($this->returnValue(true));
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $callerFinderMock = $this->createMock(CallerFinder::class);
        $callerFinderMock->expects($this->never())
            ->method('findCaller');
        $this->setValueByReflection('callerFinder', $callerFinderMock, $dumpController);

        $fixture = 'some sting';
        $this->assertEquals($dumpController, $dumpController->dumpAction($fixture));
    }

    /**
     * Testing a simple dump action.
     *
     * @covers \Brainworxx\Krexx\Controller\DumpController::dumpAction
     * @covers \Brainworxx\Krexx\Controller\BacktraceController::outputFooter
     * @covers \Brainworxx\Krexx\Controller\BacktraceController::outputCssAndJs
     */
    public function testDumpAction()
    {
        $dumpController = new DumpController(Krexx::$pool);
        $poolMock = $this->mockMainOutput($dumpController);

        $this->mockFooterHeaderOutput($poolMock);
        $fixture = 'whatever';
        $routingMock = $this->createMock(Routing::class);
        $routingMock->expects($this->once())
            ->method('analysisHub')
            ->will($this->returnValue('generated HTML code'));
        $poolMock->routing = $routingMock;

        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('setScope')
            ->with($this->callerFinderResult[ConstInterface::TRACE_VARNAME]);
        $poolMock->scope = $scopeMock;

        $poolMock->emergencyHandler->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(false));

        $outputServiceMock = $this->createMock(Browser::class);
        $outputServiceMock->expects($this->exactly(3))
            ->method('addChunkString')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->setValueByReflection('outputService', $outputServiceMock, $dumpController);

        $poolMock->expects($this->exactly(3))
            ->method('createClass')
            ->withConsecutive(
                [Model::class],
                [Model::class],
                [ThroughConfig::class]
            )->will($this->returnValueMap(
                [
                    [Model::class, new Model(Krexx::$pool)],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)]
                ]
            ));

        $dumpController->dumpAction($fixture);
    }
}