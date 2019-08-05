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

namespace Brainworxx\Krexx\Tests\Controller;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Controller\ErrorController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;

class ErrorControllerTest extends AbstractController
{
    /**
     * @covers \Brainworxx\Krexx\Controller\ErrorController::errorAction
     */
    public function testErrorAction()
    {
        $this->callerFinderResult[ConstInterface::TRACE_VARNAME] = ' Fatal Error';
        unset($this->callerFinderResult[ConstInterface::TRACE_TYPE]);

        $fixture = [
            ErrorController::TRACE_TYPE => 'Fatal Error',
            ErrorController::TRACE_ERROR_STRING => 'some string',
            ErrorController::TRACE_ERROR_FILE => 'just another path',
            ErrorController::TRACE_ERROR_LINE => 40,
            ErrorController::TRACE_BACKTRACE => ['some backtrace']
        ];

        $errorController = new ErrorController(Krexx::$pool);
        $poolMock = $this->mockMainOutput($errorController);
        $this->mockFooterHeaderOutput($poolMock);

        $backtraceMock = $this->createMock(ProcessBacktrace::class);
        $backtraceMock->expects($this->once())
            ->method('process')
            ->with($fixture[ErrorController::TRACE_BACKTRACE ])
            ->will($this->returnValue('HTML code'));

        $poolMock->expects($this->exactly(3))
            ->method('createClass')
            ->withConsecutive(
                [ProcessBacktrace::class],
                [Model::class],
                [ThroughConfig::class]

            )->will($this->returnValueMap(
                [
                    [ProcessBacktrace::class, $backtraceMock],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)],
                    [Model::class, new Model(Krexx::$pool)]
                ]
            ));

        $poolMock->render->setFatalMain('generated ');
        $errorController->errorAction($fixture);
    }

    /**
     * Adjusted version. The Error Controller does things slightly different.
     *
     * @param \Brainworxx\Krexx\Controller\AbstractController $controller
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    protected function mockMainOutput(\Brainworxx\Krexx\Controller\AbstractController $controller)
    {
        $this->setValueByReflection('jsCssSend', [], $controller);

        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('reset');
        $this->setValueByReflection('pool', $poolMock, $controller);

        $chunksMock = $this->createMock(Chunks::class);
        $chunksMock->expects($this->once())
            ->method('detectEncoding')
            ->with('generated HTML code');
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

    /**
     * Testing the registering of a fatal error handler.
     *
     * Test with 'simulated' wrong php version
     *
     * @covers \Brainworxx\Krexx\Controller\ErrorController::registerFatalAction
     */
    public function testRegisterFatalActionWrongPhpVersion()
    {
        // Short circuting the krexx command in the fatal error handler.
        $krexxShorthand = $this->getFunctionMock('\\Brainworxx\\Krexx\\Controller\\', 'krexx');
        $krexxShorthand->expects($this->once());
        
        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->exactly(2))
            ->method('getHelp')
            ->withConsecutive(['php7yellow'], ['php7'])
            ->will($this->returnValueMap([
                ['php7yellow', [], 'yellow php 7 error'],
                ['php7', [], 'php 7 error']
            ]));
        $messageMock->expects(($this->once()))
            ->method('addMessage')
            ->with('yellow php 7 error');

        Krexx::$pool->messages = $messageMock;

        $controller = new ErrorController(Krexx::$pool);
        $controller->registerFatalAction();

        // We will *not* test the rest of the error handler.
        // PHP 5 is eol, and so is the tick-implementation of
        // this error handler.
    }
}