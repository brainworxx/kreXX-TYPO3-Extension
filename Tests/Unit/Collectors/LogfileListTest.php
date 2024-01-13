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

namespace Brainworxx\Includekrexx\Tests\Unit\Collectors;

use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use TYPO3\CMS\Fluid\View\AbstractTemplateView;
use TYPO3\CMS\Backend\Routing\UriBuilder;

class LogfileListTest extends AbstractHelper
{
    /**
     * Test the retrieval of logfile infos.
     *
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::assignData
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::retrieveFileList
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::retrieveFileInfo
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::addMetaToFileInfo
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::fileSizeConvert
     * @covers \Brainworxx\Includekrexx\Collectors\LogfileList::getRoute
     */
    public function testAssignData()
    {
        $assign = 'assign';
        $fileList = 'filelist';
        $someBeUrl = 'some backend url';
        $dateFormat = 'd.m.y H:i:s';
        $dispatcher = 'dispatcher';

        // No access. Show no files at all.
        $logLister = new LogfileList();
        $viewMock = $this->createMock(AbstractTemplateView::class);
        $viewMock->expects($this->once())
            ->method($assign)
            ->with($fileList, []);
        $logLister->assignData($viewMock);

        // Normal access.
        $logLister = new LogfileList();
        $this->setValueByReflection('hasAccess', true, $logLister);

        // Use the files inside the fixture folder.
        // We are not simulating these by function mocks.
        $this->setValueByReflection(
            'directories',
            ['log' => __DIR__ . '/../../Fixtures/'],
            \Krexx::$pool->config
        );

        // Simulate some backend routing.
        $beUriBuilderMock = $this->createMock(UriBuilder::class);
        $beUriBuilderMock->expects($this->exactly(3))
            ->method('buildUriFromRoute')
            ->will($this->returnValue($someBeUrl));

        $this->injectIntoGeneralUtility(UriBuilder::class, $beUriBuilderMock);

        // Mock the filetime, because it may change in the CI server.
        $filemTimeMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Collectors\\', 'filemtime');
        $filemTimeMock->expects($this->any())
            ->willReturnOnConsecutiveCalls(100, 101, 102, 103, 104, 105, 106, 100, 101, 102, 103, 104, 105, 106);

        $expectation = [
            [
                'name' => '123456.Krexx.html',
                'size' => '390 B',
                'time' => date($dateFormat, 104),
                'id' => '123456',
                $dispatcher => $someBeUrl,
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
                'time' => date($dateFormat, 105),
                'id' => '123457',
                $dispatcher => $someBeUrl,
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
                'time' => date($dateFormat, 106),
                'id' => '123458',
                $dispatcher => $someBeUrl,
                'meta' => []
            ]
        ];
        $viewMock = $this->createMock(AbstractTemplateView::class);
        $viewMock->expects($this->once())
            ->method($assign)
            ->with($fileList, $expectation);
        $logLister->assignData($viewMock);
    }
}
