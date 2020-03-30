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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\Krexx;

class ProcessBacktraceTest extends AbstractTest
{

    public function setUp()
    {
        parent::setUp();

        $data = 'data';
        $someFile = 'some file';
        $debugBacktrace = $this->getFunctionMock('\\Brainworxx\\Krexx\\Analyse\\Routing\\Process\\', 'debug_backtrace');
        $debugBacktrace->expects($this->any())
            ->will($this->returnValue(
                [
                    [
                        ConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'blargh',
                        $data => 'Step 1',
                    ],
                    [
                        ConstInterface::TRACE_FILE => $someFile,
                        $data => 'Step 2',
                    ],
                    [
                        ConstInterface::TRACE_FILE => $someFile,
                        $data => 'Step 3',
                    ],
                    [
                        ConstInterface::TRACE_FILE => KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'whatever',
                        $data => 'Step 4',
                    ],
                ]
            ));
    }

    /**
     * Test the setting of the pool.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace::__construct
     */
    public function testConstruct()
    {
        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $processBacktrace));
    }

    /**
     * Create a mock backtrace, and see if it is processed.
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace::handle
     */
    public function testProcessNormal()
    {
        $this->mockEmergencyHandler();

        // Inject the RenderNothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        // Create an array and name it a backtrace
        $fixture = [
            'Step 1',
            'Step 2',
            'Step 3',
            'Step 4',
            'Step 5',
            'Step 6',
            'Step 7',
            'Step 8',
            'Step 9',
            'Step 10',
            'Step 11',
            'Step 12',
        ];
        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $processBacktrace->handle($fixture);

        /** @var \Brainworxx\Krexx\View\Message $message */
        $message = Krexx::$pool->messages->getMessages()['omittedBacktrace'];
        $this->assertEquals('omittedBacktrace', $message->getKey(), 'Check messages for omitted steps');
        $this->assertEquals([0 => 11, 1 => 12], $message->getArguments(), 'Check messages for omitted steps');

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
     *
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace::handle
     * @covers \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBacktrace::getBacktrace
     */
    public function testProcessEmpty()
    {
        $this->mockEmergencyHandler();

        // Inject the RenderNothing.
        $renderNothing = new RenderNothing(Krexx::$pool);
        Krexx::$pool->render = $renderNothing;

        $processBacktrace = new ProcessBacktrace(Krexx::$pool);
        $processBacktrace->handle();

        $this->assertEquals(
            [],
            Krexx::$pool->messages->getMessages(),
            'Messages should be empty, because we have not enough steps.'
        );

        // Check the parameters
        $data = 'data';
        $someFile = 'some file';
        for ($i = 0; $i <= 2; $i++) {
            /** @var \Brainworxx\Krexx\Analyse\Model $model */
            $model = $renderNothing->model['renderExpandableChild'][$i];

            if ($i === 2) {
                $someFile = KREXX_DIR . 'src' . DIRECTORY_SEPARATOR . 'whatever';
            }

            $this->assertEquals(
                [
                    ConstInterface::TRACE_FILE => $someFile,
                    $data => 'Step ' . ($i + 2),
                ],
                $model->getParameters()[CallbackCounter::PARAM_DATA],
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
}
