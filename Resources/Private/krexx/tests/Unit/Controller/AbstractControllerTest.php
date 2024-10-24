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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\EditSettingsController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\BrowserImmediately;
use Brainworxx\Krexx\View\Output\File;
use Brainworxx\Krexx\Service\Misc\File as FileService;

class AbstractControllerTest extends AbstractHelper
{
    /**
     * Testing the construction phase of the controller
     *
     * @covers \Brainworxx\Krexx\Controller\AbstractController::__construct
     */
    public function testConstruct()
    {
        // Test the file output
        ConfigSupplier::$overwriteValues[ConfigConstInterface::SETTING_DESTINATION] = ConfigConstInterface::VALUE_FILE;
        Krexx::$pool->rewrite[\Brainworxx\Krexx\Service\Config\From\File::class] = ConfigSupplier::class;
        new Config(\Krexx::$pool);
        $oldRecursionHandler = Krexx::$pool->recursionHandler;
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertNotSame($oldRecursionHandler, Krexx::$pool->recursionHandler, 'Test the resetting of the pool');
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $dumpController));
        $this->assertInstanceOf(File::class, $this->retrieveValueByReflection('outputService', $dumpController));

        // Test the browser output
        ConfigSupplier::$overwriteValues[ConfigConstInterface::SETTING_DESTINATION] = ConfigConstInterface::VALUE_BROWSER;
        new Config(\Krexx::$pool);
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $dumpController));
        $this->assertInstanceOf(Browser::class, $this->retrieveValueByReflection('outputService', $dumpController));

        // Test the immediate output.
        ConfigSupplier::$overwriteValues[ConfigConstInterface::SETTING_DESTINATION] = ConfigConstInterface::VALUE_BROWSER_IMMEDIATELY;
        new Config(\Krexx::$pool);
        $dumpController = new DumpController(Krexx::$pool);
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $dumpController));
        $this->assertInstanceOf(BrowserImmediately::class, $this->retrieveValueByReflection('outputService', $dumpController));
    }

    /**
     * We simply test the outputCssAndJs, with loading the un-minified files.
     *
     * @covers \Brainworxx\Krexx\Controller\AbstractController::outputCssAndJs
     */
    public function testOutputCssAndJsWithoutMinFiles()
    {
        $fileMock = $this->createMock(FileService::class);
        $fileMock->expects($this->any())
            ->method('fileIsReadable')
            ->willReturn(false);
        $fileMock->expects($this->any())
            ->method('getFileContents')
            ->willReturn('some content');
        Krexx::$pool->fileService = $fileMock;

        $outputServiceMock = $this->createMock(Browser::class);
        $outputServiceMock->expects($this->any())
            ->method('addChunkString')
            ->willReturn($outputServiceMock);

        Krexx::$pool->render = new RenderNothing(Krexx::$pool);

        $editSettingscontroller = new EditSettingsController(Krexx::$pool);
        $this->setValueByReflection('outputService', $outputServiceMock, $editSettingscontroller);
        $editSettingscontroller->editSettingsAction();

        // Let's do this a second time, and make sure that we do not send the
        // css/js a second time.
        $fileMock = $this->createMock(FileService::class);
        $fileMock->expects($this->any())
            ->method('fileIsReadable')
            ->willReturn(false);
        $fileMock->expects($this->never())
            ->method('getFileContents');
        Krexx::$pool->fileService = $fileMock;

        $editSettingscontroller->editSettingsAction();
    }
}
