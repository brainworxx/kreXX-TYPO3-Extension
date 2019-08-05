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

namespace Brainworxx\Krexx\Tests\Service\Flow;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;

class EmergencyTest extends AbstractTest
{

    const ALL_IS_OK = 'allIsOk';
    const MAX_RUNTIME = 'maxRuntime';
    const MIN_MEMORY_LEFT = 'minMemoryLeft';
    const MAX_CALL = 'maxCall';
    const MAX_NESTING_LEVEL = 'maxNestingLevel';
    const SERVER_MEMORY_LIMIT = 'serverMemoryLimit';
    const NESTING_LEVEL = 'nestingLevel';
    const MESSAGE_PARAMETERS = 'params';
    const TIMER = 'timer';
    const KREXX_COUNT = 'krexxCount';

    /**
     * @var Emergency
     */
    protected $emergency;

    /**
     * Create the emergency class.
     */
    protected function setUp()
    {
        parent::setUp();
        $this->emergency = new Emergency(Krexx::$pool);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();
        $this->setValueByReflection(static::ALL_IS_OK, true, Emergency::class);
    }

    /**
     * Inject the configuration mack.
     */
    protected function setConfigMock()
    {
        // Mock config
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(4))
            ->method('getSetting')
            ->withConsecutive(
                [Fallback::SETTING_MAX_RUNTIME],
                [Fallback::SETTING_MEMORY_LEFT],
                [Fallback::SETTING_MAX_CALL],
                [Fallback::SETTING_NESTING_LEVEL]
            )
            ->will($this->returnValueMap([
                [Fallback::SETTING_MAX_RUNTIME, '60'],
                [Fallback::SETTING_MEMORY_LEFT, '64'],
                [Fallback::SETTING_MAX_CALL, '10'],
                [Fallback::SETTING_NESTING_LEVEL, '5']
                ]));
        Krexx::$pool->config = $configMock;
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::__construct
     */
    public function testConstructWithKb()
    {
        $this->setConfigMock();

        // Mock kb memory limit
        $iniGet = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'ini_get');
        $iniGet->expects($this->once())
            ->will($this->returnValue('50k'));

        $this->emergency = new Emergency(Krexx::$pool);

