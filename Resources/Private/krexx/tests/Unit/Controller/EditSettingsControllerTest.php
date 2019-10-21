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

namespace Brainworxx\Krexx\Tests\Unit\Controller;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConfig;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Controller\EditSettingsController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\CallbackNothing;
use Brainworxx\Krexx\View\Output\Browser;

class EditSettingsControllerTest extends AbstractController
{
    /**
     * Call the action when the max call is already reached.
     *
     * @covers \Brainworxx\Krexx\Controller\EditSettingsController::editSettingsAction
     */
    public function testEditSettingsActionWithMaxCall()
    {
        $emergencyMock = $this->createMock(Emergency::class);
        $emergencyMock->expects($this->once())
            ->method('checkMaxCall')
            ->will($this->returnValue(true));
        $emergencyMock->expects($this->never())
            ->method('setDisable');
        Krexx::$pool->emergencyHandler = $emergencyMock;

        $controller = new EditSettingsController(Krexx::$pool);
        $this->assertEquals($controller, $controller->editSettingsAction());
    }

    /**
     * Normal call of the action, nothing special.
     *
     * @covers \Brainworxx\Krexx\Controller\EditSettingsController::editSettingsAction
     */
    public function testEditSettingsActionNormal()
    {
        $controller = new EditSettingsController(Krexx::$pool);
        $poolMock = $this->mockMainOutput($controller);
        $this->mockFooterHeaderOutput($poolMock);

        $poolMock->emergencyHandler->expects($this->exactly(2))
            ->method('setDisable')
            ->withConsecutive([true], [false]);

        $poolMock->render->setFooter('generated HTML code');

        $outputServiceMock = $this->createMock(Browser::class);
        $outputServiceMock->expects($this->exactly(2))
            ->method('addChunkString')
            ->withAnyParameters()
            ->willReturnSelf();
        $this->setValueByReflection('outputService', $outputServiceMock, $controller);

        $poolMock->expects($this->exactly(2))
            ->method('createClass')
            ->withConsecutive(
                [Model::class],
                [ThroughConfig::class]
            )->will($this->returnValueMap(
                [
                    [Model::class, new Model(Krexx::$pool)],
                    [ThroughConfig::class, new CallbackNothing(Krexx::$pool)]
                ]
            ));

        $this->assertEquals($controller, $controller->editSettingsAction());
    }
}
