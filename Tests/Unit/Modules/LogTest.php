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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Tests\Unit\Modules;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Log::class, 'getJavaScriptFiles')]
#[CoversMethod(Log::class, 'getCssFiles')]
#[CoversMethod(Log::class, 'getContent')]
#[CoversMethod(Log::class, 'hasAccess')]
#[CoversMethod(Log::class, 'retrieveKrexxMessages')]
#[CoversMethod(Log::class, 'createView')]
#[CoversMethod(Log::class, 'createView13')]
#[CoversMethod(Log::class, 'renderMessage')]
#[CoversMethod(Log::class, 'getDataToStore')]
#[CoversMethod(Log::class, 'getLabel')]
#[CoversMethod(Log::class, 'getIdentifier')]
class LogTest extends AbstractHelper
{
    protected const WRONG_VERSION = 'Wrong TYPO3 version.';
    protected const FILES = 'files';
    protected const ASSIGN_MULTIPLE = 'assignMultiple';
    protected const SEVERITY = 'severity';
    protected const TEXT = 'text';
    protected const RENDER = 'render';

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->simulatePackage('includekrexx', 'whatever');
    }

    /**
     * Testing the unique identifier.
     */
    public function testGetIdentifier()
    {
        $logModule = new Log();
        $this->assertEquals(Bootstrap::KREXX, $logModule->getIdentifier());
    }

    /**
     * Test the 'translated' label getter.
     */
    public function testGetLabel()
    {
        $logModule = new Log();
        $this->assertEquals(
            'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:mlang_tabs_tab',
            $logModule->getLabel()
        );
    }

    /**
     * Test the retrieval of the log fil list class.
     */
    public function testGetDataToStore()
    {
        $logModule = new Log();
        $fileList = ['file', 'list'];
        $expectations = new ModuleData([static::FILES => $fileList]);

        $logfileListMock = $this->createMock(LogfileList::class);
        $logfileListMock->expects($this->once())
            ->method('retrieveFileList')
            ->willReturn($fileList);
        $this->injectIntoGeneralUtility(LogfileList::class, $logfileListMock);

        $request = new ServerRequest();

        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() > 13) {
            $response = $this->createMock(\Psr\Http\Message\ResponseInterface::class);
            $this->assertEquals($expectations, $logModule->getDataToStore($request, $response));
        } else {
            $this->assertEquals($expectations, $logModule->getDataToStore($request));
        }
    }

    /**
     * Test the displaying of the file list, when having no access.
     */
    public function testGetContentNoAccess()
    {
        $mockview = $this->mockView();
        // Prepare the view for the messages.
        $mockview->expects($this->once())
            ->method(static::ASSIGN_MULTIPLE)
            ->with([
                static::TEXT => 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:accessDenied',
                static::SEVERITY => 'error',
            ]);
        $mockview->expects($this->once())
            ->method(static::RENDER)
            ->willReturn('Rendered Messages');

        $moduleData = new ModuleData();
        $logModule = new Log();
        $this->assertEquals('Rendered Messages', $logModule->getContent($moduleData));
    }

    /**
     * Test the display of the no-logfiles-available message and the display of
     * kreXX messages, complaining about stuff.
     */
    public function testGetContentEmpty()
    {
        $this->mockBeUser();
        $mockview = $this->mockView(2);

        Krexx::$pool->messages->addMessage('translationkey');
        $moduleData = new ModuleData([static::FILES => []]);

        $mockview->expects($this->exactly(2))
            ->method(static::ASSIGN_MULTIPLE)
            ->with(...$this->withConsecutive(
                [
                    [
                        static::TEXT => 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:translationkey',
                        static::SEVERITY => 'error',
                    ]
                ],
                [
                    [
                        static::TEXT => 'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:log.noresult',
                        static::SEVERITY => 'info',
                    ]
                ]
            ));
        $mockview->expects($this->exactly(2))
            ->method(static::RENDER)
            ->willReturn('rendering');

        $logModule = new Log();
        $this->assertEquals('renderingrendering', $logModule->getContent($moduleData));
    }

    /**
     * Test the normal display of the file list and without any messages.
     */
    public function testGetContentNormal()
    {
        $this->mockBeUser();
        $fileList = [static::FILES => ['just', 'some', 'files']];
        $expectations = 'list of files';
        $moduleData = new ModuleData($fileList);

        $mockview = $this->mockView();
        $mockview->expects($this->once())
            ->method(static::ASSIGN_MULTIPLE)
            ->with($fileList);
        $mockview->expects($this->once())
            ->method(static::RENDER)
            ->willReturn($expectations);

        $logModule = new Log();
        $this->assertEquals($expectations, $logModule->getContent($moduleData));
    }

    /**
     * Test the assigning of the css file.
     */
    public function testGetCssFiles()
    {
        $logModule = new Log();
        $this->assertEquals(
            ['EXT:includekrexx/Resources/Public/Css/Adminpanel.css'],
            $logModule->getCssFiles()
        );
    }

    /**
     * Test the not-assigning of any js files.
     */
    public function testGetJavaScriptFiles()
    {
        $logModule = new Log();
        $this->assertEmpty($logModule->getJavaScriptFiles());
    }

    /**
     * Mock the standalone view with the most standard values.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockView(int $count = 1): MockObject
    {
        if (interface_exists(ViewFactoryInterface::class)) {
            $viewFactoryMock = $this->createMock(ViewFactoryInterface::class);
            $viewMock = $this->createMock(ViewInterface::class);
            $viewFactoryMock->expects($this->exactly($count))
                ->method('create')
                ->willReturn($viewMock);
            $this->injectIntoGeneralUtility(ViewFactoryInterface::class, $viewFactoryMock);
        } else {
            $viewMock = $this->createMock(StandaloneView::class);
            $viewMock->expects($this->exactly($count))
                ->method('setPartialRootPaths')
                ->with(['EXT:includekrexx/Resources/Private/Partials']);
            $viewMock->expects($this->exactly($count))
                ->method('setLayoutRootPaths')
                ->with(['EXT:includekrexx/Resources/Private/Layouts']);
            $viewMock->expects($this->exactly($count))
                ->method('setTemplatePathAndFilename');
            $viewMock->expects($this->exactly($count))
                ->method('setFormat')
                ->with('html');

            $this->injectIntoGeneralUtility(StandaloneView::class, $viewMock);
        }

        return $viewMock;
    }
}
