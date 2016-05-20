<?php
/**
 * @file
 *   Internal processing functions for kreXX
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

namespace Brainworxx\Krexx\Framework;

use Brainworxx\Krexx\Analysis\Hive;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\Help;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\View\Output;
use Brainworxx\Krexx\View\Codegen;
use Brainworxx\Krexx\View\Messages;

/**
 * This class hosts the internal functions.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Internals
{


    /**
     * The current nesting level we are in.
     *
     * @var int
     */
    public static $nestingLevel = 0;

    /**
     * The "scope we are starting with. When it is $this in combination with a
     * nesting level of 1, we treat protected and private variables and functions
     * as public, because they are reachable from the current scope.
     *
     * @var string
     */
    protected static $scope = '';

    /**
     * Sends the output to the browser during shutdown phase.
     *
     * @var ShutdownHandler
     */
    public static $shutdownHandler;

    /**
     * Unix timestamp, used to determine if we need to do an emergency break.
     *
     * @var int
     */
    public static $timer = 0;

    /**
     * The benchmark main function.
     *
     * @param array $timeKeeping
     *   The timekeeping array.
     *
     * @return array
     *   The benchmark array.
     *
     * @see http://php.net/manual/de/function.microtime.php
     * @author gomodo at free dot fr
     */
    public static function miniBenchTo(array $timeKeeping)
    {
        // Get the very first key.
        $start = key($timeKeeping);
        $totalTime = round((end($timeKeeping) - $timeKeeping[$start]) * 1000, 4);
        $result['url'] = Toolbox::getCurrentUrl();
        $result['total_time'] = $totalTime;
        $prevMomentName = $start;
        $prevMomentStart = $timeKeeping[$start];

        foreach ($timeKeeping as $moment => $time) {
            if ($moment != $start) {
                // Calculate the time.
                $percentageTime = round(((round(($time - $prevMomentStart) * 1000, 4) / $totalTime) * 100), 1);
                $result[$prevMomentName . '->' . $moment] = $percentageTime . '%';
                $prevMomentStart = $time;
                $prevMomentName = $moment;
            }
        }
        return $result;
    }

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
    public static function dump($data, $headline = '')
    {

        // Start the timer.
        // When a certain time has passed, kreXX will use an
        // emergency break to prevent too large output (or no output at all (WSOD)).
        if (self::$timer == 0) {
            self::$timer = time();
        }

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

        if (self::checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }

        // Start Output.
        SkinRender::$KrexxCount++;
        // We need to get the footer before the generating of the header,
        // because we need to display messages in the header from the configuration.
        self::checkEmergencyBreak(false);
        $footer = Output::outputFooter($caller);
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
        Internals::$scope = $caller['varname'];

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

        self::$shutdownHandler->addChunkString(Output::outputHeader($headline));
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

        // Cleanup the hive, this removes all recursion markers.
        Hive::cleanupHive();

        // Reset value for the code generation.
        Config::$allowCodegen = false;

        // Enable emergency break for further use.
        self::checkEmergencyBreak(true);
    }

    /**
     * Outputs a backtrace.
     *
     */
    public static function backtrace()
    {
        // Start the timer.
        // When a certain time has passed, kreXX will use an
        // emergency break to prevent too large output (or no output at all (WSOD)).
        if (self::$timer == 0) {
            self::$timer = time();
        }

        Config::$allowCodegen = false;

        // Find caller.
        $caller = self::findCaller();
        $caller['type'] = 'Backtrace';

        $headline = 'Backtrace';

        if (self::checkMaxCall()) {
            // Called too often, we might get into trouble here!
            return;
        }

        // Start Output.
        SkinRender::$KrexxCount++;

        // Remove the fist step from the backtrace,
        // because that is the internal function in kreXX.
        $backtrace = debug_backtrace();
        unset($backtrace[0]);

        self::checkEmergencyBreak(false);
        $footer = Output::outputFooter($caller);
        self::checkEmergencyBreak(true);

        $analysis = Output::outputBacktrace($backtrace);
        // Now that our analysis is done, we must check if there was an emergency
        // break.
        $emergency = false;
        if (!self::checkEmergencyBreak()) {
            $emergency = true;
        }
        // Disable it, so we can send the "meta" stuff from the template, like
        // header, messages and footer.
        self::checkEmergencyBreak(false);

        self::$shutdownHandler->addChunkString(Output::outputHeader($headline));
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

        // Cleanup the hive, this removes all recursion markers.
        Hive::cleanupHive();

        // Enable emergency break for use in further use.
        self::checkEmergencyBreak(true);
    }

    /**
     * Finds the place in the code from where krexx was called.
     *
     * @return array
     *   The code, from where krexx was called
     */
    public static function findCaller()
    {
        $backtrace = debug_backtrace();
        while ($caller = array_pop($backtrace)) {
            if (isset($caller['function']) && strtolower($caller['function']) == 'krexx') {
                break;
            }
            if (isset($caller['class']) && strtolower($caller['class']) == 'krexx') {
                break;
            }
        }

        // We will not keep the whole backtrace im memory. We only return what we
        // actually need.
        return array(
            'file' => $caller['file'],
            'line' => $caller['line'],
            'varname' => self::getVarName($caller['file'], $caller['line'])
        );
    }

    /**
     * Finds out, if krexx was called too often, to prevent large output.
     *
     * @return bool
     *   Whether kreXX was called too often or not.
     */
    protected static function checkMaxCall()
    {
        $result = false;
        $maxCall = (int)Config::getConfigValue('runtime', 'maxCall');
        if (SkinRender::$KrexxCount >= $maxCall) {
            // Called too often, we might get into trouble here!
            $result = true;
        }
        // Give feedback if this is our last call.
        if (SkinRender::$KrexxCount == $maxCall - 1) {
            Messages::addMessage(Help::getHelp('maxCallReached'), 'critical');
        }
        return $result;
    }

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * If we use up too much, we might get a WSOD.
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   TRUE = all is OK.
     *   FALSE = we have a problem.
     */

    /**
     * Checks if there is enough memory and time left on the Server.
     *
     * @param mixed $enable
     *   Enables and disables the check itself. When disabled, it will always
     *   return TRUE (all is OK).
     *
     * @return bool
     *   Boolean to show if we have enough left.
     *   TRUE = all is OK.
     *   FALSE = we have a problem.
     */
    public static function checkEmergencyBreak($enable = null)
    {
        static $result = true;
        static $isDisabled = false;

        // We are saving the value of being enabled / disabled.
        if ($enable === true) {
            $isDisabled = false;
        }
        if ($enable === false) {
            $isDisabled = true;
        }

        // Tell them everything is fine, when it is disabled.
        if ($isDisabled) {
            return true;
        }

        if ($result === false) {
            // This has failed before!
            // No need to check again!
            return false;
        }

        // Check Runtime.
        if (self::$timer + (int)Config::getConfigValue('runtime', 'maxRuntime') <= time()) {
            // This is taking longer than expected.
            $result = false;
        }

        if ($result) {
            // Commence with ste memory check.
            // Check this only, if we have enough time left.
            $limit = strtoupper(ini_get('memory_limit'));
            $memoryLimit = 0;
            if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
                if ($matches[2] == 'M') {
                    // Megabyte.
                    $memoryLimit = $matches[1] * 1024 * 1024;
                } elseif ($matches[2] == 'K') {
                    // Kilobyte.
                    $memoryLimit = $matches[1] * 1024;
                }
            }

            // Were we able to determine a limit?
            if ($memoryLimit > 2) {
                $usage = memory_get_usage();
                $left = $memoryLimit - $usage;
                // Is more left than is configured?
                $result = $left >= (int)Config::getConfigValue('runtime', 'memoryLeft') * 1024 * 1024;
            }
        }

        if (!$result) {
            // No more memory or time, we disable kreXX!
            \Krexx::disable();
        }

        return $result;
    }

    /**
     * Tries to extract the name of the variable which we try to analyse.
     *
     * @param string $file
     *   Path to the sourcecode file.
     * @param string $line
     *   The line from where kreXX was called.
     *
     * @return string
     *   The name of the variable.
     */
    protected static function getVarName($file, $line)
    {
        // Retrieve the call from the sourcecode file.
        $source = file($file);

        // Now that we have the line where it was called, we must check if
        // we have several commands in there.
        $possibleCommands = explode(';', $source[$line - 1]);
        // Now we must weed out the none krexx commands.
        foreach ($possibleCommands as $key => $command) {
            if (strpos(strtolower($command), 'krexx') === false) {
                unset($possibleCommands[$key]);
            }
        }
        // I have no idea how to determine the actual call of krexx if we
        // are dealing with several calls per line.
        if (count($possibleCommands) > 1) {
            // Fallback to '...'.
            $varname = '...';
        } else {
            $sourceCall = reset($possibleCommands);

            // Now that we have our actual call, we must remove the krexx-part
            // from it.
            $possibleFunctionnames = array(
                'krexx',
                'krexx::open',
                'krexx::' . Config::getDevHandler(),
            );
            foreach ($possibleFunctionnames as $funcname) {
                preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', $sourceCall, $name);
                if (isset($name[1])) {
                    // Gotcha! We escape this one, just in case.
                    $varname = Variables::encodeString($name[1]);
                    break;
                }
            }
        }

        // Check if we have a value.
        if (!isset($varname) || strlen($varname) == 0) {
            $varname = '...';
        }

        return $varname;
    }

    /**
     * We decide if a function is currently within a reachable scope.
     *
     * @param string $type
     *   The type we are looking at, either class or array.
     *
     * @return bool
     *   Whether it is within the scope or not.
     */
    public static function isInScope($type = '')
    {
        // When analysing a class or array, we have + 1 on our nesting level, when
        // coming from the code generation. That is, because that class is currently
        // being analysed.
        if (strpos($type, 'class') === false && strpos($type, 'array') === false) {
            $nestingLevel = Internals::$nestingLevel;
        } else {
            $nestingLevel = Internals::$nestingLevel - 1;
        }

        return $nestingLevel <= 1 && Internals::$scope == '$this';
    }
}
