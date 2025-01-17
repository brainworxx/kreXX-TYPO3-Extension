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

namespace Brainworxx\Includekrexx\Tests\Unit\Controller;

use Brainworxx\Includekrexx\Collectors\LogfileList;
use Brainworxx\Includekrexx\Controller\AjaxController;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use TYPO3\CMS\Core\Http\ServerRequest;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(AjaxController::class, 'deleteAction')]
#[CoversMethod(AjaxController::class, 'hasAccess')]
#[CoversMethod(AjaxController::class, 'delete')]
#[CoversMethod(AjaxController::class, 'refreshLoglistAction')]
class AjaxControllerTest extends AbstractHelper
{
    /**
     * Test the retrieval of the log file list.
     */
    public function testRefreshLoglistAction()
    {
        $logfileListMock = $this->createMock(LogfileList::class);
        $logfileListMock->expects($this->once())
            ->method('retrieveFileList')
            ->willReturn(['file', 'list']);

        $this->injectIntoGeneralUtility(LogfileList::class, $logfileListMock);

        $controller = new AjaxController();
        $serverRequest = new ServerRequest();

        $this->assertEquals(
            '["file","list"]',
            $controller->refreshLoglistAction($serverRequest)->getBody()->__toString()
        );
    }

    /**
     * Test the deleting attempt of a logfile.
     */
    public function testDeleteActionNoAccess()
    {
        $controller = new AjaxController();
        $serverRequest = new ServerRequest();
        $this->assertEquals(
            '{"class":"error","text":"accessDenied"}',
            $controller->deleteAction($serverRequest)->getBody()->__toString()
        );
    }

    /**
     * Testing the real deletion of a file.
     */
    public function testDeleteActionNormal()
    {
        $controllerNamespace = '\\Brainworxx\\Includekrexx\\Controller\\';

        $this->mockBeUser();
        $serverRequest = $this->createMock(ServerRequest::class);
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['fileid' => '123456']);

        $fileExistsMock = $this->getFunctionMock($controllerNamespace, 'file_exists');
        $fileExistsMock->expects($this->exactly(2))
            ->willReturn(true);
        $isWritableMock = $this->getFunctionMock($controllerNamespace, 'is_writable');
        $isWritableMock->expects($this->exactly(2))
            ->willReturn(true);
        $unlinkMock = $this->getFunctionMock($controllerNamespace, 'unlink');
        $unlinkMock->expects($this->exactly(2));

        $controller = new AjaxController();

        $this->assertEquals(
            '{"class":"success","text":"fileDeleted"}',
            $controller->deleteAction($serverRequest)->getBody()->__toString()
        );
    }

    /**
     * Testing the real deletion of a file wit an error.
     */
    public function testDeleteActionError()
    {
        $this->mockBeUser();
        $serverRequest = $this->createMock(ServerRequest::class);
        $serverRequest->expects($this->once())
            ->method('getQueryParams')
            ->willReturn(['fileid' => '987654']);

        $controller = new AjaxController();

        $this->assertEquals(
            '{"class":"error","text":"fileDeletedFail"}',
            $controller->deleteAction($serverRequest)->getBody()->__toString(),
            'This file does not exist. No need to mock any function here.'
        );
    }
}
