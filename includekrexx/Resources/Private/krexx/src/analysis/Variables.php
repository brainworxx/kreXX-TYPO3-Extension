<?php
/**
 * @file
 *   Variables analysis functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
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

namespace Brainworxx\Krexx\Analysis;

use Brainworxx\Krexx\Analysis\Objects\Objects;
use Brainworxx\Krexx\Framework\Internals;
use Brainworxx\Krexx\View\Messages;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Config;

/**
 * This class hosts the variable analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class Variables {

  /**
   * List of all charsets that can be safely encoded via htmlentities().
   *
   * @var array
   */
  static protected $charsetList = array(
    'UTF-8',
    'ISO-8859-1',
    'ISO-8859-5',
    'ISO-8859-15',
    'cp866',
    'cp1251',
    'Windows-1251',
    'cp1252',
    'Windows-1252',
    'KOI8-R',
    'koi8r',
    'BIG5',
    'GB2312',
    'Shift_JIS',
    'SJIS',
    'SJIS-win',
    'cp932',
    'EUC-JP',
    'EUCJP',
    'eucJP-win',
  );

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
    if (!Internals::checkEmergencyBreak()) {
      // No more took too long, or not enough memory is left.
      Messages::addMessage("Emergency break for large output during analysis process.");
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
      Internals::$nestingLevel++;
      if (Internals::$nestingLevel <= (int) Config::getConfigValue('deep', 'level')) {
        $result = Objects::analyseObject($data, $name, '', $connector1, $connector2);
        Internals::$nestingLevel--;
        return $result;
      }
      else {
        Internals::$nestingLevel--;
        return Variables::analyseString("Object => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Closure?
    if (is_object($data) && is_a($data, '\Closure')) {
      Internals::$nestingLevel++;
      if (Internals::$nestingLevel <= (int) Config::getConfigValue('deep', 'level')) {
        if ($connector2 == '] =') {
          $connector2 = ']';
        }
        $result = Objects::analyseClosure($data, $name, '', $connector1, $connector2);
        Internals::$nestingLevel--;
        return $result;
      }
      else {
        Internals::$nestingLevel--;
        return Variables::analyseString("Closure => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $name);
      }
    }

    // Array?
    if (is_array($data)) {
      Internals::$nestingLevel++;
      if (Internals::$nestingLevel <= (int) Config::getConfigValue('deep', 'level')) {
        $result = Variables::analyseArray($data, $name, '', $connector1, $connector2);
        Internals::$nestingLevel--;
        return $result;
      }
      else {
        Internals::$nestingLevel--;
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
        return SkinRender::renderRecursion();
      }

      // Remember, that we've already been here.
      Hive::addToHive($data);

      // Keys?
      $keys = array_keys($data);

      $output .= SkinRender::renderSingeChildHr();

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

        $output .= Variables::analysisHub($v, $k, '[', '] =');
      }
      $output .= SkinRender::renderSingeChildHr();
      return $output;
    };
    return SkinRender::renderExpandableChild('', '', $analysis, $parameter);
  }

  /**
   * Render a 'dump' for a NULL value.
   *
   * @param string $name
   *   The Name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseNull($name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'NULL';

    $data = 'NULL';
    return SkinRender::renderSingleChild($data, $name, $data, $additional . 'null', '', $connector1, $connector2, $json);
  }

  /**
   * Render a dump for an array.
   *
   * @param array $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseArray(array &$data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'array';
    $json['count'] = (string) count($data);

    // Dumping all Properties.
    $parameter = array($data);
    $anon_function = function ($parameter) {
      $data = $parameter[0];
      return Variables::iterateThrough($data);
    };

    return SkinRender::renderExpandableChild($name, $additional . 'array', $anon_function, $parameter, count($data) . ' elements', '', '', FALSE, $connector1, $connector2, $json);
  }

  /**
   * Analyses a resource.
   *
   * @param resource $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseResource($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'resource';

    $data = get_resource_type($data);
    return SkinRender::renderSingleChild($data, $name, $data, $additional . 'resource', '', $connector1, $connector2, $json);
  }

  /**
   * Render a dump for a bool value.
   *
   * @param bool $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseBoolean($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'boolean';

    $data = $data ? 'TRUE' : 'FALSE';
    return SkinRender::renderSingleChild($data, $name, $data, $additional . 'boolean', '', $connector1, $connector2, $json);
  }

  /**
   * Render a dump for a integer value.
   *
   * @param int $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseInteger($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'integer';

    return SkinRender::renderSingleChild($data, $name, $data, $additional . 'integer', '', $connector1, $connector2, $json);
  }

  /**
   * Render a dump for a float value.
   *
   * @param float $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseFloat($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'float';

    return SkinRender::renderSingleChild($data, $name, $data, $additional . 'float', '', $connector1, $connector2, $json);
  }

  /**
   * Render a dump for a string value.
   *
   * @param string $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseString($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    $json = array();
    $json['type'] = 'string';

    // Extra ?
    $cut = $data;
    if (strlen($data) > 50) {
      $cut = substr($data, 0, 50 - 3) . '...';
    }

    // Security, there could be anything inside the string.
    $clean_data = self::encodeString($data);
    $cut = self::encodeString($cut);

    $json['encoding'] = @mb_detect_encoding($data);
    // We need to take care for mixed encodings here.
    $json['length'] = (string) $strlen = @mb_strlen($data, $json['encoding']);
    if ($strlen === FALSE) {
      // Looks like we have a mixed encoded string.
      $json['length'] = '~ ' . strlen($data);
      $strlen = ' broken encoding ' . $json['length'];
      $json['encoding'] = 'broken';
    }

    return SkinRender::renderSingleChild($clean_data, $name, $cut, $additional . 'string' . ' ' . $strlen, '', $connector1, $connector2, $json);
  }

  /**
   * Sanitizes a string, by completely encoding it.
   *
   * Should work with mixed encoding.
   *
   * @param string $data
   *   The data which needs to be sanitized.
   * @param bool $code
   *   Do we need to format the string as code?
   *
   * @return string
   *   The encoded string.
   */
  public static function encodeString($data, $code = FALSE) {
    $result = '';
    // Try to encode it.
    $encoding = mb_detect_encoding($data, self::$charsetList);
    if ($encoding !== FALSE) {
      set_error_handler(function () { /* do nothing. */ });
      $result = @htmlentities($data, NULL, $encoding);
      restore_error_handler();
      // We are also encoding @, because we need them for our chunks.
      $result = str_replace('@', '&#64;', $result);
      // We ara also encoding the {, because we use it as markers for the skins.
      $result = str_replace('{', '&#123;', $result);
    }

    // Check if encoding was successful.
    if (strlen($result) === 0 && strlen($data) !== 0) {
      // Something went wrong with the encoding, we need to
      // completely encode this one to be able to display it at all!
      $data = @mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));

      if ($code) {
        // We are displaying sourcecode, so we need
        // to do some formatting.
        $anon_function = function ($n) {
          if ($n == 9) {
            // Replace TAB with two spaces, it's better readable that way.
            $result = '&nbsp;&nbsp;';
          }
          else {
            $result = "&#$n;";
          }
          return $result;
        };
      }
      else {
        // No formatting.
        $anon_function = function ($n) {
          return "&#$n;";
        };
      }

      // Here we have another SPOF. When the string is large enough
      // we will run out of memory!
      // @see https://sourceforge.net/p/krexx/bugs/21/
      // We will *NOT* return the unescaped string. So we must check if it
      // is small enough for the unpack().
      // 100 kb should be save enough.
      if (strlen($data) < 102400) {
        $result = implode("", array_map($anon_function, unpack("N*", $data)));
      }
      else {
        $result = 'This is a very large string with a none-standard encoding.' . "\n\n" . 'For security reasons, we must escape it, but it is too large for this. Sorry.';
      }
    }
    else {
      if ($code) {
        // Replace all tabs with 2 spaces to make sourcecode better
        // readable.
        $result = str_replace(chr(9), '&nbsp;&nbsp;', $result);
      }
    }

    return $result;
  }
}
