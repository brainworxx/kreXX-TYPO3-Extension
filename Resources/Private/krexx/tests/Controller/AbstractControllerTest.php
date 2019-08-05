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

use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Errorhandler\Fatal;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\File;

class AbstractControllerTest extends AbstractTest
{

    const FATAL_SHOULD_ACTIVATE = 'fatalShouldActive';
    const SET_IS_ACTIVE = 'setIsActive';
    const KREXX_FATAL = 'krexxFatal';

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
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertAttributeEquals(Krexx::$pool, 'pool', $dumpController);
        $this->assertAttributeInstanceOf(File::class, 'outputService', $dumpController);

        // Test the browser output
        Krexx::$pool->config = $browserMock;
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertAttributeEquals(Krexx::$pool, 'pool', $dumpController);
        $this->assertAttributeInstanceOf(Browser::class, 'outputService', $dumpController);
    }

    /**
     * Test the deactivation of the fatal error handler during a analysis.
     *
     * @covers \Brainworxx\Krexx\Controller\AbstractController::noFatalForKrexx
     */
    public function testNoFatalForKrexx()
    {
        // Test with a fatal error handler mock in place.
        $dumpController = new DumpController(Krexx::$pool);
        $this->setValueByReflection(
            static::FATAL_SHOULD_ACTIVATE,
            true,
            $dumpController
        );
        $handlerMock = $this->createMock(Fatal::class);
        $handlerMock->expects($this->once())
            ->method(static::SET_IS_ACTIVE)
            ->with(false);
        $this->setValueByReflection(
            static::KREXX_FATAL,
            $handlerMock,
            $dumpController
        );

        $dumpController->noFatalForKrexx();

        // No fatal error handler active.
        $dumpController = new DumpController(Krexx::$pool);
        $this->setValueByReflection(
            static::FATAL_SHOULD_ACTIVATE,
            false,
            $dumpController
        );
        $handlerMock = $this->createMock(Fatal::class);
        $handlerMock->expects($this->never())
            ->method(static::SET_IS_ACTIVE);
        $this->setValueByReflection(
            static::KREXX_FATAL,
            $handlerMock,
            $dumpController
        );

        $dumpController->noFatalForKrexx();
    }

    /**
     * Test the reactivation of the fatal error handler after a analysis
     *
     * @covers \Brainworxx\Krexx\Controller\AbstractController::reFatalAfterKrexx
     */
    public function testReFatalAfterKrexx()
    {
        // Test with a fatal error handler mock in place.
        $dumpController = new DumpController(Krexx::$pool);
        $this->setValueByReflection(
            static::FATAL_SHOULD_ACTIVATE,
            true,
            $dumpController
        );
        $handlerMock = $this->createMock(Fatal::class);
        $handlerMock->expects($this->once())
            ->method(static::SET_IS_ACTIVE)
            ->with(true);
        $this->setValueByReflection(
            static::KREXX_FATAL,
            $handlerMock,
            $dumpController
        );

        $dumpController->reFatalAfterKrexx();

         // No fatal error handler active.
        $dumpController = new DumpController(Krexx::$pool);
        $this->setValueByReflection(
            static::FATAL_SHOULD_ACTIVATE,
            false,
            $dumpController
        );
        $handlerMock = $this->createMock(Fatal::class);
        $handlerMock->expects($this->never())
            ->method(static::SET_IS_ACTIVE);
        $this->setValueByReflection(
            static::KREXX_FATAL,
            $handlerMock,
            $dumpController
        );

        $dumpController->reFatalAfterKrexx();
    }
}