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

use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\File;

class AbstractControllerTest extends AbstractTest
{
    /**
     * Testing the construction phase of the controller
     *
     * @covers \Brainworxx\Krexx\Controller\AbstractController::__construct
     */
    public function testConstruct()
    {
        // Mock the settings.
        $fileMock = $this->createMock(Config::class);
        $fileMock->expects($this->once())
            ->method('getSetting')
            ->will($this->returnValue(Fallback::VALUE_FILE));
        $browserMock = $this->createMock(Config::class);
        $browserMock->expects($this->once())
            ->method('getSetting')
            ->will($this->returnValue(Fallback::VALUE_BROWSER));

        // Test the file output
        Krexx::$pool->config = $fileMock;
        $oldRecursionHandler = Krexx::$pool->recursionHandler;
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertNotSame($oldRecursionHandler, Krexx::$pool->recursionHandler, 'Test the resetting of the pool');
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $dumpController));
        $this->assertInstanceOf(File::class, $this->retrieveValueByReflection('outputService', $dumpController));

        // Test the browser output
        Krexx::$pool->config = $browserMock;
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $dumpController));
        $this->assertInstanceOf(Browser::class, $this->retrieveValueByReflection('outputService', $dumpController));
    }
}
