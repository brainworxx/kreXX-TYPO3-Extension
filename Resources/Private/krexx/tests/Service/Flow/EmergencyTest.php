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
        $this->setValueByReflection('allIsOk', true, Emergency::class);
        \Brainworxx\Krexx\Service\Flow\memory_get_usage(false);
        \Brainworxx\Krexx\Service\Flow\time(false);
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::__construct
     */
    public function testConstruct()
    {
        // Mock config
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->exactly(12))
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

        // Mock kb memory limit
        \Brainworxx\Krexx\Service\Flow\ini_get('whatever', true, '50k');

        $this->emergency = new Emergency(Krexx::$pool);

        // Test setting of pool
        $this->assertAttributeSame(Krexx::$pool, 'pool', $this->emergency);
        // Test setting itself in pool
        $this->assertSame($this->emergency, Krexx::$pool->emergencyHandler);
        // Test setting of values from config
        $this->assertAttributeEquals(60, 'maxRuntime', $this->emergency);
        $this->assertAttributeEquals(64 * 1024 * 1024, 'minMemoryLeft', $this->emergency);
        $this->assertAttributeEquals(10, 'maxCall', $this->emergency);
        $this->assertAttributeEquals(5, 'maxNestingLevel', $this->emergency);
        $this->assertAttributeEquals(50 * 1024, 'serverMemoryLimit', $this->emergency);

        // Mock MB memory limit.
        \Brainworxx\Krexx\Service\Flow\ini_get('whatever', true, '50m');
        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertAttributeEquals(50 * 1024 * 1024, 'serverMemoryLimit', $this->emergency);

        // No limit
        \Brainworxx\Krexx\Service\Flow\ini_get('whatever', true, 'nothing');
        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertAttributeEquals(0, 'serverMemoryLimit', $this->emergency);

        // Reset the mocking.
        \Brainworxx\Krexx\Service\Flow\ini_get('whatever', false);
    }

    /**
     * Test the disabling of the emergency break.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::setDisable
     */
    public function testDisable()
    {
        $this->assertAttributeEquals(false, 'disabled', $this->emergency);
        $this->emergency->setDisable(true);
        $this->assertAttributeEquals(true, 'disabled', $this->emergency);
        $this->emergency->setDisable(false);
        $this->assertAttributeEquals(false, 'disabled', $this->emergency);
    }

    /**
     * Test disabled.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     */
    public function testCheckEmergencyBreakDisabled()
    {
        $this->setValueByReflection('disabled', true, $this->emergency);
        $this->setValueByReflection('allIsOk', false, Emergency::class);
        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
    }

    /**
     * Test failed before
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkEmergencyBreak
     */
    public function testCheckEmergencyBreakFailedBefore()
    {
        $this->setValueByReflection('allIsOk', false, Emergency::class);
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
        $this->setValueByReflection('serverMemoryLimit', 550, $this->emergency);
        $this->setValueByReflection('minMemoryLeft', 100, $this->emergency);
        \Brainworxx\Krexx\Service\Flow\memory_get_usage(true, 500);
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(false, 'allIsOk', $this->emergency);
        $this->assertEquals(
            ['emergencyMemory' => ['key' => 'emergencyMemory', 'params' => []]],
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
        // Make sure that the memory check succeeds.
        $this->setValueByReflection('serverMemoryLimit', 5000, $this->emergency);
        $this->setValueByReflection('minMemoryLeft', 100, $this->emergency);
        \Brainworxx\Krexx\Service\Flow\memory_get_usage(true, 500);
        // Make sure the runtime check fails.
        $this->setValueByReflection('timer', 12345, $this->emergency);
        \Brainworxx\Krexx\Service\Flow\time(true, 92345);
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(false, 'allIsOk', $this->emergency);
        $this->assertEquals(
            ['emergencyTimer' => ['key' => 'emergencyTimer', 'params' => []]],
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
        $this->setValueByReflection('serverMemoryLimit', 5000, $this->emergency);
        $this->setValueByReflection('minMemoryLeft', 100, $this->emergency);
        \Brainworxx\Krexx\Service\Flow\memory_get_usage(true, 500);
        // Make sure the runtime check succeeds.
        $this->setValueByReflection('timer', 92345, $this->emergency);
        \Brainworxx\Krexx\Service\Flow\time(true, 12345);
        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
        $this->assertAttributeEquals(true, 'allIsOk', $this->emergency);
        $this->assertEquals([], Krexx::$pool->messages->getKeys());
    }

    /**
     * Going up one level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::upOneNestingLevel
     */
    public function testUpOneNestingLevel()
    {
        $this->setValueByReflection('nestingLevel', 10, $this->emergency);
        $this->emergency->upOneNestingLevel();
        $this->assertAttributeEquals(11, 'nestingLevel', $this->emergency);
    }

    /**
     * Going down one nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::downOneNestingLevel
     */
    public function testDownOneNestingLevel()
    {
        $this->setValueByReflection('nestingLevel', 10, $this->emergency);
        $this->emergency->downOneNestingLevel();
        $this->assertAttributeEquals(9, 'nestingLevel', $this->emergency);
    }

    /**
     * Test the nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkNesting
     */
    public function testCheckNesting()
    {
        $this->setValueByReflection('nestingLevel', 10, $this->emergency);
        $this->setValueByReflection('maxNestingLevel', 5, $this->emergency);
        $this->assertEquals(true, $this->emergency->checkNesting());

        $this->setValueByReflection('maxNestingLevel', 10, $this->emergency);
        $this->setValueByReflection('nestingLevel', 5, $this->emergency);
        $this->assertEquals(false, $this->emergency->checkNesting());
    }

    /**
     * Test the getter of the current nesting level.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::getNestingLevel
     */
    public function testGetNestingLevel()
    {
        $this->setValueByReflection('nestingLevel', 10, $this->emergency);
        $this->assertEquals(10, $this->emergency->getNestingLevel());
    }

    /**
     * Test the timer initialization.
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::initTimer
     */
    public function testInitTimer()
    {
        \Brainworxx\Krexx\Service\Flow\time(true, 5000);
        $this->setValueByReflection('maxRuntime', 60, $this->emergency);

        $this->assertAttributeEquals(0, 'timer', $this->emergency);
        $this->emergency->initTimer();
        $this->assertAttributeEquals(5060, 'timer', $this->emergency);

        // Re-initialize should not change the alredy existing value.
        $this->emergency->initTimer();
        $this->assertAttributeEquals(5060, 'timer', $this->emergency);
    }

    /**
     * Test the checking and up-counting of the krexx counts
     *
     * @covers \Brainworxx\Krexx\Service\Flow\Emergency::checkMaxCall
     */
    public function testCheckMaxCall()
    {
        // Called too many times.
        $this->setValueByReflection('krexxCount', 999, $this->emergency);
        $this->setValueByReflection('maxCall', 998, $this->emergency);
        $this->assertTrue($this->emergency->checkMaxCall());

        // Called normally
        $this->setValueByReflection('krexxCount', 0, $this->emergency);
        $this->assertFalse($this->emergency->checkMaxCall());
        $this->assertEquals([], Krexx::$pool->messages->getKeys());

        // Called the last time, with stored feedback Message.
        $this->setValueByReflection('krexxCount', 997, $this->emergency);
        $this->assertFalse($this->emergency->checkMaxCall());
        $this->assertEquals(
            ['maxCallReached' => ['key' => 'maxCallReached', 'params' => []]],
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
        $this->setValueByReflection('krexxCount', 999, $this->emergency);
        $this->assertEquals(999, $this->emergency->getKrexxCount());
    }
}
