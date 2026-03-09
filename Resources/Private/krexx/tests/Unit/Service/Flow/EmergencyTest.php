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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Unit\Service\Flow;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Flow\Emergency;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Emergency::class, 'getKrexxCount')]
#[CoversMethod(Emergency::class, 'checkMaxCall')]
#[CoversMethod(Emergency::class, 'initTimer')]
#[CoversMethod(Emergency::class, 'getNestingLevel')]
#[CoversMethod(Emergency::class, 'checkNesting')]
#[CoversMethod(Emergency::class, 'downOneNestingLevel')]
#[CoversMethod(Emergency::class, 'upOneNestingLevel')]
#[CoversMethod(Emergency::class, 'checkRuntime')]
#[CoversMethod(Emergency::class, 'checkMemory')]
#[CoversMethod(Emergency::class, 'checkEmergencyBreak')]
#[CoversMethod(Emergency::class, 'setDisable')]
#[CoversMethod(Emergency::class, '__construct')]
class EmergencyTest extends AbstractHelper
{
    public const  ALL_IS_OK = 'allIsOk';
    public const  MAX_RUNTIME = 'maxRuntime';
    public const  MIN_MEMORY_LEFT = 'minMemoryLeft';
    public const  MAX_CALL = 'maxCall';
    public const  MAX_NESTING_LEVEL = 'maxNestingLevel';
    public const  SERVER_MEMORY_LIMIT = 'serverMemoryLimit';
    public const  NESTING_LEVEL = 'nestingLevel';
    public const  MESSAGE_PARAMETERS = 'params';
    public const  TIMER = 'timer';
    public const  KREXX_COUNT = 'krexxCount';
    public const  FLOW_NAMESPACE = '\\Brainworxx\\Krexx\\Service\\Flow\\';
    public const  INI_GET = 'ini_get';
    public const  MEMORY_GET_USAGE = 'memory_get_usage';
    public const  PHP_SAPI_NAME = 'php_sapi_name';

    /**
     * @var Emergency
     */
    protected $emergency;

