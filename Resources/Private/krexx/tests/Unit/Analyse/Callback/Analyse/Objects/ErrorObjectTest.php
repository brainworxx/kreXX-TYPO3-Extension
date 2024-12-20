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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ErrorObject;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\View\Messages;
use Exception;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ErrorObject::class, 'callMe')]
#[CoversMethod(ErrorObject::class, 'renderBacktrace')]
#[CoversMethod(ErrorObject::class, 'addExceptionMessage')]
#[CoversMethod(AbstractCallback::class, 'dispatchStartEvent')]
#[CoversMethod(AbstractCallback::class, 'dispatchEventWithModel')]
class ErrorObjectTest extends AbstractHelper
{
    /**
     * @var \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\ErrorObject
     */
    protected $errorObject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->errorObject = new ErrorObject(Krexx::$pool);
        Krexx::$pool->rewrite = [
            ProcessBacktrace::class => CallbackCounter::class
        ];
    }

    /**
     * Test with a real error object.
     */
    public function testCallMe()
    {
        $this->mockEmergencyHandler();

        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\ErrorObject::callMe::start', $this->errorObject],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\ErrorObject::backtrace',  $this->errorObject],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\ErrorObject::source',  $this->errorObject]
        );

        $backtrace = ['some backtrace'];
        $line = 123;
        $file = 'some file';
        $code = 'some code';

        $exception = new Exception('Lorem ipsum dolor sit amet, in ubique everti vituperatoribus pro, ipsum oporteat consectetuer vel ne. Choro saepe tollit cu sit, mazim aperiri ex vim. Ne sit noluisse mnesarchum, eos verear vidisse aperiam ne. Ius an nostrud sensibus comprehensam. Vim dico tractatos te. Ne lorem persius per.');
        $this->setValueByReflection('trace', $backtrace, $exception);
        $this->setValueByReflection('line', $line, $exception);
        $this->setValueByReflection('file', $file, $exception);

        $codegenMock = $this->createMock(Codegen::class);
        $codegenMock->expects($this->exactly(2))
            ->method('setCodegenAllowed')
            ->with(...$this->withConsecutive(
                [false],
                [true]
            ));
        $codegenMock->expects($this->exactly(2))
            ->method('generateSource')
            ->willReturn('');
        Krexx::$pool->codegenHandler = $codegenMock;

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->once())
            ->method('readSourcecode')
            ->with($file, ($line - 1), ($line - 6), ($line + 4))
            ->willReturn($code);
        Krexx::$pool->fileService = $fileServiceMock;

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('addMessage')
            ->with(
                'exceptionText',
                [get_class($exception), 'Lorem ipsum dolor sit amet, in ubique everti vituperatoribus pro, ipsum opo ...'],
                true
            );
        Krexx::$pool->messages = $messageMock;

        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('getNestingLevel')
            ->willReturn(1);

        $fixture = [
            $this->errorObject::PARAM_DATA => $exception
        ];

        $this->errorObject->setParameters($fixture)->callMe();
        $this->assertEquals($backtrace, CallbackCounter::$staticParameters[0][$this->errorObject::PARAM_DATA]);
    }

    /**
     * Do not display the error message, when we are deeper into the object hive.
     */
    public function testCallMeNested()
    {
        $this->mockEmergencyHandler();

        $exception = new Exception('This is exceptional!');

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->never())
            ->method('addMessage');
        Krexx::$pool->messages = $messageMock;

        Krexx::$pool->emergencyHandler->expects($this->once())
            ->method('getNestingLevel')
            ->willReturn(2);

        $fixture = [
            $this->errorObject::PARAM_DATA => $exception
        ];
        $this->errorObject->setParameters($fixture)->callMe();
    }
}
