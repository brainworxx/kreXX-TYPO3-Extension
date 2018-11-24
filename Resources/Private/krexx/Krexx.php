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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

// Include some files and set some internal values.
include_once 'bootstrap.php';

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Config;

/**
 * Public functions, allowing access to the kreXX debug features.
 *
 * @package Krexx
 */
class Krexx
{

    /**
     * Our pool where we keep all relevant classes.
     *
     * @internal
     *
     * @var Pool
     */
    public static $pool;

    /**
     * Handles the developer handle.
     *
     * @api
     *
     * @param string $name
     *   The name of the static function which was called.
     * @param array $arguments
     *   The arguments of said function.
     */
    public static function __callStatic($name, array $arguments)
    {
        Pool::createPool();

        // Do we have a handle?
        if ($name === static::$pool->config->getDevHandler()) {
            // We do a standard-open.
            if (isset($arguments[0])) {
                static::open($arguments[0]);
                return;
            }

            static::open();
        }
    }

    /**
     * Takes a "moment".
     *
     * @api
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerMoment($string)
    {
        Pool::createPool();

        // Disabled?
        // We do not use the config settings here, because we do not have any
        // output whatsoever. The config settings are either on or off, during
        // the entire run, meaning the can not be changed (by normal api means)
        // from the outside.
        // We also do not use the static ForcedLog methods here, because they
        // are somewhat time costly.
        if (AbstractController::$analysisInProgress || Config::$disabledByPhp) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\TimerController')
            ->noFatalForKrexx()
            ->timerAction($string)
            ->reFatalAfterKrexx();

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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\TimerController')
            ->noFatalForKrexx()
            ->timerEndAction()
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Starts the analysis of a variable.
     *
     * @api
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function open($data = null)
    {
        Pool::createPool();

        // Disabled?
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->dumpAction($data)
            ->reFatalAfterKrexx();

        AbstractController::$analysisInProgress = false;
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     *
     * @api
     *
     */
    public static function backtrace()
    {
        Pool::createPool();

        // Disabled?
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            AbstractController::$analysisInProgress ||
            Config::$disabledByPhp
        ) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\BacktraceController')
            ->noFatalForKrexx()
            ->backtraceAction()
            ->reFatalAfterKrexx();

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
        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx();

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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

         static::$pool->createClass('Brainworxx\\Krexx\\Controller\\EditSettingsController')
            ->noFatalForKrexx()
            ->editSettingsAction()
            ->reFatalAfterKrexx();
    }

    /**
     * Registers a shutdown function.
     *
     * Our fatal errorhandler is located there.
     *
     * @api
     */
    public static function registerFatal()
    {
        Pool::createPool();

        // Disabled?
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

        // Wrong PHP version?
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            static::$pool->messages->addMessage('php7');
            // In case that there is no other kreXX output, we show the configuration
            // with the message.
            static::editSettings();
            return;
        }

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
            ->registerFatalAction();
    }

    /**
     * Tells the registered shutdown function to do nothing.
     *
     * We can not unregister a once declared shutdown function,
     * so we need to tell our errorhandler to do nothing, in case
     * there is a fatal.
     *
     * @api
     */
    public static function unregisterFatal()
    {
        Pool::createPool();

        // Disabled?
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) ||
            Config::$disabledByPhp
        ) {
            return;
        }

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
            ->unregisterFatalAction();
    }

    /**
     * Ignore all settings, and force file logging. Ajax requests will not be ignored.
     *
     * @api
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function log($data = null)
    {
        static::startForcedLog();
        static::open($data);
        static::endForcedLog();
    }

    /**
     * Force log a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     *
     * @api
     */
    public static function logBacktrace()
    {
        static::startForcedLog();
        static::backtrace();
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

    /**
     * Configure everything to start the forced logging.
     */
    protected static function startForcedLog()
    {
        Pool::createPool();

        // Output destination: file
        static::$pool->config
            ->settings[Fallback::SETTING_DESTINATION]
            ->setSource('forced logging')
            ->setValue(Fallback::VALUE_FILE);

        // Do not care about ajax requests.
        static::$pool->config
            ->settings[Fallback::SETTING_DETECT_AJAX]
            ->setSource('forced logging')
            ->setValue(false);

        // Reload the disabled settings with the new ajax setting.
         static::$pool->config
            ->loadConfigValue(Fallback::SETTING_DISABLED);
    }

    /**
     * Reset everything after the forced logging.
     */
    protected static function endForcedLog()
    {
        // Reset everything afterwards.
        static::$pool->config = static::$pool
            ->createClass('Brainworxx\\Krexx\\Service\\Config\\Config');
    }
}
