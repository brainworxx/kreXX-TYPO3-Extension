<?php
/**
 * @file
 *   Comments analysis of object methods for kreXX
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


/**
 * Class Comments
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Comments {

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
      return Comments::getParentalComment($original_comment, $parent_class, $method_name);
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
