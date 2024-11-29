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

namespace Brainworxx\Includekrexx\Tests\Unit\Modules;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Fluid\View\FluidViewFactory;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LogTest extends AbstractHelper
{
    const WRONG_VERSION = 'Wrong TYPO3 version.';
    const FILES = 'files';
    const ASSIGN_MULTIPLE = 'assignMultiple';
    const SEVERITY = 'severity';
    const TEXT = 'text';
    const RENDER = 'render';


    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->simulatePackage('includekrexx', 'whatever');
    }

    /**
     * @covers \Brainworxx\Includekrexx\Modules\Log::__construct
     * @covers \Brainworxx\Includekrexx\Modules\Log::createViews12
     * @covers \Brainworxx\Includekrexx\Modules\Log::createViews13
     */
    public function testConstruct()
    {
        $viewMock = $this->mockView();

        $logModule = new Log();
        $this->assertSame($viewMock, $this->retrieveValueByReflection('mainView', $logModule));
        $this->assertSame($viewMock, $this->retrieveValueByReflection('messageView', $logModule));
    }

    /**
     * Testing the unique identifier.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->mockView();
        $logModule = new Log();
        $this->assertEquals(Bootstrap::KREXX, $logModule->getIdentifier());
    }

    /**
     * Test the 'translated' label getter.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getLabel
     */
    public function testGetLabel()
    {
        $this->mockView();
        $logModule = new Log();
        $this->assertEquals(
            'LLL:EXT:includekrexx/Resources/Private/Language/locallang.xlf:mlang_tabs_tab',
            $logModule->getLabel()
        );
    }

    /**
     * Test the retrieval of the log fil list class.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getDataToStore
     */
    public function testGetDataToStore()
    {
        $this->mockView();
        $logModule = new Log();
        $fileList = ['file', 'list'];
        $expectations = new ModuleData([static::FILES => $fileList]);

        $logfileListMock = $this->createMock(LogfileList::class);
        $logfileListMock->expects($this->once())
            ->method('retrieveFileList')
            ->willReturn($fileList);
        $this->injectIntoGeneralUtility(LogfileList::class, $logfileListMock);

        $request = new ServerRequest();
        $this->assertEquals($expectations, $logModule->getDataToStore($request));
    }

    /**
     * Test the displaying of the file list, when having no access.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getContent
     * @covers \Brainworxx\Includekrexx\Modules\Log::hasAccess
     * @covers \Brainworxx\Includekrexx\Modules\Log::renderMessage
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
     * kreXX messasges, complaining about stuff.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getContent
     * @covers \Brainworxx\Includekrexx\Modules\Log::hasAccess
     * @covers \Brainworxx\Includekrexx\Modules\Log::renderMessage
     * @covers \Brainworxx\Includekrexx\Modules\Log::retrieveKrexxMessages
     */
    public function testGetContentEmpty()
    {
        $this->mockBeUser();
        $mockview = $this->mockView();

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
     * Test the normal diosplay of the file list and without any messages.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getContent
     * @covers \Brainworxx\Includekrexx\Modules\Log::hasAccess
     * @covers \Brainworxx\Includekrexx\Modules\Log::retrieveKrexxMessages
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
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getCssFiles
     */
    public function testGetCssFiles()
    {
        $this->mockView();
        $logModule = new Log();
        $this->assertEquals(
            ['EXT:includekrexx/Resources/Public/Css/Adminpanel.css'],
            $logModule->getCssFiles()
        );
    }

    /**
     * Test the not-assigning of any js files.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getJavaScriptFiles
     */
    public function testGetJavaScriptFiles()
    {
        $this->mockView();
        $logModule = new Log();
        $this->assertEmpty($logModule->getJavaScriptFiles());
    }

    /**
     * Mock the standalone view with the most standard values.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockView(): MockObject
    {
        if (interface_exists(ViewFactoryInterface::class)) {
            $viewFactoryMock = $this->createMock(ViewFactoryInterface::class);
            $viewMock = $this->createMock(ViewInterface::class);
            $viewFactoryMock->expects($this->exactly(2))
                ->method('create')
                ->willReturn($viewMock);
            $this->injectIntoGeneralUtility(ViewFactoryInterface::class, $viewFactoryMock);
        } else {
            $viewMock = $this->createMock(StandaloneView::class);
            $viewMock->expects($this->exactly(2))
                ->method('setPartialRootPaths')
                ->with(['EXT:includekrexx/Resources/Private/Partials']);
            $viewMock->expects($this->exactly(2))
                ->method('setLayoutRootPaths')
                ->with(['EXT:includekrexx/Resources/Private/Layouts']);
            $viewMock->expects($this->exactly(2))
                ->method('setTemplatePathAndFilename')
                ->with(...$this->withConsecutive(
                    ['EXT:includekrexx/Resources/Private/Templates/Modules/Log.html'],
                    ['EXT:includekrexx/Resources/Private/Templates/Modules/Message.html']
                ));
            $viewMock->expects($this->exactly(2))
                ->method('setFormat')
                ->with('html');

            $this->injectIntoGeneralUtility(StandaloneView::class, $viewMock);
        }

        return $viewMock;
    }
}
