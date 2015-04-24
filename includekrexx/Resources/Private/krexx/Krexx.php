<?php
/**
 * @file
 * Sourcecode GUI for kreXX
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

use Krexx\Toolbox;
use Krexx\Internals;
use Krexx\Config;
use Krexx\Messages;

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
function krexx($data, $handle = '') {
  if ($handle == '') {
    \Krexx::open($data);
  }
  else {
    \Krexx::$handle($data);
  }
}

// Inculde some files and set some internal values.
\Krexx::bootstrapKrexx();

/**
 * Public functions, allowing access to the kreXX debug features.
 *
 * @package Krexx
 */
class Krexx {

  /**
   * Here we store the fatal error handler.
   *
   * @var \Krexx\Errorhandler\Fatal
   */
  protected static $krexxFatal;

  /**
   * Stores wheather out fatal error handler should be active.
   *
   * During a kreXX analysis, we deactivate it to improve performance.
   * Here we save, whether we should reactivate it.
   *
   * @var boolean
   */
  protected static $fatalShouldActive = FALSE;

  /**
   * Here we save all timekeeping stuff.
   *
   * @var string array
   */
  protected static $timekeeping = array();

  /**
   * Remembers, if the header or footer must be printed.
   *
   * @var int
   */
  protected static $startRun = 0;
  protected static $counterCache = array();

  /**
   * Includs all needed files and sets some internal values.
   */
  public static function bootstrapKrexx() {

    if (!defined('KREXXDIR')) {
      define("KREXXDIR", self::getKrexxDir());
    }
    include_once KREXXDIR . 'Help.php';
    include_once KREXXDIR . 'Render.php';
    include_once KREXXDIR . 'Hive.php';
    include_once KREXXDIR . 'Config.php';
    include_once KREXXDIR . 'Toolbox.php';
    include_once KREXXDIR . 'Internals.php';
    include_once KREXXDIR . 'Objects.php';
    include_once KREXXDIR . 'Variables.php';
    include_once KREXXDIR . 'Messages.php';
    include_once KREXXDIR . 'Chunks.php';
    include_once KREXXDIR . 'ShutdownHandler.php';

    include_once KREXXDIR . 'errorhandler/AbstractHandler.php';
    include_once KREXXDIR . 'errorhandler/Fatal.php';

    // Setting template info.
    if (is_null(\Krexx\Render::$skin)) {
      \Krexx\Render::$skin = \Krexx\Config::getConfigValue('render', 'skin');
    }

    // Regsiter our shutdown handler. He will handle the display
    // of kreXX after the hosting CMS is finished.
    Internals::$shutdownHandler = new \Krexx\ShutdownHandler();
    register_shutdown_function(array(Internals::$shutdownHandler, 'shutdownCallback'));

    // Check if the log and chunk folder are writeable.
    // If not, give feedback!
    if (!is_writeable(KREXXDIR . 'chunks' . DIRECTORY_SEPARATOR)) {
      \Krexx\Messages::addMessage('Chunksfolder ' . KREXXDIR . 'chunks' . DIRECTORY_SEPARATOR . ' is not writeable!', 'critical');
    }
    if (!is_writeable(KREXXDIR . 'log' . DIRECTORY_SEPARATOR)) {
      \Krexx\Messages::addMessage('Logfolder ' . KREXXDIR . 'log' . DIRECTORY_SEPARATOR . ' is not writeable !', 'critical');
    }
    // At this point, we won't inform the user right away. The error message
    // will pop up, when kreXX is actually displayed, no need to bother the
    // dev just now.
    // We might need to register our Backtracer.
    if (\Krexx\Config::getConfigValue('errorHandling', 'registerAutomatically') == 'true') {
      self::registerFatal();
    }

  }

  /**
   * Returns the kreXX directory.
   *
   * @return string
   *   The kreXX directory.
   */
  protected static function getKrexxDir() {
    static $krexx_dir;

    if (!isset($krexx_dir)) {
      $krexx_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR;
    }
    return $krexx_dir;
  }

  /**
   * Handles the developer handle.
   *
   * @param string $name
   *   The name of the static function which was called.
   * @param array $arguments
   *   The arguments of said function.
   */
  public static function __callStatic($name, array $arguments) {
    self::noFatalForKrexx();
    // Do we gave a handle?
    $handle = Krexx\Config::getConfigFromCookies('deep', 'Local open function');
    if ($name == $handle) {
      // We do a standard-open.
      if (isset($arguments[0])) {
        self::open($arguments[0]);
      }
      else {
        self::open();
      }
    }
    else {
      // Do nothing.
    }
    self::reFatalAfterKrexx();
  }

  /**
   * Resets the timer and takes a "moment".
   */
  public Static function timerStart() {
    self::noFatalForKrexx();
    // Disabled ?
    if (!Config::isEnabled()) {
      return;
    }

    // Reset what we had before.
    self::$timekeeping = array();
    self::$counterCache = array();
    self::$timekeeping[] = Toolbox::getCurrentUrl();

    self::timerMoment('start');
    self::reFatalAfterKrexx();
  }

