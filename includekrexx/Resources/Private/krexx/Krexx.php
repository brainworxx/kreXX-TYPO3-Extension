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

use Brainworxx\Krexx\Analysis;
use Brainworxx\Krexx\Errorhandler;
use Brainworxx\Krexx\Framework;
use Brainworxx\Krexx\View;

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
function krexx($data = NULL, $handle = '') {
  if ($handle == '') {
    \Krexx::open($data);
  }
  else {
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
class Krexx {

  /**
   * Here we store the fatal error handler.
   *
   * @var \Brainworxx\Krexx\Errorhandler\Fatal
   */
  protected static $krexxFatal;

  /**
   * Stores whether out fatal error handler should be active.
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
   * Includes all needed files and sets some internal values.
   */
  public static function bootstrapKrexx() {

    $krexxdir = dirname(__FILE__) . DIRECTORY_SEPARATOR;;
    include_once $krexxdir . 'src/view/Help.php';
    include_once $krexxdir . 'src/view/Render.php';
    include_once $krexxdir . 'src/view/Messages.php';
    include_once $krexxdir . 'src/view/Codegen.php';
    include_once $krexxdir . 'src/view/Output.php';
    include_once $krexxdir . 'src/framework/Config.php';
    include_once $krexxdir . 'src/framework/Toolbox.php';
    include_once $krexxdir . 'src/framework/Chunks.php';
    include_once $krexxdir . 'src/framework/ShutdownHandler.php';
    include_once $krexxdir . 'src/framework/Internals.php';
    include_once $krexxdir . 'src/analysis/objects/Comments.php';
    include_once $krexxdir . 'src/analysis/objects/Flection.php';
    include_once $krexxdir . 'src/analysis/objects/Methods.php';
    include_once $krexxdir . 'src/analysis/objects/Objects.php';
    include_once $krexxdir . 'src/analysis/objects/Properties.php';
    include_once $krexxdir . 'src/analysis/Hive.php';
    include_once $krexxdir . 'src/analysis/Variables.php';
    include_once $krexxdir . 'src/errorhandler/AbstractHandler.php';
    include_once $krexxdir . 'src/errorhandler/Fatal.php';

    Framework\Config::$krexxdir = $krexxdir;

    // Setting the skin info.
    if (is_null(View\Render::$skin)) {
      View\Render::$skin = Framework\Config::getConfigValue('render', 'skin');
    }
    // Every skin has an own implementation of the render class. We need to
    // include this one, too.
    include_once $krexxdir . 'resources/skins/' . Framework\Config::getConfigValue('render', 'skin') . '/SkinRender.php';

    // Register our shutdown handler. He will handle the display
    // of kreXX after the hosting CMS is finished.
    Framework\Internals::$shutdownHandler = new Framework\ShutdownHandler();
    register_shutdown_function(array(Framework\Internals::$shutdownHandler, 'shutdownCallback'));

    // Check if the log and chunk folder are writable.
    // If not, give feedback!
    if (!is_writeable($krexxdir . 'chunks' . DIRECTORY_SEPARATOR)) {
      View\Messages::addMessage('Chunksfolder ' . $krexxdir . 'chunks' . DIRECTORY_SEPARATOR . ' is not writable!. This will increase the memory usage of kreXX significantly!', 'critical');
      View\Messages::addKey('protected.folder.chunk', array($krexxdir . 'chunks' . DIRECTORY_SEPARATOR ));
      // We can work without chunks, but this will require much more memory!
      Brainworxx\Krexx\Framework\Chunks::setUseChunks(FALSE);
    }
    if (!is_writeable($krexxdir . Framework\Config::getConfigValue('logging', 'folder') . DIRECTORY_SEPARATOR)) {
      View\Messages::addMessage('Logfolder ' . $krexxdir . Framework\Config::getConfigValue('logging', 'folder') . DIRECTORY_SEPARATOR . ' is not writable !', 'critical');
      View\Messages::addKey('protected.folder.log', array($krexxdir . Framework\Config::getConfigValue('logging', 'folder') . DIRECTORY_SEPARATOR));
    }
    // At this point, we won't inform the user right away. The error message
    // will pop up, when kreXX is actually displayed, no need to bother the
    // dev just now.
    // We might need to register our Backtracer.
    if (Framework\Config::getConfigValue('errorHandling', 'registerAutomatically') == 'true') {
      self::registerFatal();
    }

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
    $handle = Framework\Config::getConfigFromCookies('deep', 'Local open function');
    if ($name == $handle) {
      // We do a standard-open.
      if (isset($arguments[0])) {
        self::open($arguments[0]);
      }
      else {
        self::open();
      }
    }
    self::reFatalAfterKrexx();
  }

  /**
   * Resets the timer and takes a "moment".
   */
  public Static function timerStart() {
    self::noFatalForKrexx();
    // Disabled ?
    if (!Framework\Config::isEnabled()) {
      return;
    }

    // Reset what we had before.
    self::$timekeeping = array();
    self::$counterCache = array();
    self::$timekeeping[] = Framework\Toolbox::getCurrentUrl();

    self::timerMoment('start');
    self::reFatalAfterKrexx();
  }

  /**
   * Takes a "moment".
   *
   * @param string $string
   *   Defines a "moment" during a benchmark test.
   *   The string should be something meaningful, like "Model invoice db call".
   */
  public static function timerMoment($string) {
    self::noFatalForKrexx();
    // Disabled?
    if (!Framework\Config::isEnabled()) {
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
    if (!Framework\Config::isEnabled()) {
      return;
    }
    self::timerMoment('end');
    // And we are done. Feedback to the user.
    Framework\Internals::dump(Framework\Internals::miniBenchTo(self::$timekeeping), 'kreXX timer');
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
    if (!Framework\Config::isEnabled()) {
      return;
    }
    Framework\Internals::dump($data);
    self::reFatalAfterKrexx();
  }

  /**
   * Prints a debug backtrace.
   *
   * When there are classes found inside the backtrace,
   * they will be analysed.
   */
  Public Static Function backtrace() {
    self::noFatalForKrexx();
    // Disabled?
    if (!Framework\Config::isEnabled()) {
      return;
    }
    // Render it.
    Framework\Internals::backtrace();
    self::reFatalAfterKrexx();
  }

  /**
   * Enable kreXX.
   */
  Public Static Function enable() {
    self::noFatalForKrexx();
    Framework\Config::isEnabled(TRUE);
    self::reFatalAfterKrexx();
  }

  /**
   * Disable kreXX.
   */
  Public Static Function disable() {
    self::noFatalForKrexx();
    Framework\Config::isEnabled(FALSE);
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
    if (!Framework\Config::isEnabled(NULL, TRUE)) {
      return;
    }
    Framework\Internals::$timer = time();

    // Find caller.
    $caller = Framework\Internals::findCaller();

    // Render it.
    View\Render::$KrexxCount++;
    $footer = View\Output::outputFooter($caller, TRUE);
    Framework\Internals::$shutdownHandler->addChunkString(View\Output::outputHeader('Edit local settings', TRUE), TRUE);
    Framework\Internals::$shutdownHandler->addChunkString($footer, TRUE);

    // Cleanup the hive.
    Analysis\Hive::cleanupHive();
    self::reFatalAfterKrexx();
  }

  /**
   * Registers a shutdown function.
   *
   * Our fatal errorhandler is located there.
   */
  Public Static Function registerFatal() {
    // Disabled?
    if (!Framework\Config::isEnabled()) {
      return;
    }

    // As of PHP Version 7.0.2, the register_tick_function() causesPHP to crash,
    // with a connection reset! We need to check the version to avoid this, and
    // then tell the dev what happened.
    if (version_compare(phpversion(), '7.0.0', '>=')) {
      // Too high! 420 Method Failure :-(
      View\Messages::addMessage(Brainworxx\Krexx\View\Help::getHelp('php7yellow'));
      krexx(Brainworxx\Krexx\View\Help::getHelp('php7'));

      // Just return, there is nothing more to do here.
      return;
    }

    // Do we need another shutdown handler?
    if (!is_object(self::$krexxFatal)) {
      self::$krexxFatal = new Errorhandler\Fatal();
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
   * We can not unregister a once declared shutdown function,
   * so we need to tell our errorhandler to do nothing, in case
   * there is a fatal.
   */
  Public Static Function unregisterFatal() {
    // Disabled?
    if (!Framework\Config::isEnabled()) {
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
