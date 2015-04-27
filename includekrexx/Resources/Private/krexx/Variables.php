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

namespace Krexx;

/**
 * This class hosts the variable analysis functions.
 *
 * @package Krexx
 */
class Variables {

  /**
   * Render a 'dump' for a NULL value.
   *
   * @param string $name
   *   The Name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseNull($name, $additional = '', $connector = '=>') {
    $data = 'NULL';
    return Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'null', '', '', $connector);
  }

  /**
   * Render a dump for an array.
   *
   * @param array $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseArray(array &$data, $name, $additional = '', $connector = '=>') {
    // Dumping all Properties.
    $parameter = array($data);
    $anon_function = function ($parameter) {
      $data = $parameter[0];
      return Internals::iterateThrough($data);
    };

    return Render::renderExpandableChild($name, $additional . 'array', $anon_function, $parameter, count($data) . ' elements', '', '', FALSE, $connector);
  }

  /**
   * Analyses a resource.
   *
   * @param resource $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseResource($data, $name, $additional = '', $connector = '=>') {
    $data = get_resource_type($data);
    return Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'resource', '', '', $connector);
  }

  /**
   * Render a dump for a bool value.
   *
   * @param bool $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseBoolean($data, $name, $additional = '', $connector = '=>') {
    $data = $data ? 'TRUE' : 'FALSE';
    return Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'boolean', '', '', $connector);
  }

  /**
   * Render a dump for a integer value.
   *
   * @param int $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseInteger($data, $name, $additional = '', $connector = '=>') {
    return Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'integer', '', '', $connector);
  }

  /**
   * Render a dump for a float value.
   *
   * @param float $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseFloat($data, $name, $additional = '', $connector = '=>') {
    return Render::renderSingleChild($data, $name, $data, FALSE, $additional . 'float', '', '', $connector);
  }

  /**
   * Render a dump for a string value.
   *
   * @param string $data
   *   The data we are analysing.
   * @param string $name
   *   The name, what we render here.
   * @param string $additional
   *   Information about thedeclaration in the parent class / array.
   * @param string $connector
   *   The connector type to the parent class / array.
   *
   * @return string
   *   The rendered markup.
   */
  public Static Function analyseString($data, $name, $additional = '', $connector = '=>') {

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

    // We need to take care for mixed encodungs here.
    $strlen = @mb_strlen($data, @mb_detect_encoding($data));
    if ($strlen === FALSE) {
      // Looks like we have a mixed encoded string.
      $strlen = ' mixed encoded ~ ' . strlen($data);
    }

    return Render::renderSingleChild($clean_data, $name, $cut, $has_extra, $additional . 'string', ' ' . $strlen, '', $connector);
  }

  /**
   * Sanitizes a string, by completely encoding it.
   *
   * Should work with mixed encoding.
   *
   * @param string $data
   *   The data which nees to be sanitized.
   * @param bool $code
   *   Do we need to format the string as code?
   *
   * @return string
   *   The encoded string.
   */
  public static function encodeString($data, $code = FALSE) {
    $result = '';
    // Try to encode it.
    $encoding = mb_detect_encoding($data);
    if (in_array($encoding, get_html_translation_table(HTML_ENTITIES))) {
      set_error_handler(function() { /* do nothing. */ });
      $result = @htmlentities($data, NULL, $encoding);
      restore_error_handler();
      // We are also encodeing @, because we need them for our
      // chunks.
      $result = str_replace('@', '&#64;', $result);
    }
    // Check if encoding was successful.
    if (strlen($result) === 0 && strlen($data) !== 0) {
      // Something went wrong with the encoding, we need to
      // completely encode this one to be able to display it at all!
      $data = @mb_convert_encoding($data, 'UTF-32', mb_detect_encoding($data));
      $char_array = unpack("N*", $data);

      if ($code) {
        // We are displaying sourcecode, so we need
        // to do some formating.
        $anon_function = function($n){
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
        // No formating.
        $anon_function = function($n){
          return "&#$n;";
        };
      }
      $char_array = array_map($anon_function, $char_array);
      $result = implode("", $char_array);
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
