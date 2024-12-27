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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ProcessBacktrace::class, '__construct')]
#[CoversMethod(ProcessBacktrace::class, 'callMe')]
#[CoversMethod(ProcessBacktrace::class, 'handle')]
#[CoversMethod(ProcessBacktrace::class, 'getBacktrace')]
class ProcessBacktraceTest extends AbstractHelper
{
    /**
     * Mock the debug backtrace, to provide a fixture.
     */
    protected function mockDebugBacktrace()
    {
        $data = 'data';
        $someFile = 'some file';
        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'debug_backtrace');
        $debugBacktrace->expects($this->any())
            ->willReturn(
                [
                    [
                        BacktraceConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'blargh',
                        $data => 'Step 1',
                    ],
                    [
                        BacktraceConstInterface::TRACE_FILE => $someFile,
                        $data => 'Step 2',
                    ],
                    [
                        BacktraceConstInterface::TRACE_FILE => $someFile,
                        $data => 'Step 3',
                    ],
                    [
                        BacktraceConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'whatever',
                        $data => 'Step 4',
                    ],
                ]
            );
    }

    /**
     * Test the setting of the pool.
     */
    public function testConstruct()
    {
        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $processBacktrace));
    }

    /**
     * Create a mock backtrace, and see if it is processed.
     */
    public function testProcessNormal()
    {
        $this->mockEmergencyHandler();
        $this->mockDebugBacktrace();

        // Inject the RenderNothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Create an array and name it a backtrace
        $fixture = [
            ['Step 1'],
            ['Step 2'],
            ['Step 3'],
            ['Step 4'],
            ['Step 5'],
            ['Step 6'],
            ['Step 7'],
            ['Step 8'],
            ['Step 9'],
            ['Step 10'],
            ['Step 11'],
            ['Step 12'],
            ['Step 13'],
            ['Step 14'],
            ['Step 15'],
            ['Step 16'],
            ['Step 17'],
        ];
        $parameters = [ProcessBacktrace::PARAM_DATA => $fixture];

        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $processBacktrace->setParameters($parameters)->callMe();

        $message = Krexx::$pool->messages->getMessages()['omittedBacktrace'];
        $this->assertEquals('omittedBacktrace', $message->getKey(), 'Check messages for omitted steps');
        $this->assertEquals([0 => 16, 1 => 17], $message->getArguments(), 'Check messages for omitted steps');

        // Check the parameters
        // The standatd value is 10.
        for ($i = 0; $i <= 9; $i++) {
            /** @var \Brainworxx\Krexx\Analyse\Model $model */
            $model = $renderNothing->model['renderExpandableChild'][$i];

            $this->assertEquals(
                $fixture[$i],
                $model->getParameters()[CallbackCounter::PARAM_DATA]
            );

            $this->assertEquals(
                CallbackCounter::TYPE_STACK_FRAME,
                $model->getType(),
                'Asserting the type type of a backtrace step.'
            );
            $this->assertEquals(
                $i + 1,
                $model->getName(),
                'The name is the step number, starting with 1'
            );
        }
    }

    /**
     * Testing the backtrace processing, without a backtrace.
     */
    public function testProcessEmpty()
    {
        $this->mockEmergencyHandler();
        $this->mockDebugBacktrace();

        // Inject the RenderNothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Prepare the "docroot".
        $this->setValueByReflection(
            'docRoot',
            trim(KREXX_DIR, DIRECTORY_SEPARATOR),
            Krexx::$pool->fileService
        );

        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $processBacktrace->handle();

        $this->assertEquals(
            [],
            Krexx::$pool->messages->getMessages(),
            'Messages should be empty, because we have not enough steps.'
        );

        // Check the parameters
        $data = 'data';
        $orgPath = 'some file';
        for ($i = 0; $i <= 2; $i++) {
            /** @var \Brainworxx\Krexx\Analyse\Model $model */
            $model = $renderNothing->model['renderExpandableChild'][$i];

            if ($i === 2) {
                $orgPath = KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'whatever';
            }

            $result = $model->getParameters()[CallbackCounter::PARAM_DATA];
            $this->assertEquals(
                [
                    BacktraceConstInterface::TRACE_FILE => $orgPath,
                    $data => 'Step ' . ($i + 2),
                ],
                $result,
                'Checking the steps, the first one should be omitted.'
            );

            $this->assertEquals(
                CallbackCounter::TYPE_STACK_FRAME,
                $model->getType(),
                'Asserting the type type of a backtrace step.'
            );
            $this->assertEquals(
                $i + 1,
                $model->getName(),
                'The name is the step number, starting with 1'
            );
        }
    }

    /**
     * We mock the debug_backtrace, and make it return an empty value to
     * simulate a tiny backtrace.
     */
    public function testProcessReallyEmpty()
    {
        $this->mockEmergencyHandler();

        $backtraceMock = $this->getFunctionMock(
            '\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\',
            'debug_backtrace'
        );
        $backtraceMock->expects($this->once())
            ->willReturn([]);

        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $this->assertEquals('', $processBacktrace->handle());
    }
}
