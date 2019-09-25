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

namespace Brainworxx\Includekrexx\Tests\Unit\Collectors;

use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Service\Config\Config;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class LogfileListTest extends AbstractTest
{
    /**
     * Test the retrieval of logfile infos.
     *
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::assignData
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::retrieveFileList
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::retrieveFileInfo
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::fileSizeConvert
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::getRoute
     */
    public function testAssignData()
    {
        // No access. Show no files at all.
        $logLister = new LogfileList();
        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->once())
            ->method('assign')
            ->with('filelist', []);
        $logLister->assignData($viewMock);

        // Normal access.
        $logLister = new LogfileList();
        $this->setValueByReflection('hasAccess', true, $logLister);

        // Use the files inside the fixture folder.
        // We are not simulating these by function mocks.
        $this->setValueByReflection(
            'directories',
            [Config::LOG_FOLDER =>__DIR__ . '/../../Fixtures/'],
            \Krexx::$pool->config
        );

        // Simulate some backend routing.
        $uriBuilderMock = $this->createMock(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $uriBuilderMock->expects($this->exactly(3))
            ->method('reset')
            ->willReturnSelf();
        $uriBuilderMock->expects($this->exactly(3))
            ->method('setArguments')
            ->willReturnSelf();
        $uriBuilderMock->expects($this->exactly(3))
            ->method('uriFor')
            ->will($this->returnValue('some backend url'));

        $uriBeBuilderMock = $this->createMock(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $uriBeBuilderMock->expects($this->exactly(3))
            ->method('buildUriFromRoute')
            ->will($this->returnValue('another backend url'));

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class, $uriBuilderMock],
                [\TYPO3\CMS\Backend\Routing\UriBuilder::class, $uriBeBuilderMock]
            ]);

        $logLister->injectObjectManager($objectManagerMock);

        // Simulate a TYPO3 version.
        $versionCompareMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Collectors\\', 'version_compare');
        $versionCompareMock->expects($this->any())
            ->willReturnOnConsecutiveCalls([false, false, false, false, true, true, true, true, ]);

        // Mock the filetime, because it may change in the CI server.
        $filemTimeMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Collectors\\', 'filemtime');
        $filemTimeMock->expects($this->any())
            ->willReturnOnConsecutiveCalls(100, 101, 102, 103, 104, 105, 106, 100, 101, 102, 103, 104, 105, 106);

        $expectation = [
            [
                'name' => '123456.Krexx.html',
                'size' => '390 B',
                'time' => date("d.m.y H:i:s", 104),
                'id' => '123456',
                'dispatcher' => 'another backend url',
                'meta' => [
                    [
                        'file' => '.../some/directory/file.php',
                        'line' => '123',
                        'varname' => '$someVar',
                        'type' => 'not my type',
                        'date' => '05-08-2019 10:15:55',
                        'filename' => 'file.php'
                    ]
                ]
            ],
            [
                'name' => '123457.Krexx.html',
                'size' => '316 B',
                'time' => date("d.m.y H:i:s", 105),
                'id' => '123457',
                'dispatcher' => 'another backend url',
                'meta' => [
                    [
                        'file' => '.../some/directory/anotherFile.php',
                        'line' => '987',
                        'varname' => '$whatever',
                        'type' => 'absolutely',
                        'date' => '01-01-2010 10:00:00',
                        'filename' => 'anotherFile.php'
                    ]
                ]
            ],
            [
                'name' => '123458.Krexx.html',
                'size' => '205 B',
                'time' => date("d.m.y H:i:s", 106),
                'id' => '123458',
                'dispatcher' => 'another backend url'
            ]
        ];
        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->once())
            ->method('assign')
            ->with('filelist', $expectation);
        $logLister->assignData($viewMock);

        $expectation[0]['dispatcher'] = 'some backend url';
        $expectation[1]['dispatcher'] = 'some backend url';
        $expectation[2]['dispatcher'] = 'some backend url';
        $viewMock = $this->createMock(ViewInterface::class);
        $viewMock->expects($this->once())
            ->method('assign')
            ->with('filelist', $expectation);
        $logLister->assignData($viewMock);
    }
}
