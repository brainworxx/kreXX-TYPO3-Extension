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

use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\TimerController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Misc\Encoding;

class TimerControllerTest extends AbstractController
{

    const COUNTER_CACHE = 'counterCache';
    const TIME_KEEPING = 'timekeeping';
    /**
     * @var TimerController
     */
    protected $controller;

    public function setUp()
    {
        parent::setUp();

        // Create a clean timer controller.
        $this->controller = new TimerController(Krexx::$pool);
        $microtime = $this->getFunctionMock('\\Brainworxx\\Krexx\\Controller\\', 'microtime');
        $microtime->expects($this->any())
            ->will($this->returnValue(3000));
    }

    public function tearDown()
    {
        parent::tearDown();

        // Clean up the timekeeping stuff.
        $this->setValueByReflection(static::COUNTER_CACHE, [], $this->controller);
        $this->setValueByReflection(static::TIME_KEEPING, [], $this->controller);
    }

    /**
     * Testing the setting of the pool
     *
     * @covers \Brainworxx\Krexx\Controller\TimerController::__construct
     */
    public function testConstruct()
    {
        $this->assertEquals(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->controller));
        // The __construct from the abstract controller must not be called.
        $this->assertEquals(null, $this->retrieveValueByReflection('callerFinder', $this->controller));
    }

    /**
     * Testing the timer action.
     *
     * @covers \Brainworxx\Krexx\Controller\TimerController::timerAction
     */
    public function testTimerAction()
    {
        $first = 'first';
        $second = 'second';

        // Adding a first entry.
        $this->controller->timerAction($first);
        $this->assertEquals([$first => 1], $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller));
        $this->assertArrayHasKey($first, $this->getObjectAttribute($this->controller, static::TIME_KEEPING));

        // Adding a second entry.
        $this->controller->timerAction($second);
        $this->assertEquals([$first => 1, $second => 1], $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller));
        $this->assertArrayHasKey($first, $this->getObjectAttribute($this->controller, static::TIME_KEEPING));
        $this->assertArrayHasKey($second, $this->getObjectAttribute($this->controller, static::TIME_KEEPING));

        // Adding the first entry again.
        $this->controller->timerAction($first);
        $this->assertEquals([$first => 2, $second => 1], $this->retrieveValueByReflection(static::COUNTER_CACHE, $this->controller));
        $this->assertArrayHasKey('[2]' . $first, $this->getObjectAttribute($this->controller, static::TIME_KEEPING));
        $this->assertArrayHasKey($second, $this->getObjectAttribute($this->controller, static::TIME_KEEPING));
    }

    /**
     * Testing the output of the timer.
     *
     * @covers \Brainworxx\Krexx\Controller\TimerController::timerEndAction
     * @covers \Brainworxx\Krexx\Controller\TimerController::miniBenchTo
     * @covers \Brainworxx\Krexx\Controller\TimerController::getCurrentUrl
     */
    public function testTimerEndAction()
    {
        $dumpMock = $this->createMock(DumpController::class);
        $dumpMock->expects($this->once())
            ->method('dumpAction')
            ->will($this->returnCallback(
                function ($bench, $headline) {
                    $this->assertEquals('kreXX timer', $headline);
                    $this->assertEquals(
                        [
                            'url' => 'encoded url',
                            'total_time' => 2000000.0,
                            'first->second' => '50%',
                            'second->end' => '50%'
                        ],
                        $bench
                    );
                }
            ));

        $poolMock = $this->createMock(Pool::class);
        $poolMock->expects($this->once())
            ->method('createClass')
            ->with(DumpController::class)
            ->will($this->returnValue($dumpMock));
        $poolMock->expects($this->once())
            ->method('getServer')
            ->will($this->returnValue([
                'SERVER_PROTOCOL' => 'abcd/',
                'SERVER_PORT' => 123,
                'SERVER_NAME' => 'localhorst',
                'HTTPS' => 'on',
                'REQUEST_URI' => 'some/uri'
            ]));

        $encodingMock = $this->createMock(Encoding::class);
        $encodingMock->expects($this->once())
            ->method('encodeString')
            ->with('abcds://localhorst:123some/uri')
            ->will($this->returnValue('encoded url'));
        $poolMock->encodingService = $encodingMock;

        $this->setValueByReflection('pool', $poolMock, $this->controller);
        $this->setValueByReflection(static::TIME_KEEPING, ['first' => 1000, 'second' => 2000], $this->controller);
        $this->controller->timerEndAction();
    }
}
