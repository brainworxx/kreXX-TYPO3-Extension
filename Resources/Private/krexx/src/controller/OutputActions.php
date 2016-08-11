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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Errorhandler\Fatal;
use Brainworxx\Krexx\Model\Simple;

/**
 * Controller actions (if you want to call them that).
 *
 * @package Brainworxx\Krexx\Controller
 */
class OutputActions extends Internals
{

    /**
     * Dump information about a variable.
     *
     * Here everything starts and ends (well, unless we are only outputting
     * the settings editor).
     *
     * @param mixed $data
     *   The variable we want to analyse.
     * @param string $headline
     *   The headline of the markup we want to produce. Most likely the name of
     *   the variable.
     */
    public static function dumpAction($data, $headline = '')
    {

        if (self::checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }
        self::initStorage();
        self::$storage->emergencyHandler->resetTimer();
        self::registerShutdown();
        // Find caller.
        $caller = self::findCaller();
        if ($headline != '') {
            $caller['type'] = $headline;
        } else {
            $caller['type'] = 'Analysis';
        }
        self::$storage->codegenHandler->setScope($caller['varname']);

        // Set the headline, if it's not set already.
        if ($headline == '') {
            if (is_object($data)) {
                $headline = get_class($data);
            }
            if (is_array($data)) {
                $headline = 'array';
            }
            if (is_bool($data)) {
                $headline = 'boolean';
            }
            if (is_float($data)) {
                $headline = 'float';
            }
            if (is_int($data)) {
                $headline = 'integer';
            }
            if (is_null($data)) {
                $headline = 'null';
            }
            if (is_resource($data)) {
                $headline = 'resource';
            }
            if (is_string($data)) {
                $headline = 'string';
            }
        }

        // We need to get the footer before the generating of the header,
        // because we need to display messages in the header from the configuration.
        $footer = self::outputFooter($caller);
        self::$storage->codegenHandler->checkAllowCodegen();

        // Enable code generation only if we were aqble to determine the varname.
        if ($caller['varname'] != '. . .') {
            // We were able to determine the variable name and can generate some
            // sourcecode.
            $headline = $caller['varname'];
        }

        // Start the magic.
        $model = new Simple(self::$storage);
        $model->setData($data)
            ->setName($caller['varname'])
            ->setConnector2('=');
        $analysis = self::$storage->routing->analysisHub($model);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if (!self::$storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        self::$shutdownHandler->addChunkString(self::outputHeader($headline));
        self::$shutdownHandler->addChunkString($analysis);
        self::$shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if (self::$storage->config->getConfigValue('output', 'destination') == 'file') {
            self::$storage->chunks->addMetadata($caller);
        }
    }

    /**
     * Outputs a backtrace.
     */
    public static function backtraceAction()
    {
        if (self::checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }
        self::$storage->emergencyHandler->resetTimer();
        self::initStorage();
        self::registerShutdown();

        // Find caller.
        $caller = self::findCaller();
        $caller['type'] = 'Backtrace';

        self::$storage->codegenHandler->setScope($caller['varname']);

        $headline = 'Backtrace';

        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();
        unset($backtrace[0]);

        $footer = self::outputFooter($caller);

        $analysis = self::$storage->routing->analysisBacktrace($backtrace, -1);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        if (!self::$storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        self::$shutdownHandler->addChunkString(self::outputHeader($headline));
        self::$shutdownHandler->addChunkString($analysis);
        self::$shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if (self::$storage->config->getConfigValue('output', 'destination') == 'file') {
            self::$storage->chunks->addMetadata($caller);
        }

    }

    /**
     * Outputs the edit settings dialog, without any analysis.
     */
    public static function editSettingsAction()
    {
        if (self::checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }
        self::$storage->emergencyHandler->resetTimer();
        self::initStorage();
        self::registerShutdown();

        // We will not check this for the cookie config, to avoid people locking
        // themselves out.
        self::$storage->emergencyHandler->setEnable(false);


        // Find caller.
        $caller = self::findCaller();
        $caller['type'] = 'Cookie Configuration';
        self::$storage->chunks->addMetadata($caller);

        // Render it.
        $footer = self::outputFooter($caller, true);
        self::$shutdownHandler->addChunkString(self::outputHeader('Edit local settings'));
        self::$shutdownHandler->addChunkString($footer);
        self::$storage->emergencyHandler->setEnable(true);
    }

    /**
     * Renders the info to the error, warning or notice.
     *
     * @param array $errorData
     *   The data from the error. This should be a backtrace
     *   with code samples.
     */
    public static function errorAction(array $errorData)
    {
        self::$storage->emergencyHandler->resetTimer();
        self::initStorage();
        self::registerShutdown();

        // Get the header.
        if (self::$headerSend) {
            $header = self::$storage->render->renderFatalHeader('', '<!DOCTYPE html>');
        } else {
            $header = self::$storage->render->renderFatalHeader(self::outputCssAndJs(), '<!DOCTYPE html>');
        }

        // Get the main part.
        $main = self::$storage->render->renderFatalMain(
            $errorData['type'],
            $errorData['errstr'],
            $errorData['errfile'],
            $errorData['errline']
        );

        // Get the backtrace.
        $backtrace = self::$storage->routing->analysisBacktrace($errorData['backtrace'], -1);
        if (!self::$storage->emergencyHandler->checkEmergencyBreak()) {
            return;
        }

        // Get the footer.
        $footer = self::outputFooter('');
        // Get the messages.
        $messages = self::$storage->messages->outputMessages();

        if (self::$storage->config->getConfigValue('output', 'destination') == 'file') {
            // Add the caller as metadata to the chunks class. It will be saved as
            // additional info, in case we are logging to a file.
            self::$storage->chunks->addMetadata(array(
                'file' => $errorData['errfile'],
                'line' => $errorData['errline'] + 1,
                'varname' => ' Fatal Error',
            ));

            // Save it to a file.
            self::$storage->chunks->saveDechunkedToFile($header . $messages . $main . $backtrace . $footer);
        } else {
            // Send it to the browser.
            self::$storage->chunks->sendDechunkedToBrowser($header . $messages . $main . $backtrace . $footer);
        }
    }

    /**
     * Register the fatal error handler.
     */
    public static function registerFatalAction()
    {
        // As of PHP Version 7.0.2, the register_tick_function() causesPHP to crash,
        // with a connection reset! We need to check the version to avoid this, and
        // then tell the dev what happened.
        // Not to mention that fatals got removed anyway.
        if (version_compare(phpversion(), '7.0.0', '>=')) {
            // Too high! 420 Method Failure :-(
            self::$storage->messages->addMessage(self::$storage->render->getHelp('php7yellow'));
            krexx(self::$storage->render->getHelp('php7'));

            // Just return, there is nothing more to do here.
            return;
        }
        self::initStorage();
        // Do we need another shutdown handler?
        if (!is_object(self::$krexxFatal)) {
            self::$krexxFatal = new Fatal(self::$storage);
            declare(ticks = 1);
            register_shutdown_function(array(
              self::$krexxFatal,
              'shutdownCallback',
            ));
        }
        self::$krexxFatal->setIsActive(true);
        self::$fatalShouldActive = true;
        register_tick_function(array(self::$krexxFatal, 'tickCallback'));
    }

    /**
     * "Unregister" the fatal error handler.
     *
     * Actually we can not unregister it. We simply tell it to not activate
     * and we unregister the tick function which provides us with the
     * backtrace.
     */
    public static function unregisterFatalAction()
    {
        if (!is_null(self::$krexxFatal)) {
            // Now we need to tell the shutdown function, that is must
            // not do anything on shutdown.
            self::$krexxFatal->setIsActive(false);
            unregister_tick_function(array(self::$krexxFatal, 'tickCallback'));
        }
        self::$fatalShouldActive = false;
    }

    /**
     * Takes a "moment" for the benchmark test.
     *
     * @param string $string
     *   Defines a "moment" during a benchmark test.
     *   The string should be something meaningful, like "Model invoice db call".
     */
    public static function timerAction($string)
    {
        // Did we use this one before?
        if (isset(self::$counterCache[$string])) {
            // Add another to the counter.
            self::$counterCache[$string]++;
            self::$timekeeping['[' . self::$counterCache[$string] . ']' . $string] = microtime(true);
        } else {
            // First time counter, set it to 1.
            self::$counterCache[$string] = 1;
            self::$timekeeping[$string] = microtime(true);
        }
    }

    /**
     * Outputs the timer
     */
    public static function timerEndAction()
    {
        self::timerAction('end');
        // And we are done. Feedback to the user.
        self::dumpAction(self::miniBenchTo(self::$timekeeping), 'kreXX timer');
        // Reset the timer vars.
        self::$timekeeping = array();
        self::$counterCache = array();
    }

    /**
     * Yes, we do have an output here. We are generation messagesd to
     * inform the dev that the environment is not as it should be.
     *
     * @param string $krexxDir
     *   The directory where kreXX ist installed.
     */
    public static function checkEnvironmentAction($krexxDir)
    {
        self::initStorage($krexxDir);

        // Check chunk folder is writable.
        // If not, give feedback!
        $logFolder = $krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
        if (!is_writeable($logFolder)) {
            $chunkFolder = $krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
            self::$storage->messages->addMessage(
                'Chunksfolder ' . $chunkFolder . ' is not writable!' .
                'This will increase the memory usage of kreXX significantly!',
                'critical'
            );
            self::$storage->messages->addKey(
                'protected.folder.chunk',
                array($krexxDir . 'chunks' . DIRECTORY_SEPARATOR)
            );
            // We can work without chunks, but this will require much more memory!
            self::$storage->chunks->setUseChunks(false);
        }

        // Check if the log folder is writable.
        // If not, give feedback!
        $chunkFolder = $krexxDir . self::$storage->config->getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR;
        if (!is_writeable($chunkFolder)) {
            $logFolder = $krexxDir . self::$storage->config->getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR;
            self::$storage->messages->addMessage('Logfolder ' . $logFolder . ' is not writable !', 'critical');
            self::$storage->messages->addKey(
                'protected.folder.log',
                array($krexxDir . self::$storage->config->getConfigValue('output', 'folder') . DIRECTORY_SEPARATOR)
            );
        }
        // At this point, we won't inform the dev right away. The error message
        // will pop up, when kreXX is actually displayed, no need to bother the
        // dev just now.
        // We might need to register our fatal error handler.
        if (self::$storage->config->getConfigValue('backtraceAndError', 'registerAutomatically') == 'true') {
            self::registerFatalAction();
        }
    }

    /**
     * Simply outputs a formatted var_dump.
     *
     * This is an internal debugging function, because it is
     * rather difficult to debug a debugger, when your tool of
     * choice is the debugger itself.
     *
     * @param mixed $data
     *   The data for the var_dump.
     */
    public static function formattedVarDump($data)
    {
        echo '<pre>';
        var_dump($data);
        echo('</pre>');
    }
}
