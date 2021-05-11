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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

use Brainworxx\Includekrexx\Collectors\AbstractCollector;
use Brainworxx\Includekrexx\Collectors\Configuration;
use Brainworxx\Includekrexx\Controller\IndexController;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class AbstractCollectorTest extends AbstractTest
{
    /**
     * Test, if the current BE user has access and test the retrieval of the uc.
     *
     * @covers \Brainworxx\Includekrexx\Collectors\AbstractCollector::__construct
     */
    public function testConstruct()
    {
        $hasAccess = 'hasAccess';
        // No BE User available.
        $collector = new Configuration();
        $this->assertFalse(
            $this->retrieveValueByReflection($hasAccess, $collector)
        );

        // BE user without access.
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->expects($this->once())
            ->method('check')
            ->with('modules', AbstractCollector::PLUGIN_NAME)
            ->will($this->returnValue(false));
        $GLOBALS['BE_USER'] = $userMock;
        $collector = new Configuration();
        $this->assertFalse(
            $this->retrieveValueByReflection($hasAccess, $collector)
        );

        // BE user with access.
        $uc = [
            AbstractCollector::MODULE_DATA => [
                IndexController::MODULE_KEY => ['some', 'settings']
            ]
        ];
        $userMock = $this->createMock(BackendUserAuthentication::class);
        $userMock->expects($this->once())
            ->method('check')
            ->with('modules', AbstractCollector::PLUGIN_NAME)
            ->will($this->returnValue(true));
        $userMock->uc = $uc;
        $GLOBALS['BE_USER'] = $userMock;
        $collector = new Configuration();
        $this->assertTrue(
            $this->retrieveValueByReflection($hasAccess, $collector)
        );
        $this->assertEquals(
            ['some', 'settings'],
            $this->retrieveValueByReflection('userUc', $collector)
        );
    }
}