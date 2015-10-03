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
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analysis;

use Brainworxx\Krexx\Framework;
use Brainworxx\Krexx\View;

/**
 * This class hosts the internal analysis functions.
 *
 * @package Krexx
 */
class Internals {

  public static $nestingLevel = 0;

  /**
   * Sends the output to the browser during shutdown phase.
   *
   * @var Framework\ShutdownHandler
   */
  public static $shutdownHandler;


  /**
   * Unix timestamp, used to determine if we need to do an emergency break.
   *
   * @var int
   */
  public static $timer = 0;

  /**
   * The benchmark mainfunction.
   *
   * @param array $arg_t
   *   The timekeeping array.
   *
   * @return array
   *   The benchmark array.
   *
   * @see http://php.net/manual/de/function.microtime.php
   * @author gomodo at free dot fr
   */
  public static function miniBenchTo(array $arg_t) {
    $tttime = round((end($arg_t) - $arg_t['start']) * 1000, 4);
    $ar_aff['url'] = Framework\Toolbox::getCurrentUrl();
    $ar_aff['total_time'] = $tttime;
    $prv_cle = 'start';
    $prv_val = $arg_t['start'];

    foreach ($arg_t as $cle => $val) {
      if ($cle != 'start') {
        // Calculate the time.
        $prcnt_t = round(((round(($val - $prv_val) * 1000, 4) / $tttime) * 100), 1);
        $ar_aff[$prv_cle . '->' . $cle] = $prcnt_t . '%';
        $prv_val = $val;
        $prv_cle = $cle;
      }
    }
    return $ar_aff;
  }