    /**
     * Create the emergency class.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->emergency = new Emergency(Krexx::$pool);
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
            ->with(...$this->withConsecutive(
                [Fallback::SETTING_MAX_RUNTIME],
                [Fallback::SETTING_MEMORY_LEFT],
                [Fallback::SETTING_MAX_CALL],
                [Fallback::SETTING_NESTING_LEVEL]
            ))->willReturnMap([
                [Fallback::SETTING_MAX_RUNTIME, '60'],
                [Fallback::SETTING_MEMORY_LEFT, '64'],
                [Fallback::SETTING_MAX_CALL, '10'],
                [Fallback::SETTING_NESTING_LEVEL, '5']
                ]);
        Krexx::$pool->config = $configMock;
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     */
    public function testConstructWithKb()
    {
        $this->setConfigMock();

        // Mock kb memory limit
        $iniGet = $this->getFunctionMock(static::FLOW_NAMESPACE, static::INI_GET);
        $iniGet->expects($this->once())
            ->willReturn('50k');

        $this->emergency = new Emergency(Krexx::$pool);

        // Test setting of pool
        $this->assertSame(Krexx::$pool, $this->retrieveValueByReflection('pool', $this->emergency));
        // Test setting itself in pool
        $this->assertSame($this->emergency, Krexx::$pool->emergencyHandler);
        // Test setting of values from config
        $this->assertEquals(
            60,
            $this->retrieveValueByReflection(static::MAX_RUNTIME, $this->emergency)
        );
        $this->assertEquals(
            64 * 1024 * 1024,
            $this->retrieveValueByReflection(static::MIN_MEMORY_LEFT, $this->emergency)
        );
        $this->assertEquals(
            10,
            $this->retrieveValueByReflection(static::MAX_CALL, $this->emergency)
        );
        $this->assertEquals(
            5,
            $this->retrieveValueByReflection(static::MAX_NESTING_LEVEL, $this->emergency)
        );
        $this->assertEquals(
            50 * 1024,
            $this->retrieveValueByReflection(static::SERVER_MEMORY_LIMIT, $this->emergency)
        );
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     */
    public function testConstructWithMb()
    {
        $this->setConfigMock();

        // Mock MB memory limit.
        $iniGet = $this->getFunctionMock(static::FLOW_NAMESPACE, static::INI_GET);
        $iniGet->expects($this->once())
            ->willReturn('50m');

        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertEquals(
            50 * 1024 * 1024,
            $this->retrieveValueByReflection(static::SERVER_MEMORY_LIMIT, $this->emergency)
        );
    }

    /**
     * Test the caching of several settings, as well as retreating the memory
     * limit.
     */
    public function testConstructWithNoLimit()
    {
        $this->setConfigMock();

        // No limit
        $iniGet = $this->getFunctionMock(static::FLOW_NAMESPACE, static::INI_GET);
        $iniGet->expects($this->once())
            ->willReturn('nothing');

        $this->emergency = new Emergency(Krexx::$pool);
        $this->assertEquals(0, $this->retrieveValueByReflection(static::SERVER_MEMORY_LIMIT, $this->emergency));
    }

    /**
     * Test the disabling of the emergency break.
     */
    public function testDisable()
    {
        $this->assertEquals(false, $this->retrieveValueByReflection(Fallback::SETTING_DISABLED, $this->emergency));
        $this->emergency->setDisable(true);
        $this->assertEquals(true, $this->retrieveValueByReflection(Fallback::SETTING_DISABLED, $this->emergency));
        $this->emergency->setDisable(false);
        $this->assertEquals(false, $this->retrieveValueByReflection(Fallback::SETTING_DISABLED, $this->emergency));
    }

    /**
     * Test disabled.
     */
    public function testCheckEmergencyBreakDisabled()
    {
        $this->setValueByReflection(Fallback::SETTING_DISABLED, true, $this->emergency);
        $this->setValueByReflection(static::ALL_IS_OK, false, $this->emergency);
        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
    }

    /**
     * Test failed before
     */
    public function testCheckEmergencyBreakFailedBefore()
    {
        $this->setValueByReflection(static::ALL_IS_OK, false, $this->emergency);
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
    }

    /**
     * Test failed memory limit.
     */
    public function testCheckEmergencyBreakFailedMemory()
    {
        $this->mockDebugBacktraceStandard();
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 550, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock(static::FLOW_NAMESPACE, static::MEMORY_GET_USAGE);
        $memoryGetUsage->expects($this->once())
            ->willReturn(500);
        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertEquals(false, $this->retrieveValueByReflection(static::ALL_IS_OK, $this->emergency));
        $this->assertNotEmpty(Krexx::$pool->messages->getMessages()['emergencyMemory']);
        $this->assertCount(1, Krexx::$pool->messages->getMessages());
    }

    /**
     * Test with failed runtime check and successful memory check.
     */
    public function testCheckEmergencyBreakFailedRuntime()
    {
        $this->mockDebugBacktraceStandard();

        // Make sure that the memory check succeeds.
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 5000, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock(static::FLOW_NAMESPACE, static::MEMORY_GET_USAGE);
        $memoryGetUsage->expects($this->once())
            ->willReturn(500);

        $phpSapiName = $this->getFunctionMock(static::FLOW_NAMESPACE, static::PHP_SAPI_NAME);
        $phpSapiName->expects($this->once())
            ->willReturn('brauser');

        // Make sure the runtime check fails.
        $this->setValueByReflection(static::TIMER, 12345, $this->emergency);
        $time = $this->getFunctionMock(static::FLOW_NAMESPACE, 'time');
        $time->expects($this->once())
            ->willReturn(92345);

        $this->assertEquals(true, $this->emergency->checkEmergencyBreak());
        $this->assertEquals(false, $this->retrieveValueByReflection(static::ALL_IS_OK, $this->emergency));
        $this->assertNotEmpty(Krexx::$pool->messages->getMessages()['emergencyTimer']);
        $this->assertCount(1, Krexx::$pool->messages->getMessages());
    }

    /**
     * Everything went better than expected.
     */
    public function testCheckEmergencyBreakOk()
    {
        // Make sure that the memory check succeeds.
        $this->setValueByReflection(static::SERVER_MEMORY_LIMIT, 5000, $this->emergency);
        $this->setValueByReflection(static::MIN_MEMORY_LEFT, 100, $this->emergency);
        $memoryGetUsage = $this->getFunctionMock(static::FLOW_NAMESPACE, static::MEMORY_GET_USAGE);
        $memoryGetUsage->expects($this->once())
            ->willReturn(500);
        // Make sure the runtime check succeeds.
        $this->setValueByReflection(static::TIMER, 92345, $this->emergency);
        $time = $this->getFunctionMock(static::FLOW_NAMESPACE, 'time');
        $time->expects($this->once())
            ->willReturn(12345);

        $this->assertEquals(false, $this->emergency->checkEmergencyBreak());
        $this->assertEquals(true, $this->retrieveValueByReflection(static::ALL_IS_OK, $this->emergency));
        $this->assertEquals([], Krexx::$pool->messages->getMessages());
    }

    /**
     * Going up one level.
     */
    public function testUpOneNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->emergency->upOneNestingLevel();
        $this->assertEquals(11, $this->emergency->getNestingLevel());
    }

