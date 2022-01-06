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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Caller\ExceptionCallerFinder;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Controller\ExceptionController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\View\Output\Chunks;
use Exception;

class ExceptionControllerTest extends AbstractController
{
    /**
     * Testing the exception action, like the method name says.
     *
     * @covers \Brainworxx\Krexx\Controller\ExceptionController::exceptionAction
     */
    public function testExceptionAction()
    {
        $this->callerFinderResult[BacktraceConstInterface::TRACE_VARNAME] = ' Exception';
        $this->callerFinderResult[BacktraceConstInterface::TRACE_LEVEL] = 'error';
        $this->callerFinderResult[BacktraceConstInterface::TRACE_TYPE] = 'Exception';
        $this->callerFinderResult[BacktraceConstInterface::TRACE_DATE] = date('d-m-Y H:i:s', 1111);
        $this->callerFinderResult[BacktraceConstInterface::TRACE_URL] = 'n/a';
        $timeMock = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Caller', 'time');
        $timeMock->expects($this->once())
            ->will($this->returnValue(1111));

        // One can not simply mock an exception.
        // But we can adjust its values.
        $fixture = new Exception();
        $this->setValueByReflection('message', 'some string', $fixture);
        $this->setValueByReflection('file', 'just another path', $fixture);
        $this->setValueByReflection('line', 40, $fixture);
        $this->setValueByReflection('trace', ['some backtrace'], $fixture);

        $exceptionController = new ExceptionController(Krexx::$pool);
        $poolMock = $this->mockMainOutput($exceptionController);
        $this->mockFooterHeaderOutput($poolMock);

        $backtraceMock = $this->createMock(ProcessBacktrace::class);
        $backtraceMock->expects($this->once())
            ->method('handle')
            ->with(['some backtrace'])
            ->will($this->returnValue('HTML code'));

        $poolMock->expects($this->exactly(4))
            ->method('createClass')
            ->withConsecutive(
                [ProcessBacktrace::class],
                [ExceptionCallerFinder::class],
                [Model::class],
                [ThroughConfig::class]
            )->will($this->returnValueMap(
                [
                    [ProcessBacktrace::class, $backtraceMock],
                    [ExceptionCallerFinder::class, new ExceptionCallerFinder(Krexx::$pool)],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)],
                    [Model::class, new Model(Krexx::$pool)]
                ]
            ));

        $exceptionController->exceptionAction($fixture);
    }

    /**
     * Testing the singleton handling of the register action.
     *
     * @covers \Brainworxx\Krexx\Controller\ExceptionController::registerAction
     */
    public function testRegisterAction()
    {
        $exceptionController = new ExceptionController(Krexx::$pool);
        $setExceptionHandler = $this->getFunctionMock('\\Brainworxx\\Krexx\\Controller\\', 'set_exception_handler');
        $setExceptionHandler->expects($this->exactly(2))
            ->with([$exceptionController, 'exceptionAction']);

        $exceptionController->registerAction();
        $newExceptionHandler = new ExceptionController(Krexx::$pool);
        $newExceptionHandler->registerAction();
    }

    /**
     * Testing the callingh of the unregistering.
     *
     * @covers \Brainworxx\Krexx\Controller\ExceptionController::unregisterAction
     */
    public function testUnregisterAction()
    {
        $restoreExceptionHandler = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Controller\\',
            'restore_exception_handler'
        );
        $restoreExceptionHandler->expects($this->once());

        $exceptionController = new ExceptionController(Krexx::$pool);
        $exceptionController->unregisterAction();
    }

    /**
     * Adjusted version. The Error Controller does things slightly different.
     *
     * @param \Brainworxx\Krexx\Controller\AbstractController $controller
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockMainOutput(\Brainworxx\Krexx\Controller\AbstractController $controller)
    {
        $this->setValueByReflection('jsCssSend', [], $controller);

        $poolMock = $this->createMock(Pool::class);
        $this->setValueByReflection('pool', $poolMock, $controller);

        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('detectEncoding')
            ->with('HTML code');
        $chunksMock->expects($this->once())
            ->method('addMetadata')
            ->with($this->callerFinderResult);
        $poolMock->chunks = $chunksMock;

        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkEmergencyBreak')
            ->will($this->returnValue(false));
        $poolMock->emergencyHandler = $emergencyMock;

        $renderNothing = new RenderNothing(Krexx::$pool);
        $poolMock->render = $renderNothing;

        return $poolMock;
    }
}
