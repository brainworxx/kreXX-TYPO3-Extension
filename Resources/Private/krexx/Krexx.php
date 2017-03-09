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
     * Our pool where we keep all relevant classes.
     *
     * @var Pool
     */
    public static $pool;

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
        include_once $krexxDir . 'src/service/code/Codegen.php';
        include_once $krexxDir . 'src/service/code/Connectors.php';
        include_once $krexxDir . 'src/service/misc/File.php';
        include_once $krexxDir . 'src/service/output/Chunks.php';
        include_once $krexxDir . 'src/service/output/AbstractOutput.php';
        include_once $krexxDir . 'src/service/output/Shutdown.php';
        include_once $krexxDir . 'src/service/output/Direct.php';
        include_once $krexxDir . 'src/service/output/File.php';
        include_once $krexxDir . 'src/service/factory/Factory.php';
        include_once $krexxDir . 'src/service/factory/Pool.php';
        include_once $krexxDir . 'src/service/flow/Recursion.php';
        include_once $krexxDir . 'src/service/flow/Emergency.php';
        include_once $krexxDir . 'src/analysis/Flection.php';
        include_once $krexxDir . 'src/analysis/routing/AbstractRouting.php';
        include_once $krexxDir . 'src/analysis/routing/Routing.php';
        include_once $krexxDir . 'src/analysis/process/AbstractProcess.php';
        include_once $krexxDir . 'src/analysis/process/ProcessArray.php';
        include_once $krexxDir . 'src/analysis/process/ProcessBacktrace.php';
        include_once $krexxDir . 'src/analysis/process/ProcessBoolean.php';
        include_once $krexxDir . 'src/analysis/process/ProcessClosure.php';
        include_once $krexxDir . 'src/analysis/process/ProcessFloat.php';
        include_once $krexxDir . 'src/analysis/process/ProcessInteger.php';
        include_once $krexxDir . 'src/analysis/process/ProcessNull.php';
        include_once $krexxDir . 'src/analysis/process/ProcessObject.php';
        include_once $krexxDir . 'src/analysis/process/ProcessResource.php';
        include_once $krexxDir . 'src/analysis/process/ProcessString.php';
        include_once $krexxDir . 'src/analysis/Model.php';
        include_once $krexxDir . 'src/analysis/Scope.php';
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
        include_once $krexxDir . 'src/analysis/callback/iterate/ThroughGetter.php';
        include_once $krexxDir . 'src/analysis/caller/AbstractCaller.php';
        include_once $krexxDir . 'src/analysis/caller/Php.php';
        include_once $krexxDir . 'src/analysis/comment/AbstractComment.php';
        include_once $krexxDir . 'src/analysis/comment/Methods.php';
        include_once $krexxDir . 'src/analysis/comment/Functions.php';
        include_once $krexxDir . 'src/errorhandler/Error.php';
        include_once $krexxDir . 'src/errorhandler/Fatal.php';
        include_once $krexxDir . 'src/controller/Internals.php';
        include_once $krexxDir . 'src/controller/OutputActions.php';

        // Create a new pool where we store all our classes.
        self::$pool = new Pool($krexxDir);

        // Check our environment.
        self::checkEnvironment($krexxDir);

        // We might need to register our fatal error handler.
        if (self::$pool->config->getSetting('registerAutomatically')) {
            self::$pool->controller->registerFatalAction();
        }
    }

    /**
     * Check if the environment is  as it should be.
     *
     * @param string $krexxDir
     *   The directory where kreXX ist installed.
     */
    protected static function checkEnvironment($krexxDir)
    {
        // Check chunk folder is writable.
        // If not, give feedback!
        $chunkFolder = $krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
        if (!is_writeable($chunkFolder)) {
            self::$pool->messages->addMessage(
                'Chunksfolder ' . $chunkFolder . ' is not writable!' .
                'This will increase the memory usage of kreXX significantly!',
                'critical'
            );
            self::$pool->messages->addKey('protected.folder.chunk', array($chunkFolder));
            // We can work without chunks, but this will require much more memory!
            self::$pool->chunks->setUseChunks(false);
        }

        // Check if the log folder is writable.
        // If not, give feedback!
        $logFolder = $krexxDir . 'log' . DIRECTORY_SEPARATOR;
        if (!is_writeable($logFolder)) {
            self::$pool->messages->addMessage('Logfolder ' . $logFolder . ' is not writable !', 'critical');
            self::$pool->messages->addKey('protected.folder.log', array($logFolder));
        }
        // At this point, we won't inform the dev right away. The error message
        // will pop up, when kreXX is actually displayed, no need to bother the
        // dev just now.
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
        self::$pool->controller->noFatalForKrexx();
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
        self::$pool->controller->reFatalAfterKrexx();
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
        self::$pool->controller->noFatalForKrexx();
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->timerAction($string);
        self::$pool->controller->reFatalAfterKrexx();
    }

    /**
     * Takes a "moment" and outputs the timer.
     */
    public static function timerEnd()
    {
        self::$pool->controller->noFatalForKrexx();
        // Disabled ?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->timerEndAction();
        self::$pool->controller->reFatalAfterKrexx();
    }

    /**
     * Starts the analysis of a variable.
     *
     * @param mixed $data
     *   The variable we want to analyse.
     */
    public static function open($data = null)
    {
        self::$pool->controller->noFatalForKrexx();
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->dumpAction($data);
        self::$pool->controller->reFatalAfterKrexx();
    }

    /**
     * Prints a debug backtrace.
     *
     * When there are classes found inside the backtrace,
     * they will be analysed.
     */
    public static function backtrace()
    {
        self::$pool->controller->noFatalForKrexx();
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        // Render it.
        self::$pool->controller->backtraceAction();
        self::$pool->controller->reFatalAfterKrexx();
    }

    /**
     * Disable kreXX.
     */
    public static function disable()
    {
        self::$pool->controller->noFatalForKrexx();
        self::$pool->config->setDisabled(true);
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
        self::$pool->controller->noFatalForKrexx();
        // Disabled?
        // We are ignoring local settings here.
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->editSettingsAction();
        self::$pool->controller->reFatalAfterKrexx();
    }

    /**
     * Registers a shutdown function.
     *
     * Our fatal errorhandler is located there.
     */
    public static function registerFatal()
    {
        // Disabled?
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->registerFatalAction();
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
        if (self::$pool->config->getSetting('disabled')) {
            return;
        }
        self::$pool->controller->unregisterFatalAction();
    }
}
