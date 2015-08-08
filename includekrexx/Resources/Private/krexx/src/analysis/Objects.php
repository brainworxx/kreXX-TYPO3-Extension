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

use Brainworxx\Krexx\Framework;
use Brainworxx\Krexx\View;

/**
 * This class hosts the object analysis functions.
 *
 * @package Krexx
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
      $output .= View\Render::renderRecursion($name, get_class($data), Framework\Toolbox::generateDomIdFromObject($data), $connector1, $connector2);

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
      $output = View\Render::renderSingeChildHr();;

      $ref = new \ReflectionClass($data);

      // Dumping public properties.
      $ref_props = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

      // Adding undeclared public properties to the dump.
      // Those are properties which are not visible with
      // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
      // but can are in get_object_vars();
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
      if (count($ref_props)) {
        $output .= Objects::getReflectionPropertiesData($ref_props, $name, $ref, $data, 'Public properties');
        // Adding a HR to reflect that the following stuff are not public
        // properties anymore.
        $output .= View\Render::renderSingeChildHr();
      }

      // Dumping protected properties.
      if (Framework\Config::getConfigValue('deep', 'analyseProtected') == 'true') {
        $ref_props = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
        if (count($ref_props)) {
          $output .= Objects::getReflectionPropertiesData($ref_props, $name, $ref, $data, 'Protected properties');
        }
      }

      // Dumping private properties.
      if (Framework\Config::getConfigValue('deep', 'analysePrivate') == 'true') {
        $ref_props = $ref->getProperties(\ReflectionProperty::IS_PRIVATE);
        if (count($ref_props)) {
          $output .= Objects::getReflectionPropertiesData($ref_props, $name, $ref, $data, 'Private properties');
        }
      }

      // Dumping all methods.
      $output .= Objects::getMethodData($data, $name);

      // Dumping traversable data.
      if (Framework\Config::getConfigValue('deep', 'analyseTraversable') == 'true') {
        $output .= Objects::getTraversableData($data, $name);
      }

      // Dumping all configured debug functions.
      $output .= Objects::pollAllConfiguredDebugMethods($data, $name);

      // Adding a HR for a better readability.
      $output .= View\Render::renderSingeChildHr();
      return $output;
    };


    // Output data from the class.
    $output .= View\Render::renderExpandableChild($name, $additional . 'class', $anon_function, $parameter, get_class($data), Framework\Toolbox::generateDomIdFromObject($data), '', FALSE, $connector1, $connector2);
    // We've finished this one, and can decrease the level setting.
    $level--;
    return $output;
  }

  /**
   * Gets the properties from a reflection property of the object.
   *
   * @param array $ref_props
   *   The list of the reflection properties.
   * @param string $name
   *   The name of the object we are analysing.
   * @param \ReflectionClass $ref
   *   The reflection of the object we are currently analysing.
   * @param object $data
   *   The object we are currently analysing.
   * @param string $label
   *   The additional part of the template file.
   *
   * @return string
   *   The generated markup.
   */
  public static function getReflectionPropertiesData(array $ref_props, $name, \ReflectionClass $ref, $data, $label) {
    // I need to preprocess them, since I do not want to render a
    // reflection property.
    $parameter = array($ref_props, $ref, $data);
    $anon_function = function (&$parameter) {
      $ref_props = $parameter[0];
      /* @var \ReflectionClass $ref */
      $ref = $parameter[1];
      $org_object = $parameter[2];
      $output = '';
      $default = $ref->getDefaultProperties();

      foreach ($ref_props as $ref_property) {
        /* @var \ReflectionProperty $ref_property */
        $ref_property->setAccessible(TRUE);

        // Getting our values from the reflection.
        $value = $ref_property->getValue($org_object);
        $prop_name = $ref_property->name;
        if (is_null($value) && $ref_property->isDefault()) {
          // We might want to look at the default value.
          $value = $default[$prop_name];
        }

        // Check memory and runtime.
        if (!Internals::checkEmergencyBreak()) {
          // No more took too long, or not enough memory is left.
          View\Messages::addMessage("Emergency break for large output during rendering process.\n\nYou should try to switch to file output.");
          return '';
        }
        // Recursion tests are done in the analyseObject and
        // iterateThrough (for arrays).
        // We will not check them here.
        // Now that we have the key and the value, we can analyse it.
        // Stitch together our additional info about the data:
        // public, protected, private, static.
        $additional = '';
        $connector1 = '->';
        if ($ref_property->isPublic()) {
          $additional .= 'public ';
        }
        if ($ref_property->isPrivate()) {
          $additional .= 'private ';
        }
        if ($ref_property->isProtected()) {
          $additional .= 'protected ';
        }
        if (is_a($ref_property, '\Brainworxx\Krexx\Analysis\Flection')) {
          /* @var \Brainworxx\Krexx\Analysis\Flection $ref_property */
          $additional .= $ref_property->getWhatAmI() . ' ';
        }
        if ($ref_property->isStatic()) {
          $additional .= 'static ';
          $connector1 = '::';
        }

        // Object?
        // Closures are analysed separately.
        if (is_object($value) && !is_a($value, '\Closure')) {
          Internals::$nestingLevel++;
          if (Internals::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
            $result = Objects::analyseObject($value, $prop_name, $additional, $connector1);
            Internals::$nestingLevel--;
            $output .= $result;
          }
          else {
            Internals::$nestingLevel--;
            $output .= Variables::analyseString("Object => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $prop_name, $additional, $connector1);
          }
        }

        // Closure?
        if (is_object($value) && is_a($value, '\Closure')) {
          Internals::$nestingLevel++;
          if (Internals::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
            $result = Objects::analyseClosure($value, $prop_name, $additional, $connector1);
            Internals::$nestingLevel--;
            $output .= $result;
          }
          else {
            Internals::$nestingLevel--;
            $output .= Variables::analyseString("Closure => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $prop_name, $additional, $connector1);
          }
        }

        // Array?
        if (is_array($value)) {
          Internals::$nestingLevel++;
          if (Internals::$nestingLevel <= (int) Framework\Config::getConfigValue('deep', 'level')) {
            $result = Variables::analyseArray($value, $prop_name, $additional, $connector1);
            Internals::$nestingLevel--;
            $output .= $result;
          }
          else {
            Internals::$nestingLevel--;
            $output .= Variables::analyseString("Array => Maximum for analysis reached. I will not go any further.\n To increase this value, change the deep => level setting.", $prop_name, $additional, $connector1);
          }
        }

        // Resource?
        if (is_resource($value)) {
          $output .= Variables::analyseResource($value, $prop_name, $additional, $connector1);
        }

        // String?
        if (is_string($value)) {
          $output .= Variables::analyseString($value, $prop_name, $additional, $connector1);
        }

        // Float?
        if (is_float($value)) {
          $output .= Variables::analyseFloat($value, $prop_name, $additional, $connector1);
        }

        // Integer?
        if (is_int($value)) {
          $output .= Variables::analyseInteger($value, $prop_name, $additional, $connector1);
        }

        // Boolean?
        if (is_bool($value)) {
          $output .= Variables::analyseBoolean($value, $prop_name, $additional, $connector1);
        }

        // Null ?
        if (is_null($value)) {
          $output .= Variables::analyseNull($prop_name, $additional, $connector1);
        }
      }

      return $output;
    };

    // We are dumping public properties direct into the main-level, without
    // any "abstraction level", because they can be accessed directly.
    if (strpos(strtoupper($label), 'PUBLIC') === FALSE) {
      // Protected or private properties.
      return View\Render::renderExpandableChild($label, 'class internals', $anon_function, $parameter, '', '', '', FALSE, '', '');
    }
    else {
      // Public properties.
      return View\Render::renderExpandableChild('', '', $anon_function, $parameter, $label);
    }
  }

  /**
   * Render a dump for the properties of an array or object.
   *
   * @param mixed &$data
   *   The object or array we want to analyse.
   *
   * @return string
   *   The generated markup.
   */
  public Static Function iterateThroughReferenceProperties(&$data) {
    $parameter = array($data);
    $analysis = function (&$parameter) {
      $output = '';
      $data = $parameter[0];
      $is_object = is_object($data);

      $recursion_marker = Hive::getMarker();

      // Recursion detection of objects are
      // handled in the hub.
      if (is_array($data) && Hive::isInHive($data)) {
        return View\Render::renderRecursion();
      }

      // Remember, that we've already been here.
      Hive::addToHive($data);

      // Keys?
      if ($is_object) {
        $keys = array_keys(get_object_vars($data));
      }
      else {
        $keys = array_keys($data);
      }

      // Iterate through.
      foreach ($keys as $k) {

        // Skip the recursion marker.
        if ($k === $recursion_marker) {
          continue;
        }

        // Get real value.
        if ($is_object) {
          $v = &$data->$k;
        }
        else {
          $v = &$data[$k];
        }

        $output .= Internals::analysisHub($v, $k);
      }
      return $output;
    };
    return View\Render::renderExpandableChild('', '', $analysis, $parameter);
  }

  /**
   * Dumps all info about the public methods of an object.
   *
   * @param object $data
   *   The object we want to analyse.
   * @param string $name
   *   The name of the object we want to analyse.
   *
   * @return string
   *   The generated markup.
   */
  public static function getMethodData($data, $name) {
    // Dumping all methods but only if we have any.
    $public = array();
    $protected = array();
    $private = array();
    $ref = new \ReflectionClass($data);
    if (Framework\Config::getConfigValue('methods', 'analysePublicMethods') == 'true') {
      $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
    }
    if (Framework\Config::getConfigValue('methods', 'analyseProtectedMethods') == 'true') {
      $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
    }
    if (Framework\Config::getConfigValue('methods', 'analysePrivateMethods') == 'true') {
      $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
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
        return Objects::analyseMethods($parameter[0], $parameter[1]);
      };

      return View\Render::renderExpandableChild('Methods', 'class internals', $anon_function, $parameter, '', '', '', FALSE, '', '');
    }
    return '';
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
        // This could be anything, we need to examine it first.
        return Internals::analysisHub($data);
      };
      return View\Render::renderExpandableChild($name, 'Foreach', $anon_function, $parameter, 'Traversable Info');
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
   * @param string $name
   *   The name of the object we are analysing.
   *
   * @return string
   *   The generated markup.
   */
  public static function pollAllConfiguredDebugMethods($data, $name) {
    $output = '';

    $func_list = explode(',', Framework\Config::getConfigValue('deep', 'debugMethods'));
    foreach ($func_list as $func_name) {
      if (is_callable(array(
          $data,
          $func_name
        )) && Framework\config::isAllowedDebugCall($data, $func_name)
      ) {
        // Add a try to prevent the hosting CMS from doing something stupid.
        try {
          // We need to deactivate the current error handling to
          // prevent the host system to do anything stupid.
          set_error_handler(function () {
            // Do nothing.
          });
          $parameter = $data->$func_name();
          // Reactivate whatever error handling we had previously.
          restore_error_handler();
        }
        catch (\Exception $e) {
          // Do nothing.
        }
        if (isset($parameter)) {
          $anon_function = function (&$parameter) {
            return Internals::analysisHub($parameter);
          };
          $output .= View\Render::renderExpandableChild($func_name, 'debug method', $anon_function, $parameter, '. . .', '', '', FALSE, '->', '() =');
          unset($parameter);
        }
      }
    }
    return $output;
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
  public Static Function analyseMethod(array $data, $name) {
    $parameter = array($data);
    $anon_function = function ($parameter) {
      $data = $parameter[0];
      $output = '';
      foreach ($data as $key => $string) {
        if ($key !== 'comments' && $key !== 'declared in') {
          $output .= View\Render::renderSingleChild($string, $key, $string, FALSE, 'reflection', '', '', '', '=');
        }
        else {
          $output .= View\Render::renderSingleChild($string, $key, '. . .', TRUE, 'reflection', '', '', '', '=');
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
            '&lt;required&gt;'
          ), array('', ''), $string)) . ', ';
      }
      if (strpos($data['declaration keywords'], 'static') !== FALSE) {
        $connector1 = '::';
      }
    }
    // Remove the ',' after the last char.
    $param_list = '<small>' . trim($param_list, ', ') . '</small>';

    return View\Render::renderExpandableChild($name, $data['declaration keywords'] . ' method', $anon_function, $parameter, '', '', '', FALSE, $connector1, '(' . $param_list . ')');
  }

  /**
   * Analyses a closure.
   *
   * @param $data
   *   The closure we want to analyse
   *
   * @return string
   *   The generated markup.
   */
  public Static Function analyseClosure($data, $prop_name = 'closure', $additional, $connector1) {
    $ref = new \ReflectionFunction($data);

    $result = array();

    // Adding comments from the file.
    $result['comments'] = Variables::encodeString(Objects::prettifyComment($ref->getDocComment()), TRUE);
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
          $output .= View\Render::renderSingleChild($string, $key, $string, FALSE, 'reflection', '', '', '', '=');
        }
        else {
          $output .= View\Render::renderSingleChild($string, $key, '. . .', TRUE, 'reflection', '', '', '', '=');
        }
      }
      return $output;
    };

    return View\Render::renderExpandableChild($prop_name, $additional . ' closure', $anon_function, $result, '', '', '', FALSE, $connector1, '(' . $param_list . ') =');

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
          $method_data['comments'] = Objects::prettifyComment($comments);
          $method_data['comments'] = Objects::getParentalComment($method_data['comments'], $ref, $method);
          $method_data['comments'] = Objects::getInterfaceComment($method_data['comments'], $ref, $method);
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
        $result .= Objects::analyseMethod($method_data, $method);
      }
      return $result;

    };

    return $analysis($parameter);
  }

  /**
   * Gets comments from the reflection.
   *
   * Inherited comments are resolved by recursion of this function.
   *
   * @param string $original_comment
   *   The original comment, so far. We use this function recursively,
   *   new comments are added until all of them are resolved.
   * @param \ReflectionClass $reflection
   *   The reflection class of the object we want to analyse.
   * @param string $method_name
   *   The name of the method from which we ant to get the comment.
   *
   * @return string
   *   The generated markup.
   */
  public static function getParentalComment($original_comment, \ReflectionClass $reflection, $method_name) {
    if (stripos($original_comment, '{@inheritdoc}') !== FALSE) {
      // now we need to get the parentclass and the comment
      // from the parent function
      /* @var \ReflectionClass $parent_class */
      $parent_class = $reflection->getParentClass();
      if (!is_object($parent_class)) {
        // we've gone too far
        // maybe a trait?
        return self::getTraitComment($original_comment, $reflection, $method_name);
      }

      try {
        $parent_method = $parent_class->getMethod($method_name);
        $parentcomment = self::prettifyComment($parent_method->getDocComment());
      }
      catch (\ReflectionException $e) {
        // Looks like we are trying to inherit from a not existing method
        // maybe a trait?
        return self::getTraitComment($original_comment, $reflection, $method_name);
      }
      // Replace it.
      $original_comment = str_ireplace('{@inheritdoc}', $parentcomment, $original_comment);
      // die(get_class($original_comment));
      // and search for further parental comments . . .
      return Objects::getParentalComment($original_comment, $parent_class, $method_name);
    }
    else {
      // We don't need to do anything with it.
      return $original_comment;
    }
  }

  /**
   * Gets the comment from all implemented interfaces.
   *
   * Iterated through an array of interfaces, to see
   * if we can resolve the inherited comment.
   *
   * @param string $original_comment
   *   The original comment, so far.
   * @param \ReflectionClass $reflection
   *   A reflection of the object we are currently analysing.
   * @param string $method_name
   *   The name of the method from which we ant to get the comment.
   *
   * @return string
   *   The generated markup.
   */
  public static function getInterfaceComment($original_comment, \ReflectionClass $reflection, $method_name) {
    if (stripos($original_comment, '{@inheritdoc}') !== FALSE) {
      $interface_array = $reflection->getInterfaces();
      foreach ($interface_array as $interface) {
        if (stripos($original_comment, '{@inheritdoc}') !== FALSE) {
          try {
            $interface_method = $interface->getMethod($method_name);
            if (!is_object($interface_method)) {
              // We've gone too far.
              // We should tell the user, that we could not resolve
              // the inherited comment.
              $original_comment = str_ireplace('{@inheritdoc}', ' ***could not resolve inherited comment*** ', $original_comment);
            }
            else {
              $interfacecomment = self::prettifyComment($interface_method->getDocComment());
              // Replace it.
              $original_comment = str_ireplace('{@inheritdoc}', $interfacecomment, $original_comment);
            }
          }
          catch (\ReflectionException $e) {
            // Method not found.
            // We should try the next interface.
          }
        }
        else {
          // Looks like we've resolved them all.
          return $original_comment;
        }
      }
      // We are still here ?!? Return the original comment.
      return $original_comment;
    }
    else {
      return $original_comment;
    }
  }

  /**
   * Gets the comment from all added traits.
   *
   * Iterated through an array of traits, to see
   * if we can resolve the inherited comment. Traits
   * are only supported since PHP 5.4, so we need to
   * check if they are available.
   *
   * @param string $original_comment
   *   The original comment, so far.
   * @param \ReflectionClass $reflection
   *   A reflection of the object we are currently analysing.
   * @param string $method_name
   *   The name of the method from which we ant to get the comment.
   *
   * @return string
   *   The generated markup.
   */
  public static function getTraitComment($original_comment, \ReflectionClass $reflection, $method_name) {
    if (stripos($original_comment, '{@inheritdoc}') !== FALSE) {
      // We need to check if we can get traits here.
      if (method_exists($reflection, 'getTraits')) {
        // Get the traits from this class.
        $trait_array = $reflection->getTraits();
        // Get the traits from the parent traits.
        foreach ($trait_array as $trait) {
          $parent_traits = $trait->getTraits();
          // Merge them into our trait array to get al parents.
          $trait_array = array_merge($trait_array, $parent_traits);
        }
        // Now we should have an array with reflections of all
        // traits in the class we are currently looking at.
        foreach ($trait_array as $trait) {
          try {
            $trait_method = $trait->getMethod($method_name);
            if (!is_object($trait_method)) {
              // We've gone too far.
              // We should tell the user, that we could not resolve
              // the inherited comment.
              $original_comment = str_ireplace('{@inheritdoc}', ' ***could not resolve inherited comment*** ', $original_comment);
            }
            else {
              $trait_comment = self::prettifyComment($trait_method->getDocComment());
              // Replace it.
              $original_comment = str_ireplace('{@inheritdoc}', $trait_comment, $original_comment);
            }
          }
          catch (\ReflectionException $e) {
            // Method not found.
            // We should try the next trait.
          }
        }
        // Return what we could resolve so far.
        return $original_comment;
      }
      else {
        // Wrong PHP version. Traits are not available.
        // Maybe there is something in the interface?
        return $original_comment;
      }
    }
    else {
      return $original_comment;
    }
  }

  /**
   * Removes the comment-chars from the comment string.
   *
   * @param string $comment
   *   The original comment from the reflection
   *   (or interface) in case if an inheritated comment.
   *
   * @return string
   *   The better readable comment
   */
  public static function prettifyComment($comment) {
    // We split our comment into single lines and remove the unwanted
    // comment chars with the array_map callback.
    $comment_array = explode("\n", $comment);
    $result = array();
    foreach ($comment_array as $comment_line) {
      // We skip lines with /** and */
      if ((strpos($comment_line, '/**') === FALSE) && (strpos($comment_line, '*/') === FALSE)) {
        // Remove comment-chars, but we need to leave the whitepace intact.
        $comment_line = trim($comment_line);
        if (strpos($comment_line, '*') === 0) {
          // Remove the * by char position.
          $result[] = substr($comment_line, 1);
        }
        else {
          // We are missing the *, so we just add the line.
          $result[] = $comment_line;
        }

      }
    }

    return implode(PHP_EOL, $result);
  }
}
