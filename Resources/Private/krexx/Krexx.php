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

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Controller\AbstractController;
use Brainworxx\Krexx\Service\Config\Fallback;
use Brainworxx\Krexx\Service\Config\Config;

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
        // There may or may not be an active autoloader, which may or may not
        // be able to autolaod the krexx files. There amy or may not be an
        // unwanted interaction with the rest of the system when registering
        // another autoloader. This leaves us with loading every single file
        // via include_once.
        define('KREXX_DIR', __DIR__ . DIRECTORY_SEPARATOR);
        include_once 'src/Analyse/Callback/AbstractCallback.php';

        include_once 'src/Analyse/Callback/Analyse/Objects/AbstractObjectAnalysis.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/Constants.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/DebugMethods.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/Getter.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/Methods.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/PrivateProperties.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/ProtectedProperties.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/PublicProperties.php';
        include_once 'src/Analyse/Callback/Analyse/Objects/Traversable.php';

        include_once 'src/Analyse/Callback/Analyse/BacktraceStep.php';
        include_once 'src/Analyse/Callback/Analyse/ConfigSection.php';
        include_once 'src/Analyse/Callback/Analyse/Debug.php';
        include_once 'src/Analyse/Callback/Analyse/Objects.php';

        include_once 'src/Analyse/Callback/Iterate/ThroughArray.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughConfig.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughConstants.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughGetter.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughLargeArray.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughMethodAnalysis.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughMethods.php';
        include_once 'src/Analyse/Callback/Iterate/ThroughProperties.php';

        include_once 'src/Analyse/Caller/AbstractCaller.php';
        include_once 'src/Analyse/Caller/CallerFinder.php';

        include_once 'src/Analyse/Code/Codegen.php';
        include_once 'src/Analyse/Code/Connectors.php';
        include_once 'src/Analyse/Code/Scope.php';

        include_once 'src/Analyse/Comment/AbstractComment.php';
        include_once 'src/Analyse/Comment/Functions.php';
        include_once 'src/Analyse/Comment/Methods.php';
        include_once 'src/Analyse/Comment/Properties.php';

        include_once 'src/Analyse/Routing/AbstractRouting.php';
        include_once 'src/Analyse/Routing/Routing.php';

        include_once 'src/Analyse/Routing/Process/AbstractProcess.php';
        include_once 'src/Analyse/Routing/Process/ProcessArray.php';
        include_once 'src/Analyse/Routing/Process/ProcessBacktrace.php';
        include_once 'src/Analyse/Routing/Process/ProcessBoolean.php';
        include_once 'src/Analyse/Routing/Process/ProcessClosure.php';
        include_once 'src/Analyse/Routing/Process/ProcessFloat.php';
        include_once 'src/Analyse/Routing/Process/ProcessInteger.php';
        include_once 'src/Analyse/Routing/Process/ProcessNull.php';
        include_once 'src/Analyse/Routing/Process/ProcessObject.php';
        include_once 'src/Analyse/Routing/Process/ProcessResource.php';
        include_once 'src/Analyse/Routing/Process/ProcessString.php';
        include_once 'src/Analyse/Routing/Process/ProcessOther.php';

        include_once 'src/Analyse/AbstractModel.php';
        include_once 'src/Analyse/Model.php';

        include_once 'src/Controller/AbstractController.php';
        include_once 'src/Controller/BacktraceController.php';
        include_once 'src/Controller/DumpController.php';
        include_once 'src/Controller/EditSettingsController.php';
        include_once 'src/Controller/ErrorController.php';

        include_once 'src/Errorhandler/AbstractError.php';
        include_once 'src/Errorhandler/Fatal.php';

        include_once 'src/Service/Config/Fallback.php';
        include_once 'src/Service/Config/Config.php';
        include_once 'src/Service/Config/Model.php';
        include_once 'src/Service/Config/Security.php';

        include_once 'src/Service/Config/From/Cookie.php';
        include_once 'src/Service/Config/From/Ini.php';

        include_once 'src/Service/Factory/EventHandlerInterface.php';
        include_once 'src/Service/Factory/Event.php';
        include_once 'src/Service/Factory/Factory.php';
        include_once 'src/Service/Factory/Pool.php';

        include_once 'src/Service/Flow/Emergency.php';
        include_once 'src/Service/Flow/Recursion.php';

        include_once 'src/Service/Misc/Encoding.php';
        include_once 'src/Service/Misc/File.php';
        include_once 'src/Service/Misc/Registry.php';
        include_once 'src/Service/Misc/ReflectionUndeclaredProperty.php';

        include_once 'src/Service/Plugin/Registration.php';
        include_once 'src/Service/Plugin/PluginConfigInterface.php';

        include_once 'src/View/Output/AbstractOutput.php';
        include_once 'src/View/Output/Chunks.php';
        include_once 'src/View/Output/File.php';
        include_once 'src/View/Output/Shutdown.php';

        include_once 'src/View/RenderInterface.php';
        include_once 'src/View/AbstractRender.php';
        include_once 'src/View/Messages.php';
        include_once 'src/View/Render.php';

        // Point the configuration to the right directories
        Config::$directories = array(
            'chunks' => KREXX_DIR . 'chunks/',
            'log' => KREXX_DIR . 'log/',
            'config' => KREXX_DIR . 'config/Krexx.ini',
        );

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
                    return;
                }

                \Krexx::$handle($data);
            }
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) || AbstractController::$analysisInProgress) {
            return;
        }

        AbstractController::$analysisInProgress = true;

        static::$pool->createClass('Brainworxx\\Krexx\\Controller\\DumpController')
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) || AbstractController::$analysisInProgress) {
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED) || AbstractController::$analysisInProgress) {
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED)) {
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED)) {
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
        if (static::$pool->config->getSetting(Fallback::SETTING_DISABLED)) {
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
        Pool::createPool();

        // Output destination: file
        \Krexx::$pool->config
            ->settings['destination']
            ->setSource('forced logging')
            ->setValue('file');

        // Do not care about ajax requests.
        \Krexx::$pool->config
            ->settings['detectAjax']
            ->setSource('forced logging')
            ->setValue('false');

        // Start the anaylsis.
        static::open($data);

        // Reset everything afterwards.
        unset(\Krexx::$pool->config);
        \Krexx::$pool->config = \Krexx::$pool
            ->createClass('Brainworxx\\Krexx\\Service\\Config\\Config');
    }
}