  /**
   * Dump information about a variable.
   *
   * This function decides what functions analyse the data
   * and acts as a hub.
   *
   * @param mixed $data
   *   The variable we are analysing.
   * @param string $name
   *   The name of the variable, if available.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analysisHub(&$data, $name = '', $connector1 = '', $connector2 = '') {

    // Check memory and runtime.
    if (!self::checkEmergencyBreak()) {
      // No more took too long, or not enough memory is left.
      View\Messages::addMessage("Emergency break for large output during rendering process.\n\nYou should try to switch to file output.");
      return '';
    }

    // If we are currently analysing an array, we might need to add stuff to
    // the connector.
    if ($connector1 == '[' && is_string($name)) {
      $connector1 = $connector1 . "'";
      $connector2 = "'" . $connector2;
    }

    // Object?
    // Closures are analysed separately.
    if (is_object($data) && !is_a($data, '\Closure')) {
      self::$nestingLevel++;
      if (self::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
        $result = Objects::analyseObject($data, $name, '', $connector1, $connector2);
        self::$nestingLevel--;
        return $result;
      }
      else {
        self::$nestingLevel--;
        return Variables::analyseString("Object => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Closure?
    if (is_object($data) && is_a($data, '\Closure')) {
      self::$nestingLevel++;
      if (self::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
        if ($connector2 == '] =') {
          $connector2 = ']';
        }
        $result = Objects::analyseClosure($data, $name, '', $connector1, $connector2);
        self::$nestingLevel--;
        return $result;
      }
      else {
        self::$nestingLevel--;
        return Variables::analyseString("Closure => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Array?
    if (is_array($data)) {
      self::$nestingLevel++;
      if (self::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
        $result = Variables::analyseArray($data, $name, '', $connector1, $connector2);
        self::$nestingLevel--;
        return $result;
      }
      else {
        self::$nestingLevel--;
        return Variables::analyseString("Array => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Resource?
    if (is_resource($data)) {
      return Variables::analyseResource($data, $name, '', $connector1, $connector2);
    }

    // String?
    if (is_string($data)) {
      return Variables::analyseString($data, $name, '', $connector1, $connector2);
    }

    // Float?
    if (is_float($data)) {
      return Variables::analyseFloat($data, $name, '', $connector1, $connector2);
    }

    // Integer?
    if (is_int($data)) {
      return Variables::analyseInteger($data, $name, '', $connector1, $connector2);
    }

    // Boolean?
    if (is_bool($data)) {
      return Variables::analyseBoolean($data, $name, '', $connector1, $connector2);
    }

    // Null ?
    if (is_null($data)) {
      return Variables::analyseNull($name, '', $connector1, $connector2);
    }

    // Still here? This should not happen. Return empty string, just in case.
    return '';
  }

  /**
   * Render a dump for the properties of an array or object.
   *
   * @param array &$data
   *   The array we want to analyse.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function iterateThrough(&$data) {
    $parameter = array($data);
    $analysis = function (&$parameter) {
      $output = '';
      $data = $parameter[0];
      $is_object = is_object($data);

      $recursion_marker = Hive::getMarker();

      // Recursion detection of objects are handled in the hub.
      if (is_array($data) && Hive::isInHive($data)) {
        return View\Render::renderRecursion();
      }

      // Remember, that we've already been here.
      Hive::addToHive($data);

      // Keys?
      $keys = array_keys($data);

      $output .= View\Render::renderSingeChildHr();

      // Iterate through.
      foreach ($keys as $k) {

        // Skip the recursion marker.
        if ($k === $recursion_marker) {
          continue;
        }

        // Get real value.
        if ($is_object) {
          $v = & $data->$k;
        }
        else {
          $v = & $data[$k];
        }

        $output .= Internals::analysisHub($v, $k, '[', '] =');
      }
      $output .= View\Render::renderSingeChildHr();
      return $output;
    };
    return View\Render::renderExpandableChild('', '', $analysis, $parameter);
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
   * @param bool $ignore_local_settings
   *   Are we ignoring local settings.
   */
  public Static Function dump($data, $headline = '', $ignore_local_settings = FALSE) {

    // Start the timer.
    // When a certain time has passed, kreXX will use an
    // emergency break to prevent too large output (or no output at all (WSOD)).
    if (self::$timer == 0) {
      self::$timer = time();
    }

    // Find caller.
    $caller = self::findCaller();

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
    View\Render::$KrexxCount++;
    // We need to get the footer before the generating of the header,
    // because we need to display messages in the header.
    $footer = Framework\Toolbox::outputFooter($caller);
    // Start the analysis itself.
    View\Codegen::resetCounter();

    // Enable code generation only if we were aqble to determine the varname
    if ($caller['varname'] == '...') {
      Framework\Config::$allowCodegen = FALSE;
    }
    else {
      // We were able to determine the variable name and can generate some
      // sourcecode.
      Framework\Config::$allowCodegen = TRUE;
    }

    $analysis = self::analysisHub($data, $caller['varname'], '', '=');
    self::$shutdownHandler->addChunkString(Framework\Toolbox::outputHeader($headline, $ignore_local_settings));
    self::$shutdownHandler->addChunkString(View\Messages::outputMessages());
    self::$shutdownHandler->addChunkString($analysis);
    self::$shutdownHandler->addChunkString($footer);

    // Cleanup the hive, this removes all recursion markers.
    Hive::cleanupHive();

    // Reset value for the code generation.
    Framework\Config::$allowCodegen = FALSE;
  }

  /**
   * Outputs a backtrace.
   *
   */
  public static function backtrace() {
    // Start the timer.
    // When a certain time has passed, kreXX will use an
    // emergency break to prevent too large output (or no output at all (WSOD)).
    if (self::$timer == 0) {
      self::$timer = time();
    }

    Framework\Config::$allowCodegen = FALSE;

    // Find caller.
    $caller = self::findCaller();

    $headline = 'Backtrace';

    if (self::checkMaxCall()) {
      // Called too often, we might get into trouble here!
      return;
    }

    // Start Output.
    View\Render::$KrexxCount++;

    // Remove the fist step from the backtrace,
    // because that is the internal function in kreXX.
    $backtrace = debug_backtrace();
    unset($backtrace[0]);
    $footer = Framework\Toolbox::outputFooter($caller);
    $analysis = Framework\Toolbox::outputBacktrace($backtrace);

    self::$shutdownHandler->addChunkString(Framework\Toolbox::outputHeader($headline));
    self::$shutdownHandler->addChunkString(View\Messages::outputMessages());
    self::$shutdownHandler->addChunkString($analysis);
    self::$shutdownHandler->addChunkString($footer);

    // Cleanup the hive, this removes all recursion markers.
    Hive::cleanupHive();
  }