        // Test setting of pool
        $this->assertAttributeSame(Krexx::$pool, 'pool', $this->emergency);
        // Test setting itself in pool
        $this->assertSame($this->emergency, Krexx::$pool->emergencyHandler);
        // Test setting of values from config
        $this->assertAttributeEquals(60, static::MAX_RUNTIME, $this->emergency);
        $this->assertAttributeEquals(64 * 1024 * 1024, static::MIN_MEMORY_LEFT, $this->emergency);
        $this->assertAttributeEquals(10, static::MAX_CALL, $this->emergency);
        $this->assertAttributeEquals(5, static::MAX_NESTING_LEVEL, $this->emergency);
        $this->assertAttributeEquals(50 * 1024, static::SERVER_MEMORY_LIMIT, $this->emergency);
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::__construct
     */
    public function testConstructWithMb()
    {
        $this->setConfigMock();

        // Mock MB memory limit.
        $iniGet = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'ini_get');
        $iniGet->expects($this->once())
            ->will($this->returnValue('50m'));

        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertAttributeEquals(50 * 1024 * 1024, static::SERVER_MEMORY_LIMIT, $this->emergency);
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::__construct
     */
    public function testConstructWithNoLimit()
    {
        $this->setConfigMock();

        // No limit
        $iniGet = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'ini_get');
        $iniGet->expects($this->once())
            ->will($this->returnValue('nothing'));

        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertAttributeEquals(0, static::SERVER_MEMORY_LIMIT, $this->emergency);
    }

    /**
     * Test the disabling of the emergency break.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::setDisable
     */
    public function testDisable()
    {
        $this->assertAttributeEquals(false, Fallback::SETTING_DISABLED, $this->emergency);
        $this->emergency->setDisable(true);
        $this->assertAttributeEquals(true, Fallback::SETTING_DISABLED, $this->emergency);
        $this->emergency->setDisable(false);
        $this->assertAttributeEquals(false, Fallback::SETTING_DISABLED, $this->emergency);
    }

    /**
     * Test disabled.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     */
    public function testCheckEmergencyBreakDisabled()
    {
        $this->setValueByReflection(Fallback::SETTING_DISABLED, true, $this->emergency);
        $this->setValueByReflection(static::ALL_IS_OK, false, Emergency::class);
        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
    }

    /**
     * Test failed before
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     */
    public function testCheckEmergencyBreakFailedBefore()
    {
        $this->setValueByReflection(static::ALL_IS_OK, false, Emergency::class);
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
    }

    /**
     * Test failed memory limit.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkMemory
     */
    public function testCheckEmergencyBreakFailedMemory()
    {
        $this->mockDebugBacktraceStandard();
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 550, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'memory_get_usage');
        $memoryGetUsage->expects($this->once())
            ->will($this->returnValue(500));
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(false, static::ALL_IS_OK, $this->emergency);
        $this->assertEquals(
            ['emergencyMemory' => ['key' => 'emergencyMemory', static::MESSAGE_PARAMETERS => []]],
            Krexx::$pool->messages->getKeys()
        );
    }

    /**
     * Test with failed runtime check and successful memory check.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkMemory
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkRuntime
     */
    public function testCheckEmergencyBreakFailedRuntime()
    {
        $this->mockDebugBacktraceStandard();

        // Make sure that the memory check succeeds.
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 5000, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'memory_get_usage');
        $memoryGetUsage->expects($this->once())
            ->will($this->returnValue(500));
        // Make sure the runtime check fails.
        $this->setValueByReflection(static::TIMER, 12345, $this->emergency);
        $time = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'time');
        $time->expects($this->once())
            ->will($this->returnValue(92345));

        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(false, static::ALL_IS_OK, $this->emergency);
        $this->assertEquals(
            ['emergencyTimer' => ['key' => 'emergencyTimer', static::MESSAGE_PARAMETERS => []]],
            Krexx::$pool->messages->getKeys()
        );
    }

    /**
     * Everything went better than expected.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkMemory
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkRuntime
     */
    public function testCheckEmergencyBreakOk()
    {
        // Make sure that the memory check succeeds.
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 5000, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'memory_get_usage');
        $memoryGetUsage->expects($this->once())
            ->will($this->returnValue(500));
        // Make sure the runtime check succeeds.
        $this->setValueByReflection(static::TIMER, 92345, $this->emergency);
        $time = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'time');
        $time->expects($this->once())
            ->will($this->returnValue(12345));

        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(true, static::ALL_IS_OK, $this->emergency);
        $this->assertEquals([], Krexx::$pool->messages->getKeys());
    }

    /**
     * Going up one level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::upOneNestingLevel
     */
    public function testUpOneNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->emergency->upOneNestingLevel();
        $this->assertAttributeEquals(11, static::NESTING_LEVEL, $this->emergency);
    }

    /**
     * Going down one nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::downOneNestingLevel
     */
    public function testDownOneNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->emergency->downOneNestingLevel();
        $this->assertAttributeEquals(9, static::NESTING_LEVEL, $this->emergency);
    }

    /**
     * Test the nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkNesting
     */
    public function testCheckNesting()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->setValueByReflection(static::MAX_NESTING_LEVEL, 5, $this->emergency);
        $this->assertEquals(true, $this->emergency->checkNesting());

        $this->setValueByReflection(static::MAX_NESTING_LEVEL, 10, $this->emergency);
        $this->setValueByReflection(static::NESTING_LEVEL, 5, $this->emergency);
        $this->assertEquals(false, $this->emergency->checkNesting());
    }

    /**
     * Test the getter of the current nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::getNestingLevel
     */
    public function testGetNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->assertEquals(10, $this->emergency->getNestingLevel());
    }

    /**
     * Test the timer initialization.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::initTimer
     */
    public function testInitTimer()
    {
        $time = $this->getFunctionMock('\\Brainworxx\\Krexx\\Service\\Flow\\', 'time');
        $time->expects($this->once())
            ->will($this->returnValue(5000));
        $this->setValueByReflection(static::MAX_RUNTIME, 60, $this->emergency);

        $this->assertAttributeEquals(0, static::TIMER, $this->emergency);
        $this->emergency->initTimer();
        $this->assertAttributeEquals(5060, static::TIMER, $this->emergency);

        // Re-initialize should not change the alredy existing value.
        $this->emergency->initTimer();
        $this->assertAttributeEquals(5060, static::TIMER, $this->emergency);
    }

    /**
     * Test the checking and up-counting of the krexx counts
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkMaxCall
     */
    public function testCheckMaxCall()
    {
        // Called too many times.
        $this->setValueByReflection(static::KREXX_COUNT, 999, $this->emergency);
        $this->setValueByReflection(static::MAX_CALL, 998, $this->emergency);
        $this->assertTrue($this->emergency->checkMaxCall());

        // Called normally
        $this->setValueByReflection(static::KREXX_COUNT, 0, $this->emergency);
        $this->assertFalse($this->emergency->checkMaxCall());
        $this->assertEquals([], Krexx::$pool->messages->getKeys());

        // Called the last time, with stored feedback Message.
        $this->setValueByReflection(static::KREXX_COUNT, 997, $this->emergency);
        $this->assertFalse($this->emergency->checkMaxCall());
        $this->assertEquals(
            ['maxCallReached' => ['key' => 'maxCallReached', static::MESSAGE_PARAMETERS => []]],
            Krexx::$pool->messages->getKeys()
        );
    }

    /**
     * Test the getter for the kreXX count
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::getKrexxCount
     */
    public function testGetKrexxCount()
    {
        $this->setValueByReflection(static::KREXX_COUNT, 999, $this->emergency);
        $this->assertEquals(999, $this->emergency->getKrexxCount());
    }
}
