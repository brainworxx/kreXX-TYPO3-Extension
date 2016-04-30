<?php
/**
 * @file
 *   Toolbox functions for kreXX
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

use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\SkinRender;

/**
 * This class hosts functions, which offer additional services.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Toolbox {

  /**
   * Returns the microtime timestamp for file operations.
   *
   * File operations are the logfiles and the chunk handling.
   *
   * @return string
   *   The timestamp itself.
   */
  public static function fileStamp() {
    static $timestamp = 0;
    if ($timestamp == 0) {
      $timestamp = explode(" ", microtime());
      $timestamp = $timestamp[1] . str_replace("0.", "", $timestamp[0]);
    }

    return $timestamp;
  }

  /**
   * Check if the current request is an AJAX request.
   *
   * @return bool
   *   TRUE when this is AJAX, FALSE if not
   */
  public static function isRequestAjaxOrCli() {

    if (Config::getConfigValue('output', 'destination') != 'file') {
      // When we are not going to create a logfile, we send it to the browser.
      // Check for ajax.
      if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // Appending stuff after a ajax request will most likely
        // cause a js error. But there are moments when you actually
        // want to do this.
        if (Config::getConfigValue('output', 'detectAjax') == 'true') {
          // We were supposed to detect ajax, and we did it right now.
          return TRUE;
        }
      }
      // Check for CLI.
      if (php_sapi_name() == "cli") {
        return TRUE;
      }
    }
    // Still here? This means it's neither.
    return FALSE;
  }

  /**
   * Generates a id for the DOM.
   *
   * This is used to jump from a recursion to the object analysis data.
   * The ID is the object hash as well as the kruXX call number, to avoid
   * collisions (even if they are unlikely).
   *
   * @param mixed $data
   *   The object from which we want the ID.
   *
   * @return string
   *   The generated id.
   */
  public static function generateDomIdFromObject($data) {
    if (is_object($data)) {
      return 'k' . SkinRender::$KrexxCount . '_' . spl_object_hash($data);
    }
    else {
      // Do nothing.
      return '';
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
  public static function formattedVarDump($data) {
    echo '<pre>';
    var_dump($data);
    echo('</pre>');
  }

  /**
   * Checks for a .htaccess file with a 'deny from all' statement.
   *
   * @param string $path
   *   The path we want to check.
   *
   * @return bool
   *   Whether the path is protected.
   */
  public static function isFolderProtected($path) {
    $result = FALSE;
    if (is_readable($path . '/.htaccess')) {
      $content = file($path . '/.htaccess');
      foreach ($content as $line) {
        // We have what we are looking for, a
        // 'deny from all', not to be confuse with
        // a '# deny from all'.
        if (strtolower(trim($line)) == 'deny from all') {
          $result = TRUE;
          break;
        }
      }
    }
    return $result;
  }

  /**
   * Adds source sample to a backtrace.
   *
   * @param array $backtrace
   *   The backtrace from debug_backtrace().
   *
   * @return array
   *   The backtrace with the source samples.
   */
  public static function addSourcecodeToBacktrace(array $backtrace) {
    foreach ($backtrace as &$trace) {
      // The line number is 0-based, we need to a -1.
      $source = self::readSourcecode($trace['file'], $trace['line'] - 1, 3);
      // Add it only, if we have source code. Some internal functions do not
      // provide any (call_user_func for example).
      if (strlen(trim($source)) > 0) {
        $trace['sourcecode'] = $source;
      }
      else {
        $trace['sourcecode'] = 'No sourcecode available. Maybe this was an internal callback (call_user_func for example)?';
      }
    }

    return $backtrace;
  }

  /**
   * Reads sourcecode from files, in case a fatal error occurred.
   *
   * @param string $file
   *   Path to the file you want to read.
   * @param int $line_no
   *   The line number you want to read.
   * @param int $space_line
   *   How many lines before and after the line number.
   *
   * @return string
   *   The source code.
   */
  public static function readSourcecode($file, $line_no, $space_line) {
    $result = '';
    if (is_readable($file)) {
      // Load content and add it to the backtrace.
      $content_array = file($file);
      $from = $line_no - $space_line;
      $to = $line_no + $space_line;
      // Correct the value, in case we are exceeding the line numbers.
      if ($from < 0) {
        $from = 0;
      }
      if ($to > count($content_array)) {
        $to = count($content_array);
      }

      for ($current_line_no = $from; $current_line_no <= $to; $current_line_no++) {
        if (isset($content_array[$current_line_no])) {
          // We are ignoring empty lines.
          $line = preg_replace('/\s+/', '', $content_array[$current_line_no]);
          if (strlen($line) == 0) {
            // We will need to increase the $to.
            if ($to + 1 <= count($content_array)) {
              $to++;
            }
          }
          // Add it to the result.
          $real_line_no = $current_line_no + 1;
          if ($current_line_no == $line_no) {
            $result .= SkinRender::renderBacktraceSourceLine('highlight', $real_line_no, Variables::encodeString($content_array[$current_line_no], TRUE));
          }
          else {
            $result .= SkinRender::renderBacktraceSourceLine('source', $real_line_no, Variables::encodeString($content_array[$current_line_no], TRUE));
          }
        }
        else {
          // End of the file.
          break;
        }
      }
    }
    return $result;
  }

  /**
   * Reads the content of a file.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return string
   *   The content of the file, if readable.
   */
  public static function getFileContents($path) {
    $result = '';

    // Is it readable and does it have any content?
    if (is_readable($path)) {
      $size = filesize($path);
      if ($size > 0) {
        $file = fopen($path, "r");
        $result = fread($file, $size);
        fclose($file);
      }
    }

    return $result;
  }

  /**
   * Write the content of a string to a file.
   *
   * When the file already exists, we will append the content.
   * Caches weather we are allowed to write, to reduce the overhead.
   *
   * @param string $path
   *   Path and filename.
   * @param string $string
   *   The string we want to write.
   */
  public static function putFileContents($path, $string) {
    // Do some caching, so we check a file or dir only once!
    static $ops = array();
    static $dir = array();

    // Check the directory.
    if (!isset($dir[dirname($path)])) {
      $dir[dirname($path)]['canwrite'] = is_writable(dirname($path));
    }

    if (!isset($ops[$path])) {
      // We need to do some checking:
      $ops[$path]['append'] = is_file($path);
      $ops[$path]['canwrite'] = is_writable($path);
    }

    // Do the writing!
    if ($ops[$path]['append']) {
      if ($ops[$path]['canwrite']) {
        // Old file where we are allowed to write.
        file_put_contents($path, $string, FILE_APPEND);
      }
    }
    else {
      if ($dir[dirname($path)]['canwrite']) {
        // New file we can create.
        file_put_contents($path, $string);
        // We will append it on the next write attempt!
        $ops[$path]['append'] = TRUE;
        $ops[$path]['canwrite'] = TRUE;
      }
    }
  }

  /**
   * Return the current URL.
   *
   * @see http://stackoverflow.com/questions/6768793/get-the-full-url-in-php
   * @author Timo Huovinen
   *
   * @return string
   *   The current URL.
   */
  public static function getCurrentUrl() {
    static $result;

    if (!isset($result)) {
      $s = $_SERVER;

      // SSL or no SSL.
      if (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') {
        $ssl = TRUE;
      }
      else {
        $ssl = FALSE;
      }
      $sp = strtolower($s['SERVER_PROTOCOL']);
      $protocol = substr($sp, 0, strpos($sp, '/'));
      if ($ssl) {
        $protocol .= 's';
      }

      $port = $s['SERVER_PORT'];

      if ((!$ssl && $port == '80') || ($ssl && $port == '443')) {
        // Normal combo with port and protocol.
        $port = '';
      }
      else {
        // We have a special port here.
        $port = ':' . $port;
      }

      if (isset($s['HTTP_HOST'])) {
        $host = $s['HTTP_HOST'];
      }
      else {
        $host = $s['SERVER_NAME'] . $port;
      }

      $result = htmlspecialchars($protocol . '://' . $host . $s['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
    }
    return $result;
  }
}
