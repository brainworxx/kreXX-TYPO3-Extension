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

use Brainworxx\Krexx\View;

/**
 * This class hosts the variable analysis functions.
 *
 * @package Krexx
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
    $data = 'NULL';
    return View\Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'null', '', '', $connector1, $connector2);
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
    // Dumping all Properties.
    $parameter = array($data);
    $anon_function = function ($parameter) {
      $data = $parameter[0];
      return Internals::iterateThrough($data);
    };

    return View\Render::renderExpandableChild($name, $additional . 'array', $anon_function, $parameter, count($data) . ' elements', '', '', FALSE, $connector1, $connector2);
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
    $data = get_resource_type($data);
    return View\Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'resource', '', '', $connector1, $connector2);
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
    $data = $data ? 'TRUE' : 'FALSE';
    return View\Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'boolean', '', '', $connector1, $connector2);
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
    return View\Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'integer', '', '', $connector1, $connector2);
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
    return View\Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'float', '', '', $connector1, $connector2);
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

    // Extra ?
    $has_extra = FALSE;
    $cut = $data;
    if (strlen($data) > 50) {
      $cut = substr($data, 0, 50 - 3) . '...';
      $has_extra = TRUE;
    }

    // Security, there could be anything inside the string.
    $clean_data = self::encodeString($data);
    $cut = self::encodeString($cut);

    // We need to take care for mixed encodings here.
    $strlen = @mb_strlen($data, @mb_detect_encoding($data));
    if ($strlen === FALSE) {
      // Looks like we have a mixed encoded string.
      $strlen = ' mixed encoded ~ ' . strlen($data);
    }

    return View\Render::renderSingleChild($clean_data, $name, $cut, $has_extra, $additional . 'string', ' ' . $strlen, '', $connector1, $connector2);
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
      // We will *NOT* return the unescaped string. Se we must check if it
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
