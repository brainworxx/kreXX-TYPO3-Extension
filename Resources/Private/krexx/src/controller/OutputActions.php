<?php
/**
 * @file
 *   Controller actions for kreXX
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

namespace Brainworxx\Krexx\Controller;

use Brainworxx\Krexx\Analysis\RecursionHandler;
use Brainworxx\Krexx\Framework\Chunks;
use Brainworxx\Krexx\Config\Config;
use Brainworxx\Krexx\Analysis\Codegen;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\Messages;

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
        self::resetTimer();
        self::$recursionHandler = new RecursionHandler();
        self::loadRendrerer();

        // Find caller.
        $caller = self::findCaller();
        if ($headline != '') {
            $caller['type'] = $headline;
        } else {
            $caller['type'] = 'Analysis';
        }


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
        self::checkEmergencyBreak(false);
        $footer = self::outputFooter($caller);
        self::checkEmergencyBreak(true);

        // Start the analysis itself.
        Codegen::resetCounter();

        // Enable code generation only if we were aqble to determine the varname.
        if ($caller['varname'] == '...') {
            Config::$allowCodegen = false;
        } else {
            // We were able to determine the variable name and can generate some
            // sourcecode.
            Config::$allowCodegen = true;
            $headline = $caller['varname'];
        }

        // Set the current scope.
        Codegen::$scope = $caller['varname'];

        // Start the magic.
        $analysis = Variables::analysisHub($data, $caller['varname'], '', '=');
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        $emergency = false;
        if (!self::checkEmergencyBreak()) {
            $emergency = true;
        }
        // Disable it, so we can send the "meta" stuff from the template, like
        // header, messages and footer.
        self::checkEmergencyBreak(false);

        self::$shutdownHandler->addChunkString(self::outputHeader($headline));
        // We will not send the analysis if we have encountered an emergency break.
        if (!$emergency) {
            self::$shutdownHandler->addChunkString($analysis);
        }
        self::$shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if (Config::getConfigValue('output', 'destination') == 'file') {
            Chunks::addMetadata($caller);
        }

        // Reset value for the code generation.
        Config::$allowCodegen = false;

        // Enable emergency break for further use.
        self::checkEmergencyBreak(true);
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
        self::resetTimer();
        self::$recursionHandler = new RecursionHandler();
        self::loadRendrerer();

        Config::$allowCodegen = false;

        // Find caller.
        $caller = self::findCaller();
        $caller['type'] = 'Backtrace';

        $headline = 'Backtrace';

        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();
        unset($backtrace[0]);

        self::checkEmergencyBreak(false);
        $footer = self::outputFooter($caller);
        self::checkEmergencyBreak(true);

        $analysis = Variables::analysisBacktrace($backtrace, -1);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        $emergency = false;
        if (!self::checkEmergencyBreak()) {
            $emergency = true;
        }
        // Disable it, so we can send the "meta" stuff from the template, like
        // header, messages and footer.
        self::checkEmergencyBreak(false);

        self::$shutdownHandler->addChunkString(self::outputHeader($headline));
        // We will not send the analysis if we have encountered an emergency break.
        if (!$emergency) {
            self::$shutdownHandler->addChunkString($analysis);
        }
        self::$shutdownHandler->addChunkString($footer);

        // Add the caller as metadata to the chunks class. It will be saved as
        // additional info, in case we are logging to a file.
        if (Config::getConfigValue('output', 'destination') == 'file') {
            Chunks::addMetadata($caller);
        }

        // Enable emergency break for use in further use.
        self::checkEmergencyBreak(true);
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
        self::resetTimer();
        self::$recursionHandler = new RecursionHandler();
        self::loadRendrerer();

        // Find caller.
        $caller = self::findCaller();
        $caller['type'] = 'Cookie Configuration';
        Chunks::addMetadata($caller);

        // Render it.
        $footer = self::outputFooter($caller, true);
        self::$shutdownHandler->addChunkString(self::outputHeader('Edit local settings'));
        self::$shutdownHandler->addChunkString($footer);
    }

    /**
     * Renders the info to the error, warning or notice.
     *
     * @param array $errorData
     *   The data frm the error. This should be a backtrace
     *   with code samples.
     */
    public static function errorAction(array $errorData)
    {
        self::resetTimer();
        self::$recursionHandler = new RecursionHandler();

        // Get the header.
        if (self::$headerSend) {
            $header = OutputActions::$render->renderFatalHeader('', '<!DOCTYPE html>');
        } else {
            $header = OutputActions::$render->renderFatalHeader(self::outputCssAndJs(), '<!DOCTYPE html>');
        }

        // Get the main part.
        $main = OutputActions::$render->renderFatalMain(
            $errorData['type'],
            $errorData['errstr'],
            $errorData['errfile'],
            $errorData['errline'] + 1,
            $errorData['source']
        );
        // Get the backtrace.
        $backtrace = Variables::analysisBacktrace($errorData['backtrace']);
        // Get the footer.
        $footer = self::outputFooter('');
        // Get the messages.
        $messages = Messages::outputMessages();

        if (Config::getConfigValue('output', 'destination') == 'file') {
            // Add the caller as metadata to the chunks class. It will be saved as
            // additional info, in case we are logging to a file.
            Chunks::addMetadata(array(
                'file' => $errorData['errfile'],
                'line' => $errorData['errline'] + 1,
                'varname' => ' Fatal Error',
            ));

            // Save it to a file.
            Chunks::saveDechunkedToFile($header . $messages . $main . $backtrace . $footer);
        } else {
            // Send it to the browser.
            Chunks::sendDechunkedToBrowser($header . $messages . $main . $backtrace . $footer);
        }
    }
}
