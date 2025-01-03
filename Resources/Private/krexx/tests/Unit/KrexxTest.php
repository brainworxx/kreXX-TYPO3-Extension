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

namespace Brainworxx\Krexx\Tests\Unit;

use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\TimerController;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;
use Brainworxx\Krexx\Krexx;
use FilesystemIterator;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(Krexx::class, 'timerMoment')]
#[CoversMethod(Krexx::class, 'open')]
#[CoversMethod(Krexx::class, 'backtrace')]
#[CoversMethod(Krexx::class, 'disable')]
#[CoversMethod(Krexx::class, 'editSettings')]
#[CoversMethod(Krexx::class, 'timerEnd')]
#[CoversMethod(Krexx::class, 'logTimerEnd')]
#[CoversMethod(Krexx::class, 'startForcedLog')]
#[CoversMethod(Krexx::class, 'endForcedLog')]
#[CoversMethod(Krexx::class, 'logBacktrace')]
#[CoversMethod(Krexx::class, 'log')]
class KrexxTest extends AbstractHelper
{
    public const KREXX_COUNT = 'krexxCount';
    public const TIME_KEEPING = 'timekeeping';
    public const COUNTER_CACHE = 'counterCache';
    public const CONTROLLER_NAMESPACE = '\\Brainworxx\\Krexx\\Controller\\';

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->setValueByReflection(static::COUNTER_CACHE, [], TimerController::class);
        $this->setValueByReflection(static::TIME_KEEPING, [], TimerController::class);
    }

    protected function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (is_dir($path) === false) {
                if ($this->endsWith($path, '.php')) {
                    $results[] = $path;
                }
            } elseif ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
            }
        }

        return $results;
    }

    /**
     * Find out, if a string ends with a certain string.
     *
     * @param $string
     * @param $test
     * @return bool
     */
    protected function endsWith($string, $test)
    {
        $stringLength = strlen($string);
        $testLength = strlen($test);
        if ($testLength > $stringLength) {
            return false;
        }
        return substr_compare($string, $test, $stringLength - $testLength, $testLength) === 0;
    }

    /**
     * Testing the bootstrapping for class loading and creating
     * the krexx() shorthand.
     */
    public function testBootstrapKrexx()
    {
        // Testing the simple stuff
        $this->assertDirectoryExists(KREXX_DIR);
        $this->assertTrue(function_exists('krexx'));

        // Test if all class and interface classes are included.
        $nameSpace = 'Brainworxx\\Krexx';
        $dir = KREXX_DIR . 'src';
        $fileList = $this->getDirContents(KREXX_DIR . 'src');
        foreach ($fileList as $file) {
            $className = $nameSpace . str_replace(
                array($dir, '.php', DIRECTORY_SEPARATOR),
                array('', '', '\\'),
                $file
            );
            $result = interface_exists($className) || class_exists($className) || trait_exists($className);
            $this->assertTrue($result, 'Interface or class exists: ' . $className);
        }
    }

    /**
     * Test if we can take a moment while kreXX is disabled.
     *
     */
    public function testTimerMomentDisabled()
    {
        // Disable it
        Krexx::disable();

        // Active?
        // Expecting empty arrays.
        Krexx::timerMoment('test');
        $this->assertEquals([], $this->retrieveValueByReflection(static::COUNTER_CACHE, TimerController::class));
        $this->assertEquals([], $this->retrieveValueByReflection(static::TIME_KEEPING, TimerController::class));
    }

    /**
     * Test if we can take a moment while kreXX is analying something else.
     *
     */
    public function testTimerMomentInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        // Active?
        // Expecting empty arrays.
        Krexx::timerMoment('test');
        $this->assertEquals([], $this->retrieveValueByReflection(static::COUNTER_CACHE, TimerController::class));
        $this->assertEquals([], $this->retrieveValueByReflection(static::TIME_KEEPING, TimerController::class));
    }

    /**
     * Testing, if it can be disabled, as expected.
     *
     */
    public function testTimerMomentEnabled()
    {
        // Test the normal behaviour.
        Krexx::timerMoment('test');

        $this->assertEquals(
            ['test' => 1],
            $this->retrieveValueByReflection(static::COUNTER_CACHE, TimerController::class)
        );
        $this->assertNotEmpty(
            $this->retrieveValueByReflection(static::TIME_KEEPING, TimerController::class)
        );
    }

    /**
     * Testing if we get an output while kreXX is disabled.
     */
    public function testTimerEndDisabled()
    {
        // Disable it
        Krexx::$pool->config->setDisabled(true);

        // Active?
        // We should have a output call counter of zero.
        Krexx::timerEnd();
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     */
    public function testTimerEndInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        // Active?
        // We should have a output call counter of zero.
        Krexx::timerEnd();
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output at all.
     */
    public function testTimerEndNormal()
    {
        $this->mockDebugBacktraceStandard();
        Krexx::timerEnd();
        // The counter should go up to 1
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output when disabled.
     */
    public function testOpenDisabled()
    {
        // Disable it
        Krexx::$pool->config->setDisabled(true);

        Krexx::open();
        // The counter should be at 0.
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     */
    public function testOpenInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        Krexx::open();
        // The counter should be at 0.
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output at all.
     */
    public function testOpen()
    {
        $this->mockDebugBacktraceStandard();

        Krexx::open();
        // The counter should be at 1.
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output when disabled.
     */
    public function testBacktraceDisabled()
    {
        // Disable it
        Krexx::$pool->config->setDisabled(true);

        Krexx::backtrace();
        // The counter should be at 0.
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     */
    public function testBacktraceInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        Krexx::backtrace();
        // The counter should be at 0.
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output at all.
     */
    public function testBacktrace()
    {
        // We make this a short one.
        Krexx::$pool->config->settings[Fallback::SETTING_MAX_STEP_NUMBER]->setValue(1);
        $this->mockDebugBacktraceStandard();

        Krexx::backtrace();
        // The counter should be at 0.
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if it sets the value if kreXX beeing disabled.
     */
    public function testDisable()
    {
        $this->assertFalse(Krexx::$pool->config->getSetting(Fallback::SETTING_DISABLED));
        Krexx::disable();
        $this->assertTrue(Krexx::$pool->config->getSetting(Fallback::SETTING_DISABLED));
    }

    /**
     * Test the edit settings, normally.
     */
    public function testEditSettings()
    {
        $this->mockDebugBacktraceStandard();
        Krexx::editSettings();
        // The counter should be at 1.
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Test if we can get an output when disabled.
     */
    public function testEditSettingsDisabled()
    {
        // Disable it
        Krexx::$pool->config->setDisabled(true);

        Krexx::editSettings();
        // The counter should be at 0.
        $this->assertEquals(0, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));
    }

    /**
     * Prepare the forced logger test.
     */
    protected function beginForcedLogger()
    {
        $forcedLogging = 'Forced logging';

        // Create two settings model mocks
        $settingsMockDest = $this->createMock(Model::class);
        $settingsMockDest->expects($this->once())
            ->method('setSource')
            ->with($this->equalTo($forcedLogging))
            ->willReturn($settingsMockDest);
        $settingsMockDest->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo(Fallback::VALUE_FILE));
        $settingsMockDest->expects($this->any())
            ->method('getValue')
            ->willReturn(Fallback::VALUE_FILE);
        $settingsMockDest->expects($this->once())
            ->method('getSource')
            ->willReturn($forcedLogging);

        $settingsMockAjax = $this->createMock(Model::class);
        $settingsMockAjax->expects($this->once())
            ->method('setSource')
            ->with($this->equalTo($forcedLogging))
            ->willReturn($settingsMockAjax);
        $settingsMockAjax->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo(false));
        $settingsMockAjax->expects($this->any())
            ->method('getValue')
            ->willReturn(false);
        $settingsMockAjax->expects($this->once())
            ->method('getSource')
            ->willReturn($forcedLogging);

        // Inject the mock into the settings
        Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION] = $settingsMockDest;
        Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX] = $settingsMockAjax;
    }

    /**
     * Test, if the forced logger worked as expected afterwards.
     *
     * @param $settingsMockDest
     * @param $settingsMockAjax
     */
    protected function endForcedLogger($settingsMockDest, $settingsMockAjax)
    {
        // Test if the mock are gone.
        $this->assertNotEquals($settingsMockDest, Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION]);
        $this->assertNotEquals($settingsMockAjax, Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX]);

        // Test if we have a logfile.
        $filesystemIterator = new FilesystemIterator(
            Krexx::$pool->config->getLogDir(),
            FilesystemIterator::SKIP_DOTS
        );
        $this->assertEquals(4, iterator_count($filesystemIterator));
    }

    /**
     * Test the forced logger.
     */
    public function testLog()
    {
        $this->mockDebugBacktraceStandard();

        $this->beginForcedLogger();
        $settingsMockDest = Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
        $settingsMockAjax = Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

        // Run a simple analysis.
        Krexx::log();

        // The counter should be at 1.
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));

        $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing the backtrace logger.
     */
    public function testLogBacktrace()
    {
        $this->mockDebugBacktraceStandard();

        $this->beginForcedLogger();
        $settingsMockDest = Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
        $settingsMockAjax = Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

        // We make this a short one.
        Krexx::$pool->config->settings[Fallback::SETTING_MAX_STEP_NUMBER]->setValue(1);

        Krexx::logBacktrace();
        // The counter should be at 0.
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));

        $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing the timer logging.
     */
    public function testLogTimerEnd()
    {
        $this->mockDebugBacktraceStandard();

        $this->beginForcedLogger();
        $settingsMockDest = Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
        $settingsMockAjax = Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

        Krexx::logTimerEnd();
        // The counter should go up to 1
        $this->assertEquals(1, $this->retrieveValueByReflection(static::KREXX_COUNT, Krexx::$pool->emergencyHandler));

        $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing, if kreXX is disabled, if the call comes from the wrong IP.
     */
    public function testDisabledByIp()
    {
        // The ip settings are read as soon as the configuration is created.
        // Setting them afterwards is not possible.
        Krexx::$pool->rewrite[File::class] = ConfigSupplier::class;
        ConfigSupplier::$overwriteValues[Fallback::SETTING_IP_RANGE] = '987.654.321.123';
        // Inject the IP.
        $_SERVER['REMOTE_ADDR'] = '123.456.789.123';

        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(Krexx::$pool);
        // Run the test
        $this->assertTrue($config::$disabledByPhp);

        // Inject another ip.
        $_SERVER['REMOTE_ADDR'] = '987.654.321.123';
        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(Krexx::$pool);
        $this->assertFalse($config::$disabledByPhp);

        // Testing the wildcards.
        ConfigSupplier::$overwriteValues[Fallback::SETTING_IP_RANGE] = '987.654.321.*';
         // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(Krexx::$pool);
        $this->assertFalse($config::$disabledByPhp);

        // Inject another ip.
        $_SERVER['REMOTE_ADDR'] = '123.654.321.123';
        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(Krexx::$pool);
        // Run the test
        $this->assertTrue($config::$disabledByPhp);
    }
}
