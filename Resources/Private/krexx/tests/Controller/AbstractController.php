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

use Brainworxx\Krexx\Analyse\Caller\CallerFinder;
use Brainworxx\Krexx\Analyse\Code\Scope;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\RenderNothing;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\Output\Browser;
use Brainworxx\Krexx\View\Output\Chunks;

class AbstractController extends AbstractTest
{
    /**
     * Result mock from the caller finder.
     *
     * @var array
     */
    protected $callerFinderResult;

    public function setUp()
    {
        parent::setUp();

        $this->callerFinderResult = [
            ConstInterface::TRACE_FILE => 'just another path',
            ConstInterface::TRACE_LINE => 41,
            ConstInterface::TRACE_VARNAME => '$varWithAName',
            ConstInterface::TRACE_TYPE => 'Backtrace',
        ];
    }

    /**
     * Mock all the classes that are needed.
     *
     * @param \Brainworxx\Krexx\Controller\AbstractController $controller
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \ReflectionException
     */
    protected function mockMainOutput(\Brainworxx\Krexx\Controller\AbstractController $controller)
    {
        $this->setValueByReflection('jsCssSend', [], $controller);

        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('reset');
        $this->setValueByReflection('pool', $poolMock, $controller);

        // And now mock the living hell outa this little bugger.
        $callerFinderMock = $this->createMock(CallerFinder::class);
        $callerFinderMock->expects($this->once())
            ->method('findCaller')
            ->will($this->returnValue($this->callerFinderResult));
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
            ->will($this->returnValue(false));
        $poolMock->emergencyHandler = $emergencyMock;

        $renderNothing = new RenderNothing(Krexx::$pool);
        $poolMock->render = $renderNothing;

        return $poolMock;
    }

    /**
     * Creating all the mocks for the footer output.
     */
    protected function mockFooterHeaderOutput($poolMock)
    {
        $pathToIni = 'some path';
        $pathToSkin = 'skin directory';

        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())
            ->method('getPathToIniFile')
            ->will($this->returnValue($pathToIni));
        $configMock->expects($this->once())
            ->method('getSkinDirectory')
            ->will($this->returnValue($pathToSkin));
        $poolMock->config = $configMock;

        $fileServiceMock = $this->createMock(File::class);
        $fileServiceMock->expects($this->exactly(3))
            ->method('fileIsReadable')
            ->withConsecutive(
                [$pathToIni],
                [KREXX_DIR . 'resources/jsLibs/kdt.min.js'],
                [$pathToSkin . 'krexx.min.js']
            )->will($this->returnValueMap(
                [
                    [$pathToIni, true],
                    [KREXX_DIR . 'resources/jsLibs/kdt.min.js', true],
                    [$pathToSkin . 'krexx.min.js', true]
                ]
            ));
        $fileServiceMock->expects($this->once())
            ->method('filterFilePath')
            ->with($pathToIni)
            ->will($this->returnValue('filtered path'));
        $fileServiceMock->expects($this->exactly(3))
            ->method('getFileContents')
            ->withConsecutive(
                [KREXX_DIR . 'resources/jsLibs/kdt.min.js'],
                [$pathToSkin . 'skin.css'],
                [$pathToSkin . 'krexx.min.js']
            )->will($this->returnValueMap(
                [
                    [KREXX_DIR . 'resources/jsLibs/kdt.min.js', true, 'some js'],
                    [$pathToSkin . 'skin.css', true, 'some styles'],
                    [$pathToSkin . 'krexx.min.js', true, 'more js']
                ]
            ));
        $poolMock->fileService = $fileServiceMock;

        $messageMock = $this->createMock(Messages::class);
        $messageMock->expects($this->once())
            ->method('getHelp')
            ->with('currentConfig')
            ->will($this->returnValue('some helpful description'));
        $poolMock->messages = $messageMock;
    }
}
