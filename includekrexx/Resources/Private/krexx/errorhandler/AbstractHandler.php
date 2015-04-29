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

namespace Krexx\Errorhandler;

/**
 * This class hosts all functions which all error handlers will share
 * (as soon as they are wrttten . . .)
 *
 * @package Krexx
 */
abstract class AbstractHandler {

  /**
   * Decides, if the handler does anything.
   *
   * @return bool
   *   Returns TRUE when kreXX is active and this
   *   handler is active
   */
  protected function getIsActive() {
    if ($this->isActive && \Krexx\Config::isEnabled()) {
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
      \Krexx\Render::$KrexxCount++;
      \Krexx\Internals::$timer = time();

      // Setting template info.
      if (is_null(\Krexx\Render::$skin)) {
        \Krexx\Render::$skin = \Krexx\Config::getConfigValue('render', 'skin');
      }

      // Get the header.
      $header = \Krexx\Render::renderFatalHeader(
          \Krexx\Toolbox::outputCssAndJs(),
          '<!DOCTYPE html>');
      // Get the mainpart.
      $main = \Krexx\Render::renderFatalMain(
          $error_data['type'],
          $error_data['errstr'],
          $error_data['errfile'],
          $error_data['errline'] + 1,
          $error_data['source']);
      // Get the backtrace.
      $backtrace = \Krexx\Toolbox::outputBacktrace($error_data['backtrace']);
      // Get the footer.
      $footer = \Krexx\Toolbox::outputFooter('');
      // Get the messages.
      $messages = \Krexx\Messages::outputMessages();

      \Krexx\Toolbox::outputNow($header . $messages . $main . $backtrace . $footer);

      // Cleanup the hive, this removes all recursion markers.
      \Krexx\Hive::cleanupHive();
    }
  }

  /**
   * Translates the errornumber into human readable text.
   *
   * It also incudes the corresponding config
   * setting, so we can decide if we want to output
   * anything.
   *
   * @param int $error_int
   *   The errornumber.
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
        $error_name = 'Depricated warning';
        $error_setting = 'traceWarnings';
        break;

      case E_USER_DEPRECATED:
        $error_name = 'User defined depricated warning';
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
