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

declare(strict_types=1);

namespace Brainworxx\Krexx;

use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Controller\BacktraceController;
use Brainworxx\Krexx\Controller\DumpController;
use Brainworxx\Krexx\Controller\EditSettingsController;
use Brainworxx\Krexx\Controller\ExceptionController;
use Brainworxx\Krexx\Controller\TimerController;
use Brainworxx\Krexx\Logging\LoggingTrait;
use Brainworxx\Krexx\Service\Config\Config;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Public functions, allowing access to the kreXX debug features.
 */
class Krexx implements ConfigConstInterface
{
    use LoggingTrait;

    /**
     * Our pool where we keep all relevant classes.
     *
     * @var Pool
     */
    public static $pool;

    /**
     * Takes a "moment".
     *
     * @api
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerMoment(string $string)
    {
        Pool::createPool();

        // Disabled?
        // We do not use the config settings here, because we do not have any
        // output whatsoever. The config settings are either on or off during
        // the entire run, meaning they can not be changed (by normal api means)
        // from the outside.
        // We also do not use the static ForcedLog methods here, because they
        // are somewhat time costly.
        if (AbstractController::$analysisInProgress || Config::$disabledByPhp) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass(TimerController::class)
            ->timerAction($string);

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Takes a "moment" and outputs the timer.
     *
     * @api
     */
    public static function timerEnd()
    {
        Pool::createPool();

        // Disabled ?
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass(TimerController::class)
            ->timerEndAction();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Starts the analysis of a variable.
     *
     * @api
     *
     * @param mixed $data
     *   The variable we want to analyse.
     *
     * @return mixed
     *   Return the original analysis value.
     */
    public static function open($data = null)
    {
        Pool::createPool();

        // Disabled?
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return $data;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass(DumpController::class)
            ->dumpAction($data);

        AbstractController::$analysisInProgress = false;

        return $data;
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     *
     * @param array|null $backtrace
     *   An already existing backtrace.
     *
     * @api
     */
    public static function backtrace(array $backtrace = null)
    {
        Pool::createPool();

        // Disabled?
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass(BacktraceController::class)
            ->backtraceAction($backtrace);

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Disable kreXX.
     *
     * @api
     */
    public static function disable()
    {
        Pool::createPool();

        static::$pool->config->setDisabled(true);
        static::$pool->createClass(DumpController::class);

        Config::$disabledByPhp = true;
    }

    /**
     * Displays the edit settings part, no analysis.
     *
     * Ignores the 'disabled' settings in the cookie.
     *
     * @api
     */
    public static function editSettings()
    {
        Pool::createPool();

        // Disabled?
        // We are ignoring local settings here.
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

         static::$pool->createClass(EditSettingsController::class)
            ->editSettingsAction();
    }

    /**
     * Registering our exception handler.
     *
     * @api
     */
    public static function registerExceptionHandler()
    {
        Pool::createPool();

        // Disabled?
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

        static::$pool->createClass(ExceptionController::class)
            ->registerAction();
    }

    /**
     * Unregistering our exception handler.
     *
     * @api
     */
    public static function unregisterExceptionHandler()
    {
        Pool::createPool();

        // Disabled?
        if (
            static::$pool->config->getSetting(static::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

        static::$pool->createClass(ExceptionController::class)
            ->unregisterAction();
    }

    /**
     * Ignore all settings, and force file logging. Ajax requests will not be ignored.
     *
     * @api
     *
     * @param mixed $data
     *   The variable we want to analyse.
     *
     * @return mixed
     *   Return the original analysis value.
     */
    public static function log($data = null)
    {
        static::startForcedLog();
        static::open($data);
        static::endForcedLog();

        return $data;
    }

    /**
     * Forced logging a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     *
     * @param array $backtrace
     *   an already existing backtrace
     *
     * @api
     */
    public static function logBacktrace(array $backtrace = null)
    {
        static::startForcedLog();
        static::backtrace($backtrace);
        static::endForcedLog();
    }

    /**
     * Takes a "moment" and logs the timer results.
     *
     * @api
     */
    public static function logTimerEnd()
    {
        static::startForcedLog();
        static::timerEnd();
        static::endForcedLog();
    }
}
