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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ProcessNothing;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(BacktraceStep::class, 'callMe')]
#[CoversMethod(BacktraceStep::class, 'lineToOutput')]
#[CoversMethod(BacktraceStep::class, 'outputProcessor')]
#[CoversMethod(BacktraceStep::class, 'outputSingleChild')]
#[CoversMethod(BacktraceStep::class, 'retrieveSource')]
#[CoversMethod(AbstractCallback::class, 'dispatchStartEvent')]
#[CoversMethod(AbstractCallback::class, 'dispatchEventWithModel')]
class BacktraceStepTest extends AbstractHelper
{
    /**
     * Getting some test data and preventing deeper processing.
     */
    protected function setUp(): void
    {
        // We overwrite all processing classes with the processNothing class.
        // This way we can prevent going too deep inside the rabbit hole.
        Registration::addRewrite(ProcessArray::class, ProcessNothing::class);
        Registration::addRewrite(ProcessObject::class, ProcessNothing::class);

        parent::setUp();

        $this->mockEmergencyHandler();
    }

    /**
     * Testing, if all events got fired.
     */
    public function testCallMe()
    {
        $backtraceStep = new BacktraceStep(Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::callMe::start', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::fileToOutput::end', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::lineToOutput::end', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::objectToOutput::end', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::typeToOutput::end', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::functionToOutput::end', $backtraceStep],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\BacktraceStep::argsToOutput::end', $backtraceStep]
        );

        $singleStep = ['data' => debug_backtrace()[5]];
        $backtraceStep->setParameters($singleStep);
        $backtraceStep->callMe();
    }

    /**
     * Test everything, but some data is missing.
     */
    public function testCallMeEmpty()
    {
        $backtraceStep = new BacktraceStep(Krexx::$pool);
        $singleStep = ['data' => debug_backtrace()[5]];

        unset($singleStep['data'][BacktraceConstInterface::TRACE_LINE]);
        unset($singleStep['data'][BacktraceConstInterface::TRACE_OBJECT]);
        unset($singleStep['data'][BacktraceConstInterface::TRACE_FUNCTION]);
        $backtraceStep->setParameters($singleStep);
        $result = $backtraceStep->callMe();

        $this->assertStringContainsString(
            Krexx::$pool->messages->getHelp('noSourceAvailable'),
            $result
        );
    }
}
