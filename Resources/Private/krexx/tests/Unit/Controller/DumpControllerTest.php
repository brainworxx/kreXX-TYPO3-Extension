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
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Routing;
use Brainworxx\Krexx\Controller\BacktraceController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Event;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\OutputNothing;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\Chunks;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(DumpController::class, 'dumpAction')]
#[CoversMethod(BacktraceController::class, 'outputFooter')]
#[CoversMethod(BacktraceController::class, 'outputCssAndJs')]
class DumpControllerTest extends AbstractController
{
    /**
     * Testing of the dump action, with too many calls before.
     */
    public function testBacktraceActionWithMaxCall()
    {
        $dumpController = new DumpController(Krexx::$pool);

        // Add some mox to the mix.
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkMaxCall')
            ->willReturn(true);
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
            ->willReturn('generated HTML code');
        $poolMock->routing = $routingMock;

        $scopeMock = $this->createMock(Scope::class);
        $scopeMock->expects($this->once())
            ->method('setScope')
            ->with($this->callerFinderResult[BacktraceConstInterface::TRACE_VARNAME]);
        $poolMock->scope = $scopeMock;

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
        $this->setValueByReflection('outputService', $outputServiceMock, $dumpController);

        $poolMock->expects($this->exactly(4))
            ->method('createClass')
            ->with(...$this->withConsecutive(
                [Model::class],
                [Model::class],
                [ThroughConfig::class],
                [Model::class]
            ))->willReturnMap(
                [
                    [Model::class, new Model(Krexx::$pool)],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)]
                ]
            );

        $dumpController->dumpAction($fixture);
    }

    /**
     * Testing the use of a log model, without code generation and with an emergency break.
     */
    public function testDumpActionSpecialCases()
    {
        $dumpController = new DumpController(Krexx::$pool);
        $fixture = new \Brainworxx\Krexx\Logging\Model();
        $message = 'Message in a bottle';
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->any())
            ->method('checkEmergencyBreak')
            ->willReturn(true);
        $codeGenMock = $this->createMock(Codegen::class);
        $codeGenMock->expects($this->once())
            ->method('setCodegenAllowed')
            ->with(false);
        $chunkMock = $this->createMock(Chunks::class);
        $chunkMock->expects($this->never())
            ->method('addMetadata');
        Krexx::$pool->emergencyHandler = $emergencyMock;
        Krexx::$pool->codegenHandler = $codeGenMock;
        Krexx::$pool->chunks = $chunkMock;

        $dumpController->dumpAction($fixture, $message);
    }

    /**
     * Test if the outputFooter can handle
     *   - No configuration file
     *   - No minimized JS files
     *   - No minimized CSS files.
     */
    public function testDumpActionWithFooterHandling()
    {
        $dumpController = new DumpController(Krexx::$pool);
        $fixture = null;
        $expectation = Krexx::$pool->messages->getHelp('configFileNotFound');

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->any())
            ->method('fileIsReadable')
            ->willReturn(false);
        Krexx::$pool->fileService = $fileServiceMock;

        $outputService = new OutputNothing(Krexx::$pool);
        $this->setValueByReflection('outputService', $outputService, $dumpController);
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->any())
            ->method('checkEmergencyBreak')
            ->willReturn(false);
        Krexx::$pool->emergencyHandler = $emergencyMock;
        $this->setValueByReflection('jsCssSend', [], $dumpController);

        $dumpController->dumpAction($fixture);

        $result = $outputService->getChunkStrings()[2];
        $this->assertStringContainsString($expectation, $result);
    }
}
