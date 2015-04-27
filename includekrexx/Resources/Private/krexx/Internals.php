<?php
/**
 * @file
 * Internal processing functions for kreXX
 * kreXX: Krumo eXXtended
 *
 * This is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @license http://opensource.org/licenses/LGPL-2.1 GNU Lesser General Public License Version 2.1
 * @package Krexx
 */

namespace Krexx;

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
   * @var \Krexx\ShutdownHandler
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
    $ar_aff['url'] = Toolbox::getCurrentUrl();
    $ar_aff['total_time'] = $tttime;
    $prv_cle = 'start';
    $prv_val = $arg_t['start'];

    foreach ($arg_t as $cle => $val) {
      if ($cle != 'start') {
        // Calculate the time.
        $prcnt_t = round(((round(($val - $prv_val) * 1000, 4) / $tttime) * 100), 1);
        $ar_aff[$prv_cle . ' -> ' . $cle] = $prcnt_t . '%';
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
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analysisHub(&$data, $name = '...') {

    // Ceck memory and runtime.
    if (!self::checkEmergencyBreak()) {
      // No more took too long, or not enough memory is left.
      Messages::addMessage("Emergency break for large output during rendering process.\n\nYou should try to switch to file output.");
      return '';
    }

    // Object?
    if (is_object($data)) {
      self::$nestingLevel++;
      if (self::$nestingLevel <= (int) Config::getConfigValue('deep', 'level')) {
        $result = Objects::analyseObject($data, $name);
        self::$nestingLevel--;
        return $result;
      }
      else {
        self::$nestingLevel--;
        return Variables::analyseString("Object => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Array?
    if (is_array($data)) {
      self::$nestingLevel++;
      if (self::$nestingLevel <= (int) Config::getConfigValue('deep', 'level')) {
        $result = Variables::analyseArray($data, $name);
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
      return Variables::analyseResource($data, $name);
    }

    // String?
    if (is_string($data)) {
      return Variables::analyseString($data, $name);
    }

    // Float?
    if (is_float($data)) {
      return Variables::analyseFloat($data, $name);
    }

    // Integer?
    if (is_int($data)) {
      return Variables::analyseInteger($data, $name);
    }

    // Boolean?
    if (is_bool($data)) {
      return Variables::analyseBoolean($data, $name);
    }

    // Null ?
    if (is_null($data)) {
      return Variables::analyseNull($name);
    }
  }

  /**
   * Render a dump for the properties of an array or object.
   *
   * @param array|object &$data
   *   The object or array we want to analyse.
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

      // Recursion detection of objects are
      // handeld in the hub.
      if (is_array($data) && Hive::isInHive($data)) {
        return Render::renderRecursion();
      }

      // Remember, that we've already been here.
      Hive::addToHive($data);

      // Keys?
      if ($is_object) {
        $keys = array_keys(get_object_vars($data));
      }
      else {
        $keys = array_keys($data);
      }

      // Itterate through.
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

        $output .= Internals::analysisHub($v, $k);
      }
      return $output;
    };
    return Render::renderExpandableChild('', '', $analysis, $parameter);
  }

  /**
   * Dump information about a variable.
   *
   * Here erverything starts and ends (well, unless we are only outputting
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
    Render::$KrexxCount++;
    // We need to get the footer before the generating of the header,
    // because we need to display messages in the header.
    $footer = Toolbox::outputFooter($caller);
    $analysis = self::analysisHub($data);
    self::$shutdownHandler->addChunkString(Toolbox::outputHeader($headline, $ignore_local_settings), $ignore_local_settings);
    self::$shutdownHandler->addChunkString(Messages::outputMessages(), $ignore_local_settings);
    self::$shutdownHandler->addChunkString($analysis, $ignore_local_settings);
    self::$shutdownHandler->addChunkString($footer, $ignore_local_settings);

    // Cleanup the hive, this removes all recursion markers.
    Hive::cleanupHive();
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

    // Find caller.
    $caller = self::findCaller();

    $headline = 'Backtrace';

    if (self::checkMaxCall()) {
      // Called too often, we might get into trouble here!
      return;
    }

    // Start Output.
    Render::$KrexxCount++;

    // Remove the fist step from the backtrace,
    // because that is the interal function in kreXX.
    $backtrace = debug_backtrace();
    unset($backtrace[0]);
    $footer = Toolbox::outputFooter($caller);
    $analysis = Toolbox::outputBacktrace($backtrace);

    self::$shutdownHandler->addChunkString(Toolbox::outputHeader($headline));
    self::$shutdownHandler->addChunkString(Messages::outputMessages());
    self::$shutdownHandler->addChunkString($analysis);
    self::$shutdownHandler->addChunkString($footer);

    // Cleanup the hive, this removes all recursion markers.
    Hive::cleanupHive();
  }

  /**
   * Finds the place in the code from where krexx was called.
   *
   * @return string
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

    return $caller;
  }

  /**
   * Finds out, if krexx was called too often, to prevent large output.
   *
   * @return bool
   *   Whether kreXX was called too often or not.
   */
  protected static function checkMaxCall() {
    $result = FALSE;
    $max_call = (int) Config::getConfigValue('output', 'maxCall');
    if (Render::$KrexxCount >= $max_call) {
      // Called too often, we might get into trouble here!
      $result = TRUE;
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
    if (self::$timer + (int) Config::getConfigValue('render', 'maxRuntime') <= time()) {
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
        $result = $left >= (int) Config::getConfigValue('render', 'memoryLeft') * 1024 * 1024;
      }
    }

    if (!$result) {
      // No more memory or time, we disable kreXX!
      \Krexx::disable();
    }

    return $result;
  }
}
