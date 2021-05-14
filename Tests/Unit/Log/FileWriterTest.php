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

namespace Brainworxx\Includekrexx\Tests\Unit\Log;

use Brainworxx\Includekrexx\Log\FileWriter;
use Brainworxx\Includekrexx\Plugins\Typo3\ConstInterface;
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Includekrexx\Tests\Helpers\ControllerNothing;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Logging\Model as LogModel;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use stdClass;

class FileWriterTest extends AbstractTest implements BacktraceConstInterface, ConstInterface
{
    const REQUEST_URI  = 'REQUEST_URI';
    const REQUEST_URI_VAR = 'requestURIvar';
    const REMOTE_ADDR = 'REMOTE_ADDR';
    const ORIG_SCRIPT_NAME = 'ORIG_SCRIPT_NAME';
    const QUERY_STRING = 'QUERY_STRING';

    public function krexxUp()
    {
        parent::krexxUp();

        $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REQUEST_URI_VAR] = '';
        $_SERVER[static::REQUEST_URI] = '';
        $_SERVER[static::REMOTE_ADDR] = '';
        $_SERVER[static::ORIG_SCRIPT_NAME] = '';
        $_SERVER[static::QUERY_STRING] = '';
    }

    /**
     * {@inheritDoc}
     */
    public function krexxDown()
    {
        parent::krexxDown();
        ControllerNothing::$count = 0;
        ControllerNothing::$data = [];
        ControllerNothing::$level = [];
        ControllerNothing::$message = [];

        unset($_SERVER[static::REQUEST_URI]);
        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REQUEST_URI_VAR]);
        unset($_SERVER[static::REMOTE_ADDR]);
        unset($_SERVER[static::ORIG_SCRIPT_NAME]);
        unset($_SERVER[static::QUERY_STRING]);
    }

    /**
     * Test the setting of the configuration.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::__construct
     */
    public function testConstruct()
    {
        $config = ['some' => 'value'];
        $fileWriter = new FileWriter($config);

        $this->assertEquals(
            $config,
            $this->retrieveValueByReflection('localConfig', $fileWriter),
            'Test the setting of the configuration.'
        );
    }

    /**
     * Simple test with no configuration.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::applyTheConfiguration
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveBacktrace
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveLogLevel
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::prepareLogModelNormal
     */
    public function testWriteLogNormal()
    {
        $fixture = $this->prepareFixture();
        $config = [];

        $fileWriter = new FileWriter($config);
        $fileWriter->writeLog($fixture);

        $this->assertEquals(1, ControllerNothing::$count);
        $level = $fixture->getLevel();
        if (is_integer($level)) {
            $level = LogLevel::getName($level);
        }
        $this->assertEquals(strtolower($level), ControllerNothing::$level[0]);
        /** @var \Brainworxx\Krexx\Logging\Model $logModel */
        $logModel = ControllerNothing::$data[0];
        $this->assertInstanceOf(LogModel::class, $logModel);
        $this->assertEquals($fixture->getMessage(), $logModel->getMessage());
        $this->assertEquals($fixture->getComponent(), $logModel->getCode());
        $backtrace = $logModel->getTrace();

        if (empty($backtrace[0][static::TRACE_FILE]) === true) {
            $this->assertEmpty($logModel->getFile());
        } else {
            $this->assertEquals($backtrace[0][static::TRACE_FILE], $logModel->getFile());
        }

        if (empty($backtrace[0][static::TRACE_LINE]) === true) {
            $this->assertEmpty($logModel->getLine());
        } else {
            $this->assertEquals($backtrace[0][static::TRACE_LINE], $logModel->getLine());
        }
    }

    /**
     * Test the setting of configurations during the logging.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::applyTheConfiguration
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveBacktrace
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveLogLevel
     */
    public function testWriteLogWithConfig()
    {
        $config = [
            Fallback::SETTING_ANALYSE_SCALAR => 'true',
            'mehSetting' => 'blargh'
        ];

        $configModelMock = $this->createMock(Model::class);
        $configModelMock->expects($this->once())
            ->method('setValue')
            ->with($config[Fallback::SETTING_ANALYSE_SCALAR])
            ->will($this->returnValue($configModelMock));
        $configModelMock->expects(($this->once()))
            ->method('setSource')
            ->with(FileWriter::KREXX_LOG_WRITER);
        \Krexx::$pool->config->settings[Fallback::SETTING_ANALYSE_SCALAR] = $configModelMock;

        $fileWriter = new FileWriter($config);
        $fileWriter->writeLog($this->prepareFixture());
    }

    /**
     * Test the early return, in case kreXX ist disabled.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::applyTheConfiguration
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveBacktrace
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveLogLevel
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::isDisabled
     */
    public function testWriteLogDisabled()
    {
        \Krexx::disable();

        $fileWriter = new FileWriter([]);
        $fileWriter->writeLog($this->prepareFixture());

        $this->assertEquals(0, ControllerNothing::$count, 'No controller action allowed, because it is disabled.');
    }

    /**
     * Test the disabling of the file writer, if we are facing a backend route
     * from includekrexx.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveBacktrace
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::prepareLogModelNormal
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::isDisabled
     */
    public function testWriteLogRouting()
    {
        $_SERVER[static::REQUEST_URI] = '/ajax/refreshLoglist';
        $fileWriter = new FileWriter([]);
        $fileWriter->writeLog($this->prepareFixture());

        $_SERVER[static::REQUEST_URI] = '/ajax/delete';
        $fileWriter = new FileWriter([]);
        $fileWriter->writeLog($this->prepareFixture());

        $this->assertEquals(
            0,
            ControllerNothing::$count,
            'No logging, while in self serving backend ajax mode.'
        );
    }

    /**
     * Test the writing of the log file when getting the dreaded Oops error.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveBacktrace
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::prepareLogModelOops
     */
    public function testWriteLogOops()
    {
        // Inject the exception with a prepared backtrace.
        $oopsException = new \Exception('Guru Meditation #010000C.000EF800', time());
        $backtrace = debug_backtrace();
        $backtrace[1][static::TRACE_ARGS][1]['exception'] = $oopsException;
        $backtrace[2][static::TRACE_OBJECT] = new  stdClass();
        $debugBacktraceMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Log\\', 'debug_backtrace');
        $debugBacktraceMock->expects($this->once())
            ->will($this->returnValue($backtrace));

        // Create the fixture.
        $fixture = new LogRecord(
            'Unit Tests',
            LogLevel::ERROR,
            'Oops, an error occurred!'
        );

        Krexx::$pool->rewrite[DumpController::class] = ControllerNothing::class;
        $fileWriter = new FileWriter([]);
        $fileWriter->writeLog($fixture);

        $this->assertEquals(1, ControllerNothing::$count);
        /** @var \Brainworxx\Krexx\Logging\Model $logModel */
        $logModel = ControllerNothing::$data[0];
        $this->assertEquals($oopsException->getMessage(), $logModel->getMessage());
        $this->assertEquals($fixture->getComponent(), $logModel->getCode());
    }

    /**
     * Prepare the fixture.
     *
     * @return \TYPO3\CMS\Core\Log\LogRecord
     */
    protected function prepareFixture()
    {
        Krexx::$pool->rewrite[DumpController::class] = ControllerNothing::class;

        return new LogRecord(
            'Unit Tests',
            LogLevel::DEBUG,
            'just testing'
        );
    }
}