  /**
   * Takes a "moment".
   *
   * @param string $string
   *   Defines a "moment" during a benchmark test.
   *   The string should be something meaningfull, like "Model invoice db call".
   */
  public static function timerMoment($string) {
    self::noFatalForKrexx();
    // Disabled?
    if (!Config::isEnabled()) {
      return;
    }

    // Did we use this one before?
    if (isset(self::$counterCache[$string])) {
      // Add another to the counter.
      self::$counterCache[$string]++;
      self::$timekeeping['[' . self::$counterCache[$string] . ']' . $string] = microtime(TRUE);
    }
    else {
      // First time counter, set it to 1.
      self::$counterCache[$string] = 1;
      self::$timekeeping[$string] = microtime(TRUE);
    }
    self::reFatalAfterKrexx();
  }

  /**
   * Takes a "moment" and outputs the timer.
   */
  public static function timerEnd() {
    self::noFatalForKrexx();
    // Disabled ?
    if (!Config::isEnabled()) {
      return;
    }
    self::timerMoment('end');
    // And we are done. Feedback to the user.
    Internals::dump(Internals::miniBenchTo(self::$timekeeping), 'kreXX timer');
    self::reFatalAfterKrexx();
  }

  /**
   * Starts the analysis of a variable.
   *
   * @param mixed $data
   *   The variable we want to analyse.
   */
  Public Static function open($data = NULL) {
    self::noFatalForKrexx();
    // Disabled?
    if (!Config::isEnabled()) {
      return;
    }
    Internals::dump($data);
    self::reFatalAfterKrexx();
  }

  /**
   * Prints a debug backtrace.
   *
   * When there are classes found inside the backtrave,
   * they will be analysed.
   */
  Public Static Function backtrace() {
    self::noFatalForKrexx();
    // Disabled?
    if (!Config::isEnabled()) {
      return;
    }
    // Render it.
    Internals::backtrace();
    self::reFatalAfterKrexx();
  }

  /**
   * Enable kreXX.
   */
  Public Static Function enable() {
    self::noFatalForKrexx();
    Config::isEnabled(TRUE);
    self::reFatalAfterKrexx();
  }

  /**
   * Disable kreXX.
   */
  Public Static Function disable() {
    self::noFatalForKrexx();
    Config::isEnabled(FALSE);
    // We will not re-enable it afterwards, because kreXX
    // is disabled and the handler would not show up anyway.
  }

  /**
   * Displays the edit settings part, no analysis.
   *
   * Ignores the 'disabled' settings in the cookie.
   */
  Public Static Function editSettings() {
    self::noFatalForKrexx();
    // Disabled?
    // We are ignoring local settings here.
    if (!Config::isEnabled(NULL, TRUE)) {
      return;
    }
    Internals::$timer = time();

    // Find caller.
    $caller = Internals::findCaller();

    // Render it.
    Krexx\Render::$KrexxCount++;
    $footer = Toolbox::outputFooter($caller, TRUE);
    Internals::$shutdownHandler->addChunkString(Toolbox::outputHeader('Edit local settings', TRUE), TRUE);
    Internals::$shutdownHandler->addChunkString(Messages::outputMessages(), TRUE);
    Internals::$shutdownHandler->addChunkString($footer, TRUE);

    // Cleanup the hive.
    \Krexx\Hive::cleanupHive();
    self::reFatalAfterKrexx();
  }

  /**
   * Registers a shutdown function.
   *
   * Our fatal errorhandler is located there.
   */
  Public Static Function registerFatal() {
    // Disabled?
    if (!Config::isEnabled()) {
      return;
    }

    // Do we need another shutdown handler?
    if (!is_object(self::$krexxFatal)) {
      self::$krexxFatal = new \Krexx\Errorhandler\Fatal();
      declare(ticks = 1);
      register_shutdown_function(array(self::$krexxFatal, 'shutdownCallback'));
    }
    register_tick_function(array(self::$krexxFatal, 'tickCallback'));
    self::$krexxFatal->setIsActive(TRUE);
    self::$fatalShouldActive = TRUE;
  }

  /**
   * Tells the registered shutdown function to do nothing.
   *
   * We can not unregister a oncedeclared shutdown function,
   * so we need to tell our errorhandler to do nothing, in case
   * there is a fatal.
   */
  Public Static Function unregisterFatal() {
    // Disabled?
    if (!Config::isEnabled()) {
      return;
    }

    if (!is_null(self::$krexxFatal)) {
      // Now we need to tell the shutdown function, that is must
      // not do anything on shutdown.
      self::$krexxFatal->setIsActive(FALSE);
      unregister_tick_function(array(self::$krexxFatal, 'tickCallback'));
    }
    self::$fatalShouldActive = FALSE;
  }

  /**
   * Disables the fatal handler and the tick callback.
   *
   * We disable the tick callback and the error handler during
   * a analysis, to generate faster output.
   */
  protected static function noFatalForKrexx() {
    if (self::$fatalShouldActive) {
      self::$krexxFatal->setIsActive(FALSE);
      unregister_tick_function(array(self::$krexxFatal, 'tickCallback'));
    }
  }

  /**
   * Re-enable the fatal handler and the tick callback.
   *
   * We disable the tick callback and the error handler during
   * a analysis, to generate faster output.
   */
  protected static function reFatalAfterKrexx() {
    if (self::$fatalShouldActive) {
      self::$krexxFatal->setIsActive(TRUE);
      register_tick_function(array(self::$krexxFatal, 'tickCallback'));
    }
  }
}
