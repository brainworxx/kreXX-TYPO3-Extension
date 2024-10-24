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

declare(strict_types=1);

namespace Brainworxx\Includekrexx\Log;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Logging\LoggingTrait;
use Brainworxx\Krexx\Logging\Model as LogModel;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Throwable;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\WriterInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * File writer logging implementation.
 */
class FileWriter implements WriterInterface, ConfigConstInterface, BacktraceConstInterface
{
    use LoggingTrait;

    /**
     * @var string
     */
    protected const KREXX_LOG_WRITER = 'kreXX log writer';

    /**
     * @var string
     */
    protected const EXCEPTION = 'exception';

    /**
     * Overwrites for the configuration.
     *
     * @var array
     */
    protected array $localConfig = [];

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
    public function writeLog(LogRecord $record): WriterInterface
    {
        if ($this->isDisabled()) {
            return $this;
        }

        AbstractController::$analysisInProgress = true;

        $backtrace = $this->retrieveBacktrace();
        $logModel = $this->prepareLogModelOops($backtrace, $record);
        Krexx::$pool->createClass(DumpController::class)
            ->dumpAction(
                $logModel,
                Krexx::$pool->encodingService->encodeString($logModel->getMessage()),
                $record->getLevel()
            );

        AbstractController::$analysisInProgress = false;
        static::endForcedLog();

        return $this;
    }

    /**
     * Retrieve a cleaned up backtrace
     *
     * @return array
     *   The backtrace.
     */
    protected function retrieveBacktrace(): array
    {
        // Get the backtrace ready.
        // We extract our own backtrace, because the objects in the thrown
        // exception may not be available.
        $backtrace = debug_backtrace();
        // The first one is not instance of the logger.
        unset($backtrace[0]);
        $step = 1;
        while (
            isset($backtrace[$step + 1][static::TRACE_OBJECT])
            && $backtrace[$step + 1][static::TRACE_OBJECT] instanceof Logger
        ) {
            // Remove the backtrace steps, until we leave the logger.
            unset($backtrace[$step]);
            ++$step;
        }

        return array_values($backtrace);
    }

    /**
     * What the method name says. We also apply the configuration here.
     *
     * And no, we are not going to reset the configuration. kreXX is disabled.
     *
     * @return bool
     */
    protected function isDisabled(): bool
    {
        $get = (string)GeneralUtility::getIndpEnv('REQUEST_URI');

        if (
            strpos($get, '/ajax/refreshLoglist') !== false
            || strpos($get, '/ajax/delete') !== false
        ) {
            // Do nothing.
            // We will not spam the log folder with debug calls from the kreXX
            // ajax backend.
            return true;
        }

        static::startForcedLog();
        // We apply the configuration after the forced logging, to give the
        // dev the opportunity to change the stuff from the forced logging.
        $this->applyTheConfiguration();

        // Disabled?
        if (
            Krexx::$pool->config->getSetting(static::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return true;
        }

        return false;
    }

    /**
     * Preparing the LogModel with the dreaded "Oops" exception
     *
     * The "Oops, an error occurred!" is the most evil thing in typo3. Getting
     * any useful information from one of those can proof quite a challenge.
     *
     * Otoh, one can always revert to development settings, so that
     * literally everybody can see what is happening.
     *
     * @param array $backtrace
     *   The original backtrace.
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     *   The log record from tYPO3
     *
     * @return \Brainworxx\Krexx\Logging\Model
     *   The loaded kreXX log model.
     */
    protected function prepareLogModelOops(array $backtrace, LogRecord $record): LogModel
    {
        // We have to extract it from the backtrace.
        if (
            !isset($backtrace[0][static::TRACE_ARGS][1][static::EXCEPTION]) ||
            !($backtrace[0][static::TRACE_ARGS][1][static::EXCEPTION] instanceof Throwable)
        ) {
            // Fallback to the normal handling.
            return $this->prepareLogModelNormal($backtrace, $record);
        }

        /** @var Throwable $oopsException */
        $oopsException = $backtrace[0][static::TRACE_ARGS][1][static::EXCEPTION];
        $message = '';

        if (method_exists($oopsException, 'getTitle')) {
            $message = $oopsException->getTitle() . "\r\n";
        }

        $message .= $oopsException->getMessage();
        // We use the backtrace from the oopsException.
        // Depending on the PHP settings and/or the PHP version, we may have no
        // args available here. The original backtrace does not help.
        // But this is better than nothing.
        $realBacktrace = $oopsException->getTrace();

        /** @var LogModel $logModel */
        $logModel = \Krexx::$pool->createClass(LogModel::class)
            ->setTrace($realBacktrace)
            ->setCode($record->getComponent())
            ->setMessage($message);

        if (isset($realBacktrace[0][static::TRACE_FILE])) {
            $logModel->setFile((string)$realBacktrace[0][static::TRACE_FILE]);
        }

        if (isset($realBacktrace[0][static::TRACE_LINE])) {
            $logModel->setLine((int)$realBacktrace[0][static::TRACE_LINE]);
        }

        return $logModel;
    }

    /**
     * We are handling a "normal" error. Nothing special.
     *
     * @param array $backtrace
     *   The retrieved backtrace.
     * @param \TYPO3\CMS\Core\Log\LogRecord $record
     *   The log record from the core.
     *
     * @return \Brainworxx\Krexx\Logging\Model
     *   The prepared log mode.
     */
    protected function prepareLogModelNormal(array $backtrace, LogRecord $record): LogModel
    {
        /** @var LogModel $logModel */
        $logModel = \Krexx::$pool->createClass(LogModel::class)
            ->setTrace($backtrace)
            ->setCode($record->getComponent())
            ->setMessage($record->getMessage());

        if (isset($backtrace[0][static::TRACE_FILE])) {
            $logModel->setFile((string)$backtrace[0][static::TRACE_FILE]);
        }
        if (isset($backtrace[0][static::TRACE_LINE])) {
            $logModel->setLine((int)$backtrace[0][static::TRACE_LINE]);
        }

        return $logModel;
    }

    /**
     * Iterate through the configuration and overwrite the settings.
     */
    protected function applyTheConfiguration(): void
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
