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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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
use Brainworxx\Includekrexx\Tests\Helpers\AbstractHelper;
use Brainworxx\Includekrexx\Tests\Helpers\ControllerNothing;
use Brainworxx\Includekrexx\Tests\Helpers\ErrorException;
use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use Brainworxx\Krexx\Logging\Model as LogModel;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use stdClass;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(FileWriter::class, 'writeLog')]
#[CoversMethod(FileWriter::class, 'retrieveBacktrace')]
#[CoversMethod(FileWriter::class, 'prepareLogModelOops')]
#[CoversMethod(FileWriter::class, 'isDisabled')]
#[CoversMethod(FileWriter::class, 'prepareLogModelNormal')]
#[CoversMethod(FileWriter::class, 'applyTheConfiguration')]
#[CoversMethod(FileWriter::class, '__construct')]
class FileWriterTest extends AbstractHelper implements BacktraceConstInterface, ConstInterface
{
    protected const REQUEST_URI  = 'REQUEST_URI';
    protected const REQUEST_URI_VAR = 'requestURIvar';
    protected const REMOTE_ADDR = 'REMOTE_ADDR';
    protected const ORIG_SCRIPT_NAME = 'ORIG_SCRIPT_NAME';
    protected const QUERY_STRING = 'QUERY_STRING';
    protected const REVERSE_PROXY_IP = 'reverseProxyIP';

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();

        $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REQUEST_URI_VAR] = '';
        $GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY_IP] = '';
        $_SERVER[static::REQUEST_URI] = '';
        $_SERVER[static::REMOTE_ADDR] = '';
        $_SERVER[static::ORIG_SCRIPT_NAME] = '';
        $_SERVER[static::QUERY_STRING] = '';
    }

    /**
     * {@inheritDoc}
     */
    public function tearDown(): void
    {
        parent::tearDown();
        ControllerNothing::$count = 0;
        ControllerNothing::$data = [];
        ControllerNothing::$level = [];
        ControllerNothing::$message = [];

        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REQUEST_URI_VAR]);
        unset($GLOBALS[static::TYPO3_CONF_VARS][static::SYS][static::REVERSE_PROXY_IP]);
        unset($_SERVER[static::REQUEST_URI]);
        unset($_SERVER[static::REMOTE_ADDR]);
        unset($_SERVER[static::ORIG_SCRIPT_NAME]);
        unset($_SERVER[static::QUERY_STRING]);
    }

    /**
     * Test the setting of the configuration.
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
     */
    public function testWriteLogNormal()
    {
        $backtrace = debug_backtrace();
        $backtrace[1][static::TRACE_OBJECT] = new Logger('Unit');
        $backtrace[2][static::TRACE_OBJECT] = new Logger('Division');
        $debugBacktraceMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Log\\', 'debug_backtrace');
        $debugBacktraceMock->expects($this->once())
            ->willReturn($backtrace);

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
            ->willReturn($configModelMock);
        $configModelMock->expects(($this->once()))
            ->method('setSource')
            ->with('kreXX log writer');
        $configModelMock->expects(($this->once()))
            ->method('getValue')
            ->willReturn(false);
        \Krexx::$pool->config->settings[Fallback::SETTING_ANALYSE_SCALAR] = $configModelMock;

        $fileWriter = new FileWriter($config);
        $fileWriter->writeLog($this->prepareFixture());
    }

    /**
     * Test the early return, in case kreXX ist disabled.
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
     */
    public function testWriteLogOops()
    {
        // Inject the exception with a prepared backtrace.
        $oopsException = new ErrorException('Guru Meditation #010000C.000EF800', time());
        $backtrace = debug_backtrace();
        $backtrace[1][static::TRACE_ARGS][1]['exception'] = $oopsException;
        $backtrace[2][static::TRACE_OBJECT] = new  stdClass();
        $debugBacktraceMock = $this->getFunctionMock('\\Brainworxx\\Includekrexx\\Log\\', 'debug_backtrace');
        $debugBacktraceMock->expects($this->once())
            ->willReturn($backtrace);

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
        $this->assertEquals($oopsException->getTitle() . "\r\n" . $oopsException->getMessage(), $logModel->getMessage());
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
