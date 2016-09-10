<?php
/**
 * @file
 *   Sourcecode GUI for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Storage;

/**
 * Alias function for object analysis.
 *
 * Register an alias function for object analysis,
 * so you will not have to type \Krexx::open($data);
 * all the time.
 *
 * @param mixed $data
 *   The variable we want to analyse.
 * @param string $handle
 *   The developer handle.
 */
function krexx($data = null, $handle = '')
{
    if (empty($handle)) {
        \Krexx::open($data);
    } else {
        \Krexx::$handle($data);
    }
}

// Include some files and set some internal values.
\Krexx::bootstrapKrexx();

/**
 * Public functions, allowing access to the kreXX debug features.
 *
 * @package Krexx
 */
class Krexx
{
    /**
     * Our storage wher we keep al relevant classes.
     *
     * @var Storage
     */
    public static $storage;

    /**
     * Includes all needed files and sets some internal values.
     */
    public static function bootstrapKrexx()
    {

        $krexxDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        include_once $krexxDir . 'src/service/view/Help.php';
        include_once $krexxDir . 'src/service/view/Render.php';
        include_once $krexxDir . 'src/service/view/Messages.php';
        include_once $krexxDir . 'src/service/config/Model.php';
        include_once $krexxDir . 'src/service/config/Fallback.php';
        include_once $krexxDir . 'src/service/config/Security.php';
        include_once $krexxDir . 'src/service/config/Config.php';
        include_once $krexxDir . 'src/service/misc/Codegen.php';
        include_once $krexxDir . 'src/service/misc/Chunks.php';
        include_once $krexxDir . 'src/service/misc/Shutdown.php';
        include_once $krexxDir . 'src/service/Storage.php';
        include_once $krexxDir . 'src/service/flow/Recursion.php';

        include_once $krexxDir . 'src/service/flow/Emergency.php';
        include_once $krexxDir . 'src/analysis/Flection.php';
        include_once $krexxDir . 'src/analysis/Routing.php';
        include_once $krexxDir . 'src/analysis/Model.php';
        include_once $krexxDir . 'src/analysis/callback/AbstractCallback.php';
        include_once $krexxDir . 'src/analysis/callback/analyse/BacktraceStep.php';
        include_once $krexxDir . 'src/analysis/callback/analyse/ConfigSection.php';
        include_once $krexxDir . 'src/analysis/callback/analyse/Debug.php';
        include_once $krexxDir . 'src/analysis/callback/analyse/Objects.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughArray.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughConfig.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughConstants.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughMethodAnalysis.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughMethods.php';
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughProperties.php';
        include_once $krexxDir . 'src/errorhandler/Error.php';
        include_once $krexxDir . 'src/errorhandler/Fatal.php';
        include_once $krexxDir . 'src/controller/Internals.php';
        include_once $krexxDir . 'src/controller/OutputActions.php';

        // Create a new storage where we sotre all our classes.
        self::$storage = new Storage($krexxDir);
    }

    /**
     * Handles the developer handle.
     *
     * @param string $name
     *   The name of the static function which was called.
     * @param array $arguments
     *   The arguments of said function.
     */
    public static function __callStatic($name, array $arguments)
    {
        self::$storage->controller->noFatalForKrexx();
        // Do we gave a handle?
        $handle = self::$storage->config->getDevHandler();
        if ($name === $handle) {
            // We do a standard-open.
            if (isset($arguments[0])) {
                self::open($arguments[0]);
            } else {
                self::open();
            }
        }
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Takes a "moment".
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerMoment($string)
    {
        self::$storage->controller->noFatalForKrexx();
        // Disabled?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->timerAction($string);
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Takes a "moment" and outputs the timer.
     */
    public static function timerEnd()
    {
        self::$storage->controller->noFatalForKrexx();
        // Disabled ?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->timerEndAction();
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Starts the analysis of a variable.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function open($data = null)
    {
        self::$storage->controller->noFatalForKrexx();
        // Disabled?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->dumpAction($data);
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     */
    public static function backtrace()
    {
        self::$storage->controller->noFatalForKrexx();
        // Disabled?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        // Render it.
        self::$storage->controller->backtraceAction();
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Disable kreXX.
     */
    public static function disable()
    {
        self::$storage->controller->noFatalForKrexx();
        self::$storage->config->setDisabled(true);
        // We will not re-enable it afterwards, because kreXX
        // is disabled and the handler would not show up anyway.
    }

    /**
     * Displays the edit settings part, no analysis.
     *
     * Ignores the 'disabled' settings in the cookie.
     */
    public static function editSettings()
    {
        self::$storage->controller->noFatalForKrexx();
        // Disabled?
        // We are ignoring local settings here.
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->editSettingsAction();
        self::$storage->controller->reFatalAfterKrexx();
    }

    /**
     * Registers a shutdown function.
     *
     * Our fatal errorhandler is located there.
     */
    public static function registerFatal()
    {
        // Disabled?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->registerFatalAction();
    }

    /**
     * Tells the registered shutdown function to do nothing.
     *
     * We can not unregister a once declared shutdown function,
     * so we need to tell our errorhandler to do nothing, in case
     * there is a fatal.
     */
    public static function unregisterFatal()
    {
        // Disabled?
        if (self::$storage->config->getSetting('disabled')) {
            return;
        }
        self::$storage->controller->unregisterFatalAction();
    }
}
