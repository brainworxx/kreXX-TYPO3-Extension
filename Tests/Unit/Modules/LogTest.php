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

namespace Brainworxx\Includekrexx\Tests\Unit\Modules;

use Brainworxx\Includekrexx\Bootstrap\Bootstrap;
use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Modules\Log;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Krexx;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

class LogTest extends AbstractTest
{
    /**
     * @var \Brainworxx\Includekrexx\Modules\Log
     */
    protected $log;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        parent::setUp();
        $this->simulatePackage('includekrexx', 'whatever');
        $this->log = new Log();
    }

    /**
     * Testing the unique identifier.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->assertEquals(Bootstrap::KREXX, $this->log->getIdentifier());
    }

    /**
     * Test the 'translated' label getter.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getLabel
     */
    public function testGetLabel()
    {
        $this->assertEquals($this->log::TRANSLATION_PREFIX . 'mlang_tabs_tab', $this->log->getLabel());
    }

    /**
     * Test the retrieval of the log fil list class.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getDataToStore
     */
    public function testGetDataToStore()
    {
        $fileList = ['file', 'list'];
        $expectations = new ModuleData(['files' => $fileList]);

        $logfileListMock = $this->createMock(LogfileList::class);
        $logfileListMock->expects($this->once())
            ->method('retrieveFileList')
            ->will($this->returnValue($fileList));

        $objectManagerMock = $this->createMock(ObjectManager::class);
        $objectManagerMock->expects($this->once())
            ->method('get')
            ->will($this->returnValue($logfileListMock));
        $this->injectIntoGeneralUtility(ObjectManager::class, $objectManagerMock);

        $request = new ServerRequest();
        $this->assertEquals($expectations, $this->log->getDataToStore($request));
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
        // Prepare the view for the messages.
        $viewMock = $this->mockView();
        $viewMock->expects($this->once())
            ->method('assignMultiple')
            ->with([
                'text' => $this->log::TRANSLATION_PREFIX . 'accessDenied',
                'severity' => $this->log::MESSAGE_SEVERITY_ERROR,
            ]);
        $viewMock->expects($this->once())
            ->method('render')
            ->will($this->returnValue('Rendered Messages'));

        $moduleData = new ModuleData();
        $this->assertEquals('Rendered Messages', $this->log->getContent($moduleData));
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

        Krexx::$pool->messages->addMessage('translationkey');
        $moduleData = new ModuleData(['files' => []]);

        $viewMock = $this->mockView();
        $viewMock->expects($this->exactly(2))
            ->method('assignMultiple')
            ->withConsecutive(
                [
                    [
                        'text' => $this->log::TRANSLATION_PREFIX . 'translationkey',
                        'severity' => $this->log::MESSAGE_SEVERITY_ERROR,
                    ]
                ],
                [
                    [
                        'text' => $this->log::TRANSLATION_PREFIX . 'log.noresult',
                        'severity' => $this->log::MESSAGE_SEVERITY_INFO,
                    ]
                ]
            );
        $viewMock->expects($this->exactly(2))
            ->method('render')
            ->will($this->returnValue('rendering'));

        $this->assertEquals('renderingrendering', $this->log->getContent($moduleData));
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
        $fileList = ['files' => ['just', 'some', 'files']];
        $expectations = 'list of files';
        $moduleData = new ModuleData($fileList);

        $viewMock = $this->mockView();
        $viewMock->expects($this->once())
            ->method('assignMultiple')
            ->with($fileList);
        $viewMock->expects($this->once())
            ->method('render')
            ->will($this->returnValue($expectations));

         $this->assertEquals($expectations, $this->log->getContent($moduleData));
    }

    /**
     * Test the assigning of the css file.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getCssFiles
     */
    public function testGetCssFiles()
    {
        $this->assertEquals(
            ['EXT:includekrexx/Resources/Public/Css/Adminpanel.css'],
            $this->log->getCssFiles()
        );
    }

    /**
     * Test the not-assigning of any js files.
     *
     * @covers \Brainworxx\Includekrexx\Modules\Log::getJavaScriptFiles
     */
    public function testGetJavaScriptFiles()
    {
        $this->assertEmpty($this->log->getJavaScriptFiles());
    }

    /**
     * Mock the standalone view with the most standard values.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockView() : MockObject
    {
        // Prepare the view for the messages.
        $viewMock = $this->createMock(StandaloneView::class);
        $viewMock->expects($this->any())
            ->method('setTemplatePathAndFilename');
        $viewMock->expects($this->any())
            ->method('setPartialRootPaths');
        $viewMock->expects($this->any())
            ->method('setLayoutRootPaths');
        $this->injectIntoGeneralUtility(StandaloneView::class, $viewMock);

        return $viewMock;
    }
}