  /**
   * Finds the place in the code from where krexx was called.
   *
   * @return array
   *   The code, from where krexx was called
   */
  public static function findCaller() {
    $_ = debug_backtrace();
    while ($caller = array_pop($_)) {
      if (isset($caller['function']) && strtolower($caller['function']) == 'krexx') {
        break;
      }
      if (isset($caller['class']) && strtolower($caller['class']) == 'krexx') {
        break;
      }
    }

    // We will not keep the whole backtrace im memory. We only return what we
    // actually need.
    return array('file' => $caller['file'], 'line' => $caller['line'], 'varname' => self::getVarName($caller['file'], $caller['line']));
  }

  /**
   * Finds out, if krexx was called too often, to prevent large output.
   *
   * @return bool
   *   Whether kreXX was called too often or not.
   */
  protected static function checkMaxCall() {
    $result = FALSE;
    $max_call = (int) Framework\Config::getConfigValue('output', 'maxCall');
    if (View\Render::$KrexxCount >= $max_call) {
      // Called too often, we might get into trouble here!
      $result = TRUE;
    }
    // Give feedback if this is our last call.
    if (View\Render::$KrexxCount == $max_call - 1) {
      View\Messages::addMessage('Maximum call-level reached. This is the last analysis for this request. To increase this value, please edit:<br />output => maxCall.','critical');
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
  public static function checkEmergencyBreak() {
    static $result = TRUE;

    if (!$result) {
      // This has failed before!
      // no need to check again!
      return $result;
    }

    // Check Runtime.
    if (self::$timer + (int) Framework\Config::getConfigValue('render', 'maxRuntime') <= time()) {
      // This is taking longer than expected.
      $result = FALSE;
    }

    if ($result) {
      // Check this only, if we have enough time left.
      $limit = strtoupper(ini_get('memory_limit'));
      $memory_limit = 0;
      if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {

        if ($matches[2] == 'M') {
          // Megabyte.
          $memory_limit = $matches[1] * 1024 * 1024;
        }
        elseif ($matches[2] == 'K') {
          // Kilobyte.
          $memory_limit = $matches[1] * 1024;
        }
      }

      // Were we able to determine a limit?
      if ($memory_limit > 2) {
        $usage = memory_get_usage();
        $left = $memory_limit - $usage;
        // Is more left than is configured?
        $result = $left >= (int) Framework\Config::getConfigValue('render', 'memoryLeft') * 1024 * 1024;
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
  protected static function getVarName($file, $line) {
    // Retrieve the call from the sourcecode file
    $source = file($file);

    // Now that we have the line where it was called, we must check if
    // we have several commands in there.
    $possible_commands = explode(';', $source[$line - 1]);
    // Now we must weed out the none krexx commands.
    foreach ($possible_commands as $key => $command) {
      if (strpos(strtolower($command), 'krexx') === FALSE) {
        unset($possible_commands[$key]);
      }
    }
    // I have no idea how to determine the actual call of krexx if we
    // are dealing with several calls per line.
    if (count($possible_commands) > 1) {
      // Fallback to '...'.
      $varname = '...';
    }
    else {
      $source_call = reset($possible_commands);

      // Now that we have our actual call, we must remove the krexx-part
      // from it.
      $possible_functionnames = array(
        'krexx',
        'krexx::open',
        'krexx::' . Framework\Config::getDevHandler(),
      );
      foreach ($possible_functionnames as $funcname) {
        preg_match('/' . $funcname . '\s*\((.*)\)\s*/u', $source_call, $name);
        if (isset($name[1])) {
          // Gotcha! We escape this one, just in case.
          $varname = \Brainworxx\Krexx\Analysis\Variables::encodeString($name[1]);
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
}
