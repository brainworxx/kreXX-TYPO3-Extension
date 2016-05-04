<?php
/**
 * @file
 *   Object method analysis functions for kreXX
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

namespace Brainworxx\Krexx\Analysis\Objects;

use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\Framework\Internals;


/**
 * This class hosts the object methods analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Methods {

  /**
   * Decides which methods we want to analyse and then starts the dump.
   *
   * @param object $data
   *   The object we want to analyse.
   *
   * @return string
   *   The generated markup.
   */
  public static function getMethodData($data) {
    // Dumping all methods but only if we have any.
    $public = array();
    $protected = array();
    $private = array();
    $ref = new \ReflectionClass($data);
    if (Config::getConfigValue('methods', 'analyseMethodsAtall') == 'true') {
      $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

      if (Config::getConfigValue('methods', 'analyseProtectedMethods') == 'true' || Internals::isInScope()) {
        $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
      }
      if (Config::getConfigValue('methods', 'analysePrivateMethods') == 'true' || Internals::isInScope()) {
        $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
      }
    }

    // Is there anything to analyse?
    $methods = array_merge($public, $protected, $private);
    if (count($methods)) {
      // We need to sort these alphabetically.
      $sorting_callback = function ($a, $b) {
        return strcmp($a->name, $b->name);
      };
      usort($methods, $sorting_callback);

      $parameter = array($ref, $methods);
      $anon_function = function (&$parameter) {
        return Methods::analyseMethods($parameter[0], $parameter[1]);
      };

      return SkinRender::renderExpandableChild('Methods', 'class internals', $anon_function, $parameter);
    }
    return '';
  }

  /**
   * Render a dump for the methods of an object.
   *
   * @param mixed $ref
   *   A reflection of the original class.
   * @param array $data
   *   An array with the reflection methods.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analyseMethods(\ReflectionClass $ref, $data) {
    $parameter = array($ref, $data);

    $analysis = function ($parameter) {
      $ref = $parameter[0];
      $data = $parameter[1];
      $result = '';

      // Deep analysis of the methods.
      foreach ($data as $reflection) {
        $method_data = array();
        /* @var \ReflectionMethod $reflection */
        $method = $reflection->name;
        // Get the comment from the class, it's parents, interfaces or traits.
        $comments = trim($reflection->getDocComment());
        if ($comments != '') {
          $method_data['comments'] = Comments::prettifyComment($comments);
          $method_data['comments'] = Comments::getParentalComment($method_data['comments'], $ref, $method);
          $method_data['comments'] = Comments::getInterfaceComment($method_data['comments'], $ref, $method);
          $method_data['comments'] = Variables::encodeString($method_data['comments'], TRUE);
        }
        // Get declaration place.
        $declaring_class = $reflection->getDeclaringClass();
        if (is_null($declaring_class->getFileName()) || $declaring_class->getFileName() == '') {
          $method_data['declared in'] = ':: unable to determine declaration ::<br/><br/>Maybe this is a predeclared class?';
        }
        else {
          $method_data['declared in'] = htmlspecialchars($declaring_class->getFileName()) . '<br/>';
          $method_data['declared in'] .= htmlspecialchars($declaring_class->getName()) . ' ';
          $method_data['declared in'] .= 'in line ' . htmlspecialchars($reflection->getStartLine());
        }

        // Get parameters.
        $parameters = $reflection->getParameters();
        foreach ($parameters as $parameter) {
          preg_match('/(.*)(?= \[ )/', $parameter, $key);
          $parameter = str_replace($key[0], '', $parameter);
          $method_data[$key[0]] = htmlspecialchars(trim($parameter, ' []'));
        }
        // Get visibility.
        $method_data['declaration keywords'] = '';
        if ($reflection->isPrivate()) {
          $method_data['declaration keywords'] .= ' private';
        }
        if ($reflection->isProtected()) {
          $method_data['declaration keywords'] .= ' protected';
        }
        if ($reflection->isPublic()) {
          $method_data['declaration keywords'] .= ' public';
        }
        if ($reflection->isStatic()) {
          $method_data['declaration keywords'] .= ' static';
        }
        if ($reflection->isFinal()) {
          $method_data['declaration keywords'] .= ' final';
        }
        if ($reflection->isAbstract()) {
          $method_data['declaration keywords'] .= ' abstract';
        }
        $method_data['declaration keywords'] = trim($method_data['declaration keywords']);
        $result .= Methods::dumpMethodInfo($method_data, $method);
      }
      return $result;

    };

    return $analysis($parameter);
  }

  /**
   * Render a dump for method info.
   *
   * @param array $data
   *   The method analysis results in an array.
   * @param string $name
   *   The name of the object.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function dumpMethodInfo(array $data, $name) {
    $parameter = array($data);
    $anon_function = function ($parameter) {
      $data = $parameter[0];
      $output = '';
      foreach ($data as $key => $string) {
        if ($key !== 'comments' && $key !== 'declared in') {
          $output .= SkinRender::renderSingleChild($string, $key, $string, 'reflection', '', '', '=');
        }
        else {
          $output .= SkinRender::renderSingleChild($string, $key, '. . .', 'reflection', '', '', '=');
        }
      }
      return $output;
    };

    $param_list = '';
    $connector1 = '->';
    foreach ($data as $key => $string) {
      // Getting the parameter list.
      if (strpos($key, 'Parameter') === 0) {
        $param_list .= trim(str_replace(array(
            '&lt;optional&gt;',
            '&lt;required&gt;',
          ), array('', ''), $string)) . ', ';
      }
      if (strpos($data['declaration keywords'], 'static') !== FALSE) {
        $connector1 = '::';
      }
    }
    // Remove the ',' after the last char.
    $param_list = '<small>' . trim($param_list, ', ') . '</small>';
    return SkinRender::renderExpandableChild($name, $data['declaration keywords'] . ' method', $anon_function, $parameter, '', '', '', FALSE, $connector1, '(' . $param_list . ')');
  }

}
