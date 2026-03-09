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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Analyse\Caller;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Caller\ExceptionCallerFinder;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Logging\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(ExceptionCallerFinder::class, 'findCaller')]
class ExceptionCallerFinderTest extends AbstractHelper
{
    /**
     * Test the return array.
     */
    public function testFindCaller()
    {
        $fixture = new \Exception();
        $callerFinder = new ExceptionCallerFinder(Krexx::$pool);
        $whatever = 'Whatever';
        $result = $callerFinder->findCaller($whatever, $fixture);

        $this->assertEquals(__FILE__, $result[BacktraceConstInterface::TRACE_FILE]);
        $this->assertEquals(54, $result[BacktraceConstInterface::TRACE_LINE]);
        $this->assertEquals(' ' . \Exception::class, $result[BacktraceConstInterface::TRACE_VARNAME]);
        $this->assertEquals('error', $result[BacktraceConstInterface::TRACE_LEVEL]);
        $this->assertEquals(\Exception::class, $result[BacktraceConstInterface::TRACE_TYPE]);
        $this->assertTrue(isset($result[BacktraceConstInterface::TRACE_DATE]));
        $this->assertEquals('n/a', $result[BacktraceConstInterface::TRACE_URL], 'There should be no url on the shell.');

        // Something a little bit differnet with the log model.
        $logModel = new Model();
        $result = $callerFinder->findCaller($whatever, $logModel);
        $this->assertEquals($whatever, $result[BacktraceConstInterface::TRACE_TYPE]);
    }
}