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

namespace Brainworxx\Krexx\Tests;

use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\TimerController;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Tests\Helpers\AbstractTest;
use Brainworxx\Krexx\Tests\Helpers\ConfigSupplier;

class KrexxTest extends AbstractTest
{

    const KREXX_COUNT = 'krexxCount';
    const TIME_KEEPING = 'timekeeping';
    const COUNTER_CACHE = 'counterCache';

    protected function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach ($files as $value) {
            $path = realpath($dir . DIRECTORY_SEPARATOR . $value);
            if (is_dir($path) === false) {
                $results[] = $path;
            } elseif ($value != "." && $value != "..") {
                $this->getDirContents($path, $results);
            }
        }

        return $results;
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
            $result = interface_exists($className) || class_exists($className);
            $this->assertTrue($result, 'Interface orclass exists: ' . $className);
        }
    }

    /**
     * Test if we can take a moment while kreXX is disabled.
     *
     * @covers \Krexx::timerMoment
     */
    public function testTimerMomentDisabled()
    {
        // Disable it
        \Krexx::disable();

        // Active?
        // Expecting empty arrays.
        \Krexx::timerMoment('test');
        $this->assertAttributeEquals(
            [],
            static::COUNTER_CACHE,
            TimerController::class
        );
        $this->assertAttributeEquals(
            [],
            static::TIME_KEEPING,
            TimerController::class
        );
    }

    /**
     * Test if we can take a moment while kreXX is analying something else.
     *
     * @covers \Krexx::timerMoment
     */
    public function testTimerMomentInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        // Active?
        // Expecting empty arrays.
        \Krexx::timerMoment('test');
        $this->assertAttributeEquals(
            [],
            static::COUNTER_CACHE,
            TimerController::class
        );
        $this->assertAttributeEquals(
            [],
            static::TIME_KEEPING,
            TimerController::class
        );
    }

    /**
     * Testing, if it can be disabled, as expected.
     *
     * @covers \Krexx::timerMoment
     */
    public function testTimerMomentEnabled()
    {
        // Test the normal behaviour.
        \Krexx::timerMoment('test');

        $this->assertAttributeEquals(
            ['test' => 1],
            static::COUNTER_CACHE,
            TimerController::class
        );
        $this->assertAttributeNotEmpty(
            static::TIME_KEEPING,
            TimerController::class
        );
    }

    /**
     * Testing if we get an output while kreXX is disabled.
     *
     * @covers \Krexx::timerEnd
     */
    public function testTimerEndDisabled()
    {
        // Disable it
        \Krexx::$pool->config->setDisabled(true);

        // Active?
        // We should have a output call counter of zero.
        \Krexx::timerEnd();
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     *
     * @covers \Krexx::timerEnd
     */
    public function testTimerEndInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        // Active?
        // We should have a output call counter of zero.
        \Krexx::timerEnd();
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output at all.
     *
     * @covers \Krexx::timerEnd
     */
    public function testTimerEndNormal()
    {
        \Krexx::timerEnd();
        // The counter should go up to 1
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output when disabled.
     *
     * @covers \Krexx::open
     */
    public function testOpenDisabled()
    {
        // Disable it
        \Krexx::$pool->config->setDisabled(true);

        \Krexx::open();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     *
     * @covers \Krexx::open
     */
    public function testOpenInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        \Krexx::open();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output at all.
     *
     * @covers \Krexx::open
     */
    public function testOpen()
    {
        \Krexx::open();
        // The counter should be at 1.
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output when disabled.
     *
     * @covers \Krexx::backtrace
     */
    public function testBacktraceDisabled()
    {
        // Disable it
        \Krexx::$pool->config->setDisabled(true);

        \Krexx::backtrace();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output, while another analysis is in progress.
     *
     * @covers \Krexx::backtrace
     */
    public function testBacktraceInProgress()
    {
        // Disable it by acting like we are in the middle of an analysis.
        AbstractController::$analysisInProgress = true;

        \Krexx::backtrace();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can get an output at all.
     *
     * @covers \Krexx::backtrace
     */
    public function testBacktrace()
    {
        // We make this a short one.
        \Krexx::$pool->config->settings[Fallback::SETTING_MAX_STEP_NUMBER]->setValue(1);

        \Krexx::backtrace();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if it sets the value if kreXX beeing disabled.
     *
     * @covers \Krexx::disable
     */
    public function testDisable()
    {
        $this->assertFalse(\Krexx::$pool->config->getSetting(Fallback::SETTING_DISABLED));
        \Krexx::disable();
        $this->assertTrue(\Krexx::$pool->config->getSetting(Fallback::SETTING_DISABLED));
    }

    /**
     * Test if we can get an output when disabled.
     *
     * @covers \Krexx::editSettings
     */
    public function testEditSettingsDisabled()
    {
        // Disable it
        \Krexx::$pool->config->setDisabled(true);

        \Krexx::editSettings();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Test if we can register our fatal error handler.
     *
     * @covers \Krexx::registerFatal
     * @incomplete
     *   We only test the php version.
     */
    public function testRegisterFatal()
    {
        ob_start();
        \Krexx::registerFatal();
        $output = ob_get_contents();
        ob_end_clean();

        $this->assertNotFalse(strpos($output, 'PHP7'));
    }

    /**
     * Test if we can unregister our fatal error handler.
     *
     * @covers \Krexx::unregisterFatal
     */
    public function testUnregisterFatal()
    {
        // Create a mock of the error handler.
        $errorMock = $this->createMock(
            \Brainworxx\Krexx\Errorhandler\Fatal::class
        );
        $errorMock->expects($this->once())
            ->method('setIsActive')
            ->will($this->returnValue(null));

        // Inject it into the controller.
        $this->setValueByReflection('krexxFatal', $errorMock, AbstractController::class);
        \Krexx::unregisterFatal();
    }

    /**
     * Prepare the forced logger test.
     */
    protected function beginForcedLogger()
    {
        // Create two settings model mocks
        $settingsMockDest = $this->createMock(Model::class);
        $settingsMockDest->expects($this->once())
            ->method('setSource')
            ->with($this->equalTo('forced logging'))
            ->will($this->returnValue($settingsMockDest));
        $settingsMockDest->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo(Fallback::VALUE_FILE));
        $settingsMockDest->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(Fallback::VALUE_FILE));

        $settingsMockAjax = $this->createMock(Model::class);
        $settingsMockAjax->expects($this->once())
            ->method('setSource')
            ->with($this->equalTo('forced logging'))
            ->will($this->returnValue($settingsMockAjax));
        $settingsMockAjax->expects($this->once())
            ->method('setValue')
            ->with($this->equalTo(false));
        $settingsMockAjax->expects($this->any())
            ->method('getValue')
            ->will($this->returnValue(false));

        // Inject the mock into the settings
        \Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION] = $settingsMockDest;
        \Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX] = $settingsMockAjax;
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
        $this->assertNotEquals($settingsMockDest, \Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION]);
        $this->assertNotEquals($settingsMockAjax, \Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX]);

        // Test if we have a logfile.
        $filesystemIterator = new \FilesystemIterator(
            \krexx::$pool->config->getLogDir(),
            \FilesystemIterator::SKIP_DOTS
        );
        $this->assertEquals(4, iterator_count($filesystemIterator));
    }

    /**
     * Test the forced logger.
     *
     * @covers \Krexx::log
     * @covers \Krexx::startForcedLog
     * @covers \Krexx::endForcedLog
     */
    public function testLog()
    {
        $this->beginForcedLogger();
        $settingsMockDest = \Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
        $settingsMockAjax = \Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

        // Run a simple analysis.
        \Krexx::log();

        // The counter should be at 1.
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );

        $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing the backtrace logger.
     *
     * @covers \Krexx::logBacktrace
     * @covers \Krexx::startForcedLog
     * @covers \Krexx::endForcedLog
     */
    public function testLogBacktrace()
    {
        $this->beginForcedLogger();
        $settingsMockDest = \Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
        $settingsMockAjax = \Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

        // We make this a short one.
        \Krexx::$pool->config->settings[Fallback::SETTING_MAX_STEP_NUMBER]->setValue(1);

        \Krexx::logBacktrace();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );

        $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing the timer logging.
     *
     * @covers \Krexx::logTimerEnd
     * @covers \Krexx::startForcedLog
     * @covers \Krexx::endForcedLog
     */
    public function testLogTimerEnd()
    {
         $this->beginForcedLogger();
         $settingsMockDest = \Krexx::$pool->config->settings[Fallback::SETTING_DESTINATION];
         $settingsMockAjax = \Krexx::$pool->config->settings[Fallback::SETTING_DETECT_AJAX];

         \Krexx::logTimerEnd();
         // The counter should go up to 1
         $this->assertAttributeEquals(
             1,
             static::KREXX_COUNT,
             \Krexx::$pool->emergencyHandler
         );

         $this->endForcedLogger($settingsMockDest, $settingsMockAjax);
    }

    /**
     * Testing a call with a wrong developer handle.
     *
     * @covers \Krexx::__callStatic
     */
    public function testCallStatic()
    {
        $configMock = $this->createPartialMock(Config::class, ['getDevHandler']);
        $configMock->expects($this->any())
            ->method('getDevHandler')
            ->will($this->returnValue('someValue'));

        // The setup does not get executed, hence we get these settings from
        // the already existing ones in the original class.
        $configMock->settings =  \Krexx::$pool->config->settings;
        $this->setValueByReflection(
            'directories',
            [
                'log' => \Krexx::$pool->config->getLogDir(),
                'chunks' => \Krexx::$pool->config->getChunkDir(),
                'config' => \Krexx::$pool->config->getPathToIniFile()
            ],
            $configMock
        );
        // Inject the mock.
        \Krexx::$pool->config = $configMock;

        \Krexx::whatever();
        // The counter should be at 0.
        $this->assertAttributeEquals(
            0,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );

        \Krexx::someValue();
        // The counter should be at 1.
        $this->assertAttributeEquals(
            1,
            static::KREXX_COUNT,
            \Krexx::$pool->emergencyHandler
        );
    }

    /**
     * Testing, if kreXX is disabled, if the call comes from the wrong IP.
     *
     * @covers \Krexx::open
     */
    public function testDisabledByIp()
    {
        // The ip settings are read as soon as the configuration is created.
        // Setting them afterwards is not possible.
        \Krexx::$pool->rewrite[Ini::class] = ConfigSupplier::class;
        ConfigSupplier::$overwriteValues[Fallback::SETTING_IP_RANGE] = '987.654.321.123';
        // Inject the IP.
        $_SERVER[Config::REMOTE_ADDRESS] = '123.456.789.123';

        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(\Krexx::$pool);
        // Run the test
        $this->assertTrue($config::$disabledByPhp);

        // Inject another ip.
        $_SERVER[Config::REMOTE_ADDRESS] = '987.654.321.123';
        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(\Krexx::$pool);
        $this->assertFalse($config::$disabledByPhp);

        // Testing the wildcards.
        ConfigSupplier::$overwriteValues[Fallback::SETTING_IP_RANGE] = '987.654.321.*';
         // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(\Krexx::$pool);
        $this->assertFalse($config::$disabledByPhp);

        // Inject another ip.
        $_SERVER[Config::REMOTE_ADDRESS] = '123.654.321.123';
        // Reset the config.
        Config::$disabledByPhp = false;
        $config = new Config(\Krexx::$pool);
        // Run the test
        $this->assertTrue($config::$disabledByPhp);
    }
}
