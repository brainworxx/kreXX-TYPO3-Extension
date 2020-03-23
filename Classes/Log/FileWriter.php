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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Log;

use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Logging\LoggingTrait;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\Fallback;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Log\LogRecord;

class FileWriter implements WriterInterface
{
    use LoggingTrait;

    const KREXX_LOG_WRITER = 'kreXX log writer';

    /**
     * Overwrites for the configuration.
     *
     * @var array
     */
    protected $localConfig = [];

    /**
     * Constructs this log writer
     *
     * @param array $options
     *   Special configuration options.
     */
    public function __construct(array $options = [])
    {
        // We do not validate this right away, because we may not have a pool
        // at this time.
        $this->localConfig = $options;
    }

    /**
     * Writes the log record
     *
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     *   The log record we want to write.
     *
     * @return \TYPO3\CMS\Core\Log\Writer\WriterInterface
     *   Return this, for chaining.
     */
    public function writeLog(LogRecord $record)
    {
        static::startForcedLog();
        // We apply the configuration after the forced logging, to give the
        // dev the opportunity to change the stuff from the forced logging.
        $this->applyTheConfiguration();

        // Disabled?
        if (
            Krexx::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return $this;
        }

        AbstractController::$analysisInProgress = true;

        $data = $record->getData();
        Krexx::$pool->createClass(DumpController::class)
            ->dumpAction(
                $data,
                $record->getComponent() . ': ' . $record->getMessage(),
                strtolower(LogLevel::getName($record->getLevel()))
            );

        AbstractController::$analysisInProgress = false;

        static::endForcedLog();

        return $this;
    }

    /**
     * Iterate through the configuration and overwrite the settings.
     */
    protected function applyTheConfiguration()
    {
        // Early return. Do nothing.
        if (empty($this->localConfig)) {
            return;
        }

        // We are overwriting an already parsed configuration.
        // This means, we do not have to cate about the fe configuration keys,
        // because they simply do not exist anymore at this point. Their value
        // is already assigned at the model, and we do not touch these settings.
        $config = \Krexx::$pool->config;
        foreach ($this->localConfig as $key => $value) {
            if (
                isset($config->settings[$key]) &&
                $config->validation->evaluateSetting(static::KREXX_LOG_WRITER, $key, $value)
            ) {
                $config->settings[$key]->setValue($value)->setSource(static::KREXX_LOG_WRITER);
            }
        }
    }
}
