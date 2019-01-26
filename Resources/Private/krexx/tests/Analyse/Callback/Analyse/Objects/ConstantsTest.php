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

namespace Brainworxx\Krexx\Tests\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Constants;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughConstants;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\CallbackCounter;
use Brainworxx\Krexx\View\AbstractRender;

class ConstantsTest extends AbstractTest
{
    public function setUp()
    {
        parent::setUp();

        \Krexx::$pool->rewrite = [
            ThroughConstants::class => CallbackCounter::class,
        ];

        $this->mockEmergencyHandler();
    }

    /**
     * Testing the analysis of constants (without any constants).
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Constants::callMe
     */
    public function testCallMeNoConstants()
    {
        // Create the fixture mock, without any constants.
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->expects($this->once())
            ->method('getConstants')
            ->will($this->returnValue([]));

        $reflectionMock->expects($this->never())
            ->method('getName');

        $fixture = [
            'ref' => $reflectionMock
        ];

        $constants = new Constants(\Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Constants::callMe::start', $constants]
        );

        // We will not render anything.
        $renderMock = $this->createMock(AbstractRender::class);
        $renderMock->expects($this->never())
            ->method('renderExpandableChild');
        \Krexx::$pool->render = $renderMock;

        // Run the test.
        $constants->setParams($fixture)
            ->callMe();

        // Was it called?
        $this->assertEquals(0, CallbackCounter::$counter);
    }

     /**
     * Testing the analysis of constants,
     *
     * @covers \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Constants::callMe
     */
    public function testCallMeWithConstants()
    {
        // Create the fixture mock, without any constants.
        $reflectionMock = $this->createMock(\ReflectionClass::class);
        $reflectionMock->expects($this->once())
            ->method('getConstants')
            ->will($this->returnValue('some constants'));

        $reflectionMock->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('some classname'));

        $fixture = [
            'ref' => $reflectionMock
        ];

        // Create the class to test
        $constants = new Constants(\Krexx::$pool);
        $this->mockEventService(
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Constants::callMe::start', $constants],
            ['Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Objects\\Constants::analysisEnd', $constants]
        );

        // Run the test.
        $constants->setParams($fixture)
            ->callMe();

        // Was it called?
        $this->assertEquals(1, CallbackCounter::$counter);
        // Were the parameters set correctly?
        // The classname gets root-namespaced, hence the '\'
        $this->assertEquals(
            ['data' => 'some constants', 'classname' => '\\some classname'],
            CallbackCounter::$staticParameters[0]
        );
    }
}
