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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Service\Plugin\Registration;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ProcessNothing;
use Brainworxx\Krexx\Krexx;

class BacktraceStepTest extends AbstractTest
{
    /**
     * Getting some test data and preventing deeper processing.
     */
    protected function krexxUp()
    {
        // We overwrite all processing classes with the processNothing class.
        // This way we can prevent going too deep inside the rabbit hole.
        Registration::addRewrite(ProcessArray::class, ProcessNothing::class);
        Registration::addRewrite(ProcessObject::class, ProcessNothing::class);

        parent::krexxUp();

        $this->mockEmergencyHandler();
    }

    /**
     * Testing, if all events got fired.
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep::callMe
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep::lineToOutput
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep::outputProcessor
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep::outputSingleChild
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\BacktraceStep::retrieveSource
     * @covers \Brainworxx\Krexx\Analyse\Callback\AbstractCallback::dispatchStartEvent
     * @covers \Brainworxx\Krexx\Analyse\Callback\AbstractCallback::dispatchEventWithModel
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
}
