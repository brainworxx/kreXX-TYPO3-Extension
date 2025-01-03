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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Controller\BacktraceController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\Chunks;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(BacktraceController::class, 'backtraceAction')]
#[CoversMethod(BacktraceController::class, 'outputFooter')]
#[CoversMethod(BacktraceController::class, 'outputCssAndJs')]
class BacktraceControllerTest extends AbstractController
{
    /**
     * Testing of the backtrace action, with too many calls before.
     */
    public function testBacktraceActionWithMaxCall()
    {
        $backtraceController = new BacktraceController(Krexx::$pool);

        // Add some mox to the mix.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkMaxCall')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $callerFinderMock = $this->createMock(CallerFinder::class);
        $callerFinderMock->expects($this->never())
            ->method('findCaller');
        $this->setValueByReflection('callerFinder', $callerFinderMock, $backtraceController);

        $this->assertEquals($backtraceController, $backtraceController->backtraceAction());
    }

    /**
     * Test it with a triggered emergency break.
     */
    public function testBacktraceActionWithEmergency()
    {
        $backtraceController = new BacktraceController(Krexx::$pool);

        // Mix it, this time with a different kind of mox.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkMaxCall')
            ->willReturn(false);
        $emergencyMock->expects($this->any())
            ->method('checkEmergencyBreak')
            ->willReturn(true);
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->never())
            ->method('addMetadata');
        Krexx::$pool->chunks = $chunksMock;

        $this->assertEquals($backtraceController, $backtraceController->backtraceAction());
    }

    /**
     * Testing a simple backtrace.
     */
    public function testBacktraceAction()
    {
        $this->callerFinderResult = [BacktraceConstInterface::TRACE_LEVEL => 'backtrace'];

        $backtraceController = new BacktraceController(Krexx::$pool);
        $poolMock = $this->mockMainOutput($backtraceController);

        $proccessMock = $this->createMock(ProcessBacktrace::class);
        $proccessMock->expects($this->once())
            ->method('handle')
            ->with(null)
            ->willReturn('generated HTML code');

        $poolMock->codegenHandler = $this->createMock(Codegen::class);
        $poolMock->codegenHandler->expects($this->once())
            ->method('setCodegenAllowed')
            ->with(false);

        $poolMock->emergencyHandler->expects($this->once())
            ->method('checkEmergencyBreak')
            ->willReturn(false);

        $poolMock->eventService = $this->createMock(Event::class);
        $poolMock->eventService->expects($this->once())
            ->method('dispatch');

        $outputServiceMock = $this->createMock(Browser::class);
        $outputServiceMock->expects($this->exactly(3))
            ->method('addChunkString')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->setValueByReflection('outputService', $outputServiceMock, $backtraceController);

        $poolMock->expects($this->exactly(4))
            ->method('createClass')
            ->with(...$this->withConsecutive(
                [ProcessBacktrace::class],
                [Model::class],
                [ThroughConfig::class],
                [Model::class]
            ))->willReturnMap(
                [
                    [ProcessBacktrace::class, $proccessMock],
                    [Model::class, new Model(Krexx::$pool)],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)]
                ]
            );

        $this->mockFooterHeaderOutput($poolMock);
        $backtraceController->backtraceAction();
    }
}
