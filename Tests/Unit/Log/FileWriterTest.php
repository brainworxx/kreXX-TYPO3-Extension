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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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
use Brainworxx\Includekrexx\Tests\Helpers\AbstractTest;
use Brainworxx\Includekrexx\Tests\Helpers\ControllerNothing;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Model;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;

class FileWriterTest extends AbstractTest
{

    public function tearDown()
    {
        parent::tearDown();
        ControllerNothing::$count = 0;
        ControllerNothing::$data = [];
        ControllerNothing::$level = [];
        ControllerNothing::$message = [];
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
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveLogLevel
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
        $this->assertEquals($fixture->getData(), ControllerNothing::$data[0]);
        $this->assertEquals($fixture->getComponent() . ': ' . $fixture->getMessage(), ControllerNothing::$message[0]);
    }

    /**
     * Test the setting of configurations during the logging.
     *
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::applyTheConfiguration
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::writeLog
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
     * @covers \Brainworxx\Includekrexx\Log\FileWriter::retrieveLogLevel
     */
    public function testWriteLogDisabled()
    {
        \Krexx::disable();

        $fileWriter = new FileWriter([]);
        $fileWriter->writeLog($this->prepareFixture());

        $this->assertEquals(0, ControllerNothing::$count, 'No controller action allowed, because it is disabled.');
    }

    /**
     * Prepare the fixture.
     *
     * @return \TYPO3\CMS\Core\Log\LogRecord
     */
    protected function prepareFixture()
    {
        Krexx::$pool->rewrite[DumpController::class] = ControllerNothing::class;
        $fixture = new LogRecord(
            'Unit Tests',
            LogLevel::DEBUG,
            'just testing'
        );

        return $fixture;
    }
}