    /**
     * Going down one nesting level.
     */
    public function testDownOneNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->emergency->downOneNestingLevel();
        $this->assertEquals(9, $this->emergency->getNestingLevel());
    }

    /**
     * Test the nesting level.
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
     */
    public function testGetNestingLevel()
    {
        $this->setValueByReflection(static::NESTING_LEVEL, 10, $this->emergency);
        $this->assertEquals(10, $this->emergency->getNestingLevel());
    }

    /**
     * Test the timer initialization.
     */
    public function testInitTimer()
    {
        $time = $this->getFunctionMock(static::FLOW_NAMESPACE, 'time');
        $time->expects($this->once())
            ->willReturn(5000);
        $this->setValueByReflection(static::MAX_RUNTIME, 60, $this->emergency);

        $this->assertEquals(0, $this->retrieveValueByReflection(static::TIMER, $this->emergency));
        $this->emergency->initTimer();
        $this->assertEquals(5060, $this->retrieveValueByReflection(static::TIMER, $this->emergency));

        $phpSapiName = $this->getFunctionMock(static::FLOW_NAMESPACE, static::PHP_SAPI_NAME);
        $phpSapiName->expects($this->once())
            ->willReturn('brauser');

        // Re-initialize should not change the already existing value.
        $this->emergency->initTimer();
        $this->assertEquals(5060, $this->retrieveValueByReflection(static::TIMER, $this->emergency));
    }

    /**
     * Test the re-initializing of the timer on cli.
     */
    public function testInitTimerOnCli()
    {
        $time = $this->getFunctionMock(static::FLOW_NAMESPACE, 'time');
        $time->expects($this->exactly(2))
            ->willReturn(5000);

        // The sapi gets only called once, because the timer value is empty
        // on the first run.
        $phpSapiName = $this->getFunctionMock(static::FLOW_NAMESPACE, static::PHP_SAPI_NAME);
        $phpSapiName->expects($this->exactly(1))
            ->willReturn('cli');

        $this->emergency->initTimer();
        $this->emergency->initTimer();
    }

    /**
     * Test the checking and up-counting of the krexx counts
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
        $this->assertEquals([], Krexx::$pool->messages->getMessages());

        // Called the last time, with stored feedback Message.
        $this->setValueByReflection(static::KREXX_COUNT, 997, $this->emergency);
        $this->assertFalse($this->emergency->checkMaxCall());
        $this->assertCount(1, Krexx::$pool->messages->getMessages());
        $this->assertEquals('maxCallReached', Krexx::$pool->messages->getMessages()['maxCallReached']->getKey());
    }

    /**
     * Test the getter for the kreXX count
     */
    public function testGetKrexxCount()
    {
        $this->setValueByReflection(static::KREXX_COUNT, 999, $this->emergency);
        $this->assertEquals(999, $this->emergency->getKrexxCount());
    }
}
