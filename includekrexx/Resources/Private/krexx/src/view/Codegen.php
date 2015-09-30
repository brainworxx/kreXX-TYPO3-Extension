<?php
/**
 * @file
 *   Render functions for kreXX
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

namespace Brainworxx\Krexx\View;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\Framework\Toolbox;


/**
 * This class hosts the internal rendering functions.
 *
 * @package Krexx
 */
class Codegen {

  protected static $counter = 0;

  /**
   * Generates PHP sourcecode.
   *
   * From the 2 connectors and from the name of name/key of the attribute
   * we can generate PHP code to actually reach the corresponding value.
   * This function generates this code.
   *
   * @param string $connector1
   *   The first PHP connector to the value.
   * @param string $connector2
   *   The second PHP connector to the value.
   * @param string $type
   *   The type we are handling (static protected property, for example).
   * @param string $name
   *   The name of the property or method.
   *
   * @return string
   *   The generated PHP source.
   */
  public static function generateSource($connector1, $connector2, $type, $name) {

    $result = '';
    // We will not generate anything for function analytic data
    $connector2 = trim($connector2, ' = ');

    // We handle the first one special
    if (self::$counter == 0) {
      $connector1 = '$result = ';
      $connector2 = '';
    }

    if ($connector1 . $connector2 == '') {
      // No connectors mean, we are dealing with some meta stuff, like functions
      // We will not add anything for them.
    }
    else {
      // Simply fuse the connectors.
      // The connectors are a representation of the current used "language".

      switch (self::analyseType($type)) {

        case 'contagination':
          // We simply add the connectors for public access.
          $result = $connector1 . $name . $connector2;
          break;

        case 'method':
          // We create a reflection method and then call it.
          $result = self::reflectFunction($name);
          break;

        case 'property':
          // We create a reflection property an set it to public to access it.
          $result = self::reflectProperty($name);
          break;
      }
    }

    // We can not simply put anything inside the data element. We need to do
    // some escaping!
    $result = Variables::encodeString($result);

    self::$counter++;
    return $result;
  }

  protected static function reflectProperty($name) {
    // We stop the current codeline here.
    $result = ";<br />";
    // The genereted code at thsi point should look something like this:
    // $result = $myClass->value1->value2;
    // This means, that we have the protected/private property inside the
    // $result object.
    $result .= '// We are creating a reflection to make this property accessible<br />';
    $result .= '// Please note, that this is *NOT* a proper way to code.<br />';
    $result .= '// There is a reason, why this value is protected.<br />';
    $result .= '$reflection = new \reflectionClass($result);<br />';
    $result .= '$property = $reflection->getProperty(' . $name . ');<br />';
    $result .= '$property->setAccessible(true);<br />';
    $result .= '$result = $property->getValue($result)';
    return $result;
  }


  protected static function reflectFunction($name) {
    return '@todo: Write me!';
  }

  /**
   * Analyses the type and then decides what to do with it
   *
   * @param string $type
   *
   * @return string
   *   Possible values:
   *   - contagination
   *   - method
   *   - property
   */
  protected static function analyseType($type) {
    $contagination = 'contagination';
    $method = 'method';
    $property = 'property';

    // Debug methods are always public.
    if ($type == 'debug method' || self::$counter == 0) {
      return $contagination;
    }

    // Test for protected or private
    if (strpos($type, 'protected') === FALSE && strpos($type, 'private') === FALSE) {
      // Is not protected.
      return $contagination;
    }


    // We are still here? Must be a protected method or property.
    if (strpos($type, 'method') === FALSE) {
      // this is not a method
      return $property;
    }
    else {
      return $method;
    }

    // Still here ?!? Fallback to contagination.
    return $contagination;
  }

  /**
   * Here we reset the counter
   *
   * We are handling the first time a little bit different. If not we would
   * get something like '$myClass->value->anotherValue'
   *
   * When we start the run, we need to generate something like:
   * $result = $myClass->value
   */
  public static function resetCounter() {
    self::$counter = 0;
  }
}