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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Chunks;
use PHPUnit\Framework\MockObject\MockObject;

abstract class AbstractController extends AbstractHelper
{
    /**
     * Result mock from the caller finder.
     *
     * @var array
     */
    protected $callerFinderResult;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callerFinderResult = [
            BacktraceConstInterface::TRACE_FILE => 'just another path',
            BacktraceConstInterface::TRACE_LINE => 41,
            BacktraceConstInterface::TRACE_VARNAME => '$varWithAName',
            BacktraceConstInterface::TRACE_TYPE => 'Backtrace',
            BacktraceConstInterface::TRACE_LEVEL => 'debug'
        ];
    }

    /**
     * Mock all the classes that are needed.
     *
     * @param \Brainworxx\Krexx\Controller\AbstractController $controller
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockMainOutput(\Brainworxx\Krexx\Controller\AbstractController $controller)
    {
        $this->setValueByReflection('jsCssSend', [], $controller);

        $poolMock = $this->createMock(Pool::class);
        $this->setValueByReflection('pool', $poolMock, $controller);

        // And now mock the living hell outa this little bugger.
        $callerFinderMock = $this->createMock(CallerFinder::class);
        $callerFinderMock->expects($this->once())
            ->method('findCaller')
            ->willReturn($this->callerFinderResult);
        $this->setValueByReflection('callerFinder', $callerFinderMock, $controller);

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
            ->method('checkMaxCall')
            ->willReturn(false);
        $poolMock->emergencyHandler = $emergencyMock;

        $renderNothing = new RenderNothing(Krexx::$pool);
        $poolMock->render = $renderNothing;

        return $poolMock;
    }

    /**
     * Creating all the mocks for the footer output.
     *
     * @param \PHPUnit\Framework\MockObject\MockObject $poolMock
     */
    protected function mockFooterHeaderOutput(MockObject $poolMock)
    {
        $pathToIni = 'some path';
        $pathToSkin = 'skin directory';
        $pathToKdt = 'resources/jsLibs/kdt.min.js';
        $skinJs = 'krexx.min.js';
        $skinCss = 'skin.min.css';

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getPathToConfigFile')
            ->willReturn($pathToIni);
        $configMock->expects($this->once())
            ->method('getSkinDirectory')
            ->willReturn($pathToSkin);
        $poolMock->config = $configMock;

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('fileIsReadable')
            ->with(...$this->withConsecutive(
                [$pathToIni],
                [KREXX_DIR . $pathToKdt],
                [$pathToSkin . $skinCss]
            ))->willReturnMap(
                [
                    [$pathToIni, true],
                    [KREXX_DIR . $pathToKdt, true],
                    [$pathToSkin . $skinCss, true]
                ]
            );
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->with(...$this->withConsecutive(
                [KREXX_DIR . $pathToKdt],
                [$pathToSkin . $skinJs],
                [$pathToSkin . $skinCss]
            ))->willReturnMap(
                [
                    [KREXX_DIR . $pathToKdt, true, 'some js'],
                    [$pathToSkin . $skinCss, true, 'some styles'],
                    [$pathToSkin . $skinJs, true, 'more js']
                ]
            );
        $poolMock->fileService = $fileServiceMock;

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->any())
            ->method('getHelp')
            ->with($this->anything())
            ->willReturn('some helpful description');


        $messageMock->expects($this->any())
            ->method('outputMessages')
            ->willReturn('');
        $poolMock->messages = $messageMock;
    }
}
