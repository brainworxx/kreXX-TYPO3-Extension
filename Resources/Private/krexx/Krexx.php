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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Factory\Pool;

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
     * Here we remember, if we are currently running a analysis.
     * The debug methods may trigger another run, and we may get into
     * trouble, memory or runtime wise.
     *
     * @var bool
     */
    protected static $analysisInProgress = false;

    /**
     * Our pool where we keep all relevant classes.
     *
     * @internal
     *
     * @var Pool
     */
    public static $pool;

    /**
     * Includes all needed files and sets some internal values.
     *
     * @internal
     */
    public static function bootstrapKrexx()
    {
        $krexxDir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        include_once $krexxDir . 'src/view/Help.php';
        include_once $krexxDir . 'src/view/AbstractRender.php';
        include_once $krexxDir . 'src/view/Render.php';
        include_once $krexxDir . 'src/view/Messages.php';
        include_once $krexxDir . 'src/view/output/Chunks.php';
        include_once $krexxDir . 'src/view/output/AbstractOutput.php';
        include_once $krexxDir . 'src/view/output/Shutdown.php';
        include_once $krexxDir . 'src/view/output/File.php';
        include_once $krexxDir . 'src/service/config/Model.php';
        include_once $krexxDir . 'src/service/config/Fallback.php';
        include_once $krexxDir . 'src/service/config/Security.php';
        include_once $krexxDir . 'src/service/config/Config.php';
        include_once $krexxDir . 'src/service/misc/File.php';
        include_once $krexxDir . 'src/service/misc/Registry.php';
        include_once $krexxDir . 'src/service/factory/Factory.php';
        include_once $krexxDir . 'src/service/factory/Pool.php';
        include_once $krexxDir . 'src/service/flow/Recursion.php';
        include_once $krexxDir . 'src/service/flow/Emergency.php';
        include_once $krexxDir . 'src/analyse/Flection.php';
        include_once $krexxDir . 'src/analyse/routing/AbstractRouting.php';
        include_once $krexxDir . 'src/analyse/routing/Routing.php';
        include_once $krexxDir . 'src/analyse/routing/process/AbstractProcess.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessArray.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessBacktrace.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessBoolean.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessClosure.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessFloat.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessInteger.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessNull.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessObject.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessResource.php';
        include_once $krexxDir . 'src/analyse/routing/process/ProcessString.php';
        include_once $krexxDir . 'src/analyse/Model.php';
        include_once $krexxDir . 'src/analyse/Scope.php';
        include_once $krexxDir . 'src/analyse/callback/AbstractCallback.php';
        include_once $krexxDir . 'src/analyse/callback/analyse/BacktraceStep.php';
        include_once $krexxDir . 'src/analyse/callback/analyse/ConfigSection.php';
        include_once $krexxDir . 'src/analyse/callback/analyse/Debug.php';
        include_once $krexxDir . 'src/analyse/callback/analyse/Objects.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughArray.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughConfig.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughConstants.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughMethodAnalysis.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughMethods.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughProperties.php';
        include_once $krexxDir . 'src/analyse/callback/iterate/ThroughGetter.php';
        include_once $krexxDir . 'src/analyse/caller/AbstractCaller.php';
        include_once $krexxDir . 'src/analyse/caller/CallerFinder.php';
        include_once $krexxDir . 'src/analyse/comment/AbstractComment.php';
        include_once $krexxDir . 'src/analyse/comment/Methods.php';
        include_once $krexxDir . 'src/analyse/comment/Functions.php';
        include_once $krexxDir . 'src/analyse/code/Codegen.php';
        include_once $krexxDir . 'src/analyse/code/Connectors.php';
        include_once $krexxDir . 'src/analyse/code/ReflectionParameterWrapper.php';
        include_once $krexxDir . 'src/errorhandler/AbstractError.php';
        include_once $krexxDir . 'src/errorhandler/Fatal.php';
        include_once $krexxDir . 'src/controller/AbstractController.php';
        include_once $krexxDir . 'src/controller/BacktraceController.php';
        include_once $krexxDir . 'src/controller/DumpController.php';
        include_once $krexxDir . 'src/controller/EditSettingsController.php';
        include_once $krexxDir . 'src/controller/ErrorController.php';

        if (!function_exists('krexx')) {
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
        }

        // Create a new pool where we store all our classes.
        // We also need to check if we have an overwrite for the pool.
        if (!empty($GLOBALS['kreXXoverwrites']) &&
            is_array($GLOBALS['kreXXoverwrites']['classes']) &&
            isset($GLOBALS['kreXXoverwrites']['classes']['Brainworxx\\Krexx\\Service\\Factory\\Pool'])
        ) {
            $classname = $GLOBALS['kreXXoverwrites']['classes']['Brainworxx\\Krexx\\Service\\Factory\\Pool'];
            self::$pool = new $classname($krexxDir);
        } else {
            self::$pool = new Pool($krexxDir);
        }


        // We might need to register our fatal error handler.
        if (self::$pool->config->getSetting('registerAutomatically') &&
            !self::$pool->config->getSetting('disabled')) {
            self::$pool
                ->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
                ->registerFatalAction();
        }
    }

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
        // Do we gave a handle?
        $handle = self::$pool->config->getDevHandler();
        if ($name === $handle) {
            // We do a standard-open.
            if (isset($arguments[0])) {
                self::open($arguments[0]);
            } else {
                self::open();
            }
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
        // Disabled?
        if (self::$pool->config->getSetting('disabled') || self::$analysisInProgress) {
            return;
        }

        self::$analysisInProgress = true;

        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->timerAction($string)
            ->reFatalAfterKrexx();

        self::$analysisInProgress = false;
    }

    /**
     * Takes a "moment" and outputs the timer.
     *
     * @api
     */
    public static function timerEnd()
    {
        // Disabled ?
        if (self::$pool->config->getSetting('disabled') || self::$analysisInProgress) {
            return;
        }

        self::$analysisInProgress = true;

        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->timerEndAction()
            ->reFatalAfterKrexx();

        self::$analysisInProgress = false;
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
        // Disabled?
        if (self::$pool->config->getSetting('disabled') || self::$analysisInProgress) {
            return;
        }

        self::$analysisInProgress = true;

        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx()
            ->dumpAction($data)
            ->reFatalAfterKrexx();

        self::$analysisInProgress = false;
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
        // Disabled?
        if (self::$pool->config->getSetting('disabled') || self::$analysisInProgress) {
            return;
        }

        self::$analysisInProgress = true;

        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\BacktraceController')
            ->noFatalForKrexx()
            ->backtraceAction()
            ->reFatalAfterKrexx();

        self::$analysisInProgress = false;
    }

    /**
     * Disable kreXX.
     *
     * @api
     */
    public static function disable()
    {
        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
            ->noFatalForKrexx();
        // We will not re-enable it afterwards, because kreXX
        // is disabled and the handler would not show up anyway.
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
        // Disabled?
        // We are ignoring local settings here.
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }

         self::$pool->createClass('Brainworxx\\Krexx\\Controller\\EditSettingsController')
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
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
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
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->createClass('Brainworxx\\Krexx\\Controller\\ErrorController')
            ->unregisterFatalAction();
    }
}
