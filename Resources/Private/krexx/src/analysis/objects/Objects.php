<?php
/**
 * @file
 *   Object analysis functions for kreXX
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

use Brainworxx\Krexx\Framework\Internals;
use Brainworxx\Krexx\Analysis\Hive;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Framework\Toolbox;


/**
 * This class hosts the object analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Objects {

  /**
   * Render a dump for an object.
   *
   * @param mixed $data
   *   The object we want to analyse.
   * @param string $name
   *   The name of the object.
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analyseObject($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=') {
    static $level = 0;

    $output = '';
    $parameter = array($data, $name);
    $level++;

    if (Hive::isInHive($data)) {
      // Tell them, we've been here before
      // but also say who we are.
      $output .= SkinRender::renderRecursion($name, $additional . 'class', get_class($data), Toolbox::generateDomIdFromObject($data), $connector1, $connector2);

      // We will not render this one, but since we
      // return to wherever we came from, we need to decrease the level.
      $level--;
      return $output;
    }
    // Remember, that we've been here before.
    Hive::addToHive($data);

    $anon_function = function (&$parameter) {
      $data = $parameter[0];
      $name = $parameter[1];
      $output = SkinRender::renderSingeChildHr();;

      $ref = new \ReflectionClass($data);

      // Dumping public properties.
      $ref_props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

      // Adding undeclared public properties to the dump.
      // Those are properties which are not visible with
      // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
      // but are in get_object_vars();
      // 1. Make a list of all properties
      // 2. Remove those that are listed in
      // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
      // What is left are those special properties that were dynamically
      // set during runtime, but were not declared in the class.
      foreach ($ref_props as $ref_prop) {
        $public_props[$ref_prop->name] = $ref_prop->name;
      }
      foreach (get_object_vars($data) as $key => $value) {
        if (!isset($public_props[$key])) {
          $ref_props[] = new Flection($value, $key);
        }
      }

      // We will dump the properties alphabetically sorted, via this callback.
      $sorting_callback = function ($a, $b) {
        return strcmp($a->name, $b->name);
      };

      if (count($ref_props)) {
        usort($ref_props, $sorting_callback);
        $output .= Properties::getReflectionPropertiesData($ref_props, $ref, $data, 'Public properties');
        // Adding a HR to reflect that the following stuff are not public
        // properties anymore.
        $output .= SkinRender::renderSingeChildHr();
      }

      // Dumping protected properties.
      if (Config::getConfigValue('deep', 'analyseProtected') == 'true' || Internals::isInScope()) {
        $ref_props = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
        usort($ref_props, $sorting_callback);

        if (count($ref_props)) {
          $output .= Properties::getReflectionPropertiesData($ref_props, $ref, $data, 'Protected properties');
        }
      }

      // Dumping private properties.
      if (Config::getConfigValue('deep', 'analysePrivate') == 'true' || Internals::isInScope()) {
        $ref_props = $ref->getProperties(\ReflectionProperty::IS_PRIVATE);
        usort($ref_props, $sorting_callback);
        if (count($ref_props)) {
          $output .= Properties::getReflectionPropertiesData($ref_props, $ref, $data, 'Private properties');
        }
      }

      // Dumping class constants.
      if (Config::getConfigValue('deep', 'analyseConstants') == 'true') {
        $output .= Properties::getReflectionConstantsData($ref);
      }

      // Dumping all methods.
      $output .= Methods::getMethodData($data);

      // Dumping traversable data.
      if (Config::getConfigValue('deep', 'analyseTraversable') == 'true') {
        $output .= Objects::getTraversableData($data, $name);
      }

      // Dumping all configured debug functions.
      $output .= Objects::pollAllConfiguredDebugMethods($data);

      // Adding a HR for a better readability.
      $output .= SkinRender::renderSingeChildHr();
      return $output;
    };


    // Output data from the class.
    $output .= SkinRender::renderExpandableChild($name, $additional . 'class', $anon_function, $parameter, get_class($data), Toolbox::generateDomIdFromObject($data), '', FALSE, $connector1, $connector2);
    // We've finished this one, and can decrease the level setting.
    $level--;
    return $output;
  }

  /**
   * Dumps all available traversable data.
   *
   * @param object $data
   *   The object we are analysing.
   * @param string $name
   *   The name of the object we want to analyse.
   *
   * @return string
   *   The generated markup.
   */
  public static function getTraversableData($data, $name) {
    if (is_a($data, 'Traversable')) {
      $parameter = iterator_to_array($data);
      $anon_function = function (&$data) {
        // This should be an array. Giving it directly to the analysis hub would
        // create another useless nest.
        return Variables::iterateThrough($data);
      };
      // If we are facing a IteratorAggregate, we can not access the array
      // directly. To do this, we must get the Iterator from the class.
      // For our analysis is it not really important, because it does not
      // change anything. We need this for the automatic code generation.
      if (is_a($data, 'IteratorAggregate')) {
        $connector2 = '->getIterator()';
        // Remove the name, because this would then get added to the source
        // generation, resulting in unusable code.
        $name = '';
      }
      else {
        $connector2 = '';
      }
      return SkinRender::renderExpandableChild($name, 'Foreach', $anon_function, $parameter, 'Traversable Info', '', '', FALSE, '', $connector2);
    }
    return '';
  }

  /**
   * Calls all configured debug methods in die class.
   *
   * I've added a try and an empty error function callback
   * to catch possible problems with this. This will,
   * of cause, not stop a possible fatal in the function
   * itself.
   *
   * @param object $data
   *   The object we are analysing.
   *
   * @return string
   *   The generated markup.
   */
  public static function pollAllConfiguredDebugMethods($data) {
    $output = '';

    $func_list = explode(',', Config::getConfigValue('deep', 'debugMethods'));
    foreach ($func_list as $func_name) {
      if (is_callable(array(
          $data,
          $func_name,
        )) && Config::isAllowedDebugCall($data, $func_name)
      ) {
        $found_required = FALSE;
        // We need to check if this method actually exists. Just because it is
        // callable does not mean it exists!
        if (method_exists($data, $func_name)) {
          // We need to check if the callable function requires any parameters.
          // We will not call those, because we simply can not provide them.
          // Interestingly, some methods of a class are callable, but are not
          // implemented. This means, that when I try to get a reflection,
          // it will result in a WSOD.
          $ref = new \ReflectionMethod($data, $func_name);
          $params = $ref->getParameters();
          foreach ($params as $param) {
            if (!$param->isOptional()) {
              // We've got a required parameter!
              // We will not call this one.
              $found_required = TRUE;
            }
          }
          unset($ref);
        }
        else {
          // It's callable, but does not exist. Looks like a __call fallback.
          // We will not poll it for data.
          $found_required = TRUE;
        }

        if ($found_required == FALSE) {
          // Add a try to prevent the hosting CMS from doing something stupid.
          try {
            // We need to deactivate the current error handling to
            // prevent the host system to do anything stupid.
            set_error_handler(function () {
              // Do nothing.
            });
            $result = $data->$func_name();
            // Reactivate whatever error handling we had previously.
            restore_error_handler();
          }
          catch (\Exception $e) {
            // Do nothing.
          }
          if (isset($result)) {
            $anon_function = function (&$result) {
              return Variables::analysisHub($result);
            };
            $output .= SkinRender::renderExpandableChild($func_name, 'debug method', $anon_function, $result, '. . .', '', $func_name, FALSE, '->', '() =');
            unset($result);
          }
        }
      }
    }
    return $output;
  }

  /**
   * Analyses a closure.
   *
   * @param object $data
   *   The closure we want to analyse.
   * @param string $prop_name
   *   The property name
   * @param string $additional
   *   Information about the declaration in the parent class / array.
   * @param string $connector1
   *   The connector1 type to the parent class / array.
   * @param string $connector2
   *   The connector2 type to the parent class / array.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analyseClosure($data, $prop_name = 'closure', $additional = '', $connector1 = '', $connector2 = '') {
    $ref = new \ReflectionFunction($data);

    $result = array();

    // Adding comments from the file.
    $result['comments'] = Variables::encodeString(Comments::prettifyComment($ref->getDocComment()), TRUE);
    // Adding the place where it was declared.
    $result['declared in'] = htmlspecialchars($ref->getFileName()) . '<br/>';
    $result['declared in'] .= 'in line ' . htmlspecialchars($ref->getStartLine());
    // Adding the namespace, but only if we have one.
    $namespace = $ref->getNamespaceName();
    if (strlen($namespace) > 0) {
      $result['namespace'] = $namespace;
    }
    // Adding the parameters.
    $parameters = $ref->getParameters();
    $param_list = '';
    foreach ($parameters as $parameter) {
      preg_match('/(.*)(?= \[ )/', $parameter, $key);
      $parameter = str_replace($key[0], '', $parameter);
      $result[$key[0]] = htmlspecialchars(trim($parameter, ' []'));
      $param_list .= trim(str_replace(array('&lt;optional&gt;', '&lt;required&gt;'), array('', ''), $result[$key[0]]))  . ', ';
    }
    // Remove the ',' after the last char.
    $param_list = '<small>' . trim($param_list, ', ') . '</small>';

    $anon_function = function ($parameter) {
      $data = $parameter;
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

    return SkinRender::renderExpandableChild($prop_name, $additional . ' closure', $anon_function, $result, '', '', '', FALSE, $connector1, $connector2 . '(' . $param_list . ') =');

  }
}
