<?php
/**
 * @file
 *   Abstract errorhandler for kreXX
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

namespace Brainworxx\Krexx\Errorhandler;

use Brainworxx\Krexx\Analysis;
use Brainworxx\Krexx\Framework;
use Brainworxx\Krexx\View;

/**
 * This class hosts all functions which all error handlers will share
 * (as soon as they are written . . .)
 *
 * @package Krexx
 */
abstract class AbstractHandler {

  /**
   * Stores if the handler is active.
   *
   * Decides if the registered shutdown function should
   * do anything, in case we decide later that we do not
   * want to interfere.
   *
   * @var bool
   */
  protected $isActive = FALSE;

  /**
   * Decides, if the handler does anything.
   *
   * @return bool
   *   Returns TRUE when kreXX is active and this
   *   handler is active
   */
  protected function getIsActive() {
    if ($this->isActive && Framework\Config::isEnabled()) {
      // We will only handle errors when kreXX and the handler
      // itself is enabled.
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * Renders the info to the error, warning or notice.
   *
   * @param array $error_data
   *   The data frm the error. This should be a backtrace
   *   with code samples.
   */
  protected function giveFeedback(array $error_data) {
    if ($this->isActive) {
      View\Render::$KrexxCount++;
      Analysis\Internals::$timer = time();

      // Setting template info.
      if (is_null(View\Render::$skin)) {
        View\Render::$skin = Framework\Config::getConfigValue('render', 'skin');
      }

      // Get the header.
      $header = View\Render::renderFatalHeader(
          Framework\Toolbox::outputCssAndJs(),
          '<!DOCTYPE html>');
      // Get the main part.
      $main = View\Render::renderFatalMain(
          $error_data['type'],
          $error_data['errstr'],
          $error_data['errfile'],
          $error_data['errline'] + 1,
          $error_data['source']);
      // Get the backtrace.
      $backtrace = Framework\Toolbox::outputBacktrace($error_data['backtrace']);
      // Get the footer.
      $footer = Framework\Toolbox::outputFooter('');
      // Get the messages.
      $messages = View\Messages::outputMessages();

      Framework\Toolbox::outputNow($header . $messages . $main . $backtrace . $footer);

      // Cleanup the hive, this removes all recursion markers.
      Analysis\Hive::cleanupHive();
    }
  }

  /**
   * Translates the error number into human readable text.
   *
   * It also includes the corresponding config
   * setting, so we can decide if we want to output
   * anything.
   *
   * @param int $error_int
   *   The error number.
   *
   * @return array
   *   The translated type and the setting.
   */
  protected function translateErrorType($error_int) {
    switch ($error_int) {
      case E_ERROR:
        $error_name = 'Fatal';
        $error_setting = 'traceFatals';
        break;

      case E_WARNING:
        $error_name = 'Warning';
        $error_setting = 'traceWarnings';
        break;

      case E_PARSE:
        $error_name = 'Parse error';
        $error_setting = 'traceFatals';
        break;

      case E_NOTICE:
        $error_name = 'Notice';
        $error_setting = 'traceNotices';
        break;

      case E_CORE_ERROR:
        $error_name = 'PHP startup error';
        $error_setting = 'traceFatals';
        break;

      case E_CORE_WARNING:
        $error_name = 'PHP startup warning';
        $error_setting = 'traceWarnings';
        break;

      case E_COMPILE_ERROR:
        $error_name = 'Zend scripting fatal error';
        $error_setting = 'traceFatals';
        break;

      case E_COMPILE_WARNING:
        $error_name = 'Zend scripting warning';
        $error_setting = 'traceWarnings';
        break;

      case E_USER_ERROR:
        $error_name = 'User defined error';
        $error_setting = 'traceFatals';
        break;

      case E_USER_WARNING:
        $error_name = 'User defined warning';
        $error_setting = 'traceWarnings';
        break;

      case E_USER_NOTICE:
        $error_name = 'User defined notice';
        $error_setting = 'traceNotices';
        break;

      case E_STRICT:
        $error_name = 'Strict notice';
        $error_setting = 'traceNotices';
        break;

      case E_RECOVERABLE_ERROR:
        $error_name = 'Catchable fatal error';
        $error_setting = 'traceFatals';
        break;

      case E_DEPRECATED:
        $error_name = 'Deprecated warning';
        $error_setting = 'traceWarnings';
        break;

      case E_USER_DEPRECATED:
        $error_name = 'User defined deprecated warning';
        $error_setting = 'traceWarnings';
        break;

      default:
        $error_name = 'Unknown error';
        $error_setting = 'unknown';
        break;

    }
    return array($error_name, $error_setting);
  }
}
