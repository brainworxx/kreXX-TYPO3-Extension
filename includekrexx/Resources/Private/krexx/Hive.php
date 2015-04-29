<?php
/**
 * @file
 *   Recursion handler for kreXX
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

namespace Krexx;

/**
 * This class decides about recursion.
 *
 * @package Krexx
 */
class Hive {
  /**
   * Storeage for arrays ans objects, to prevent recursions.
   *
   * Layout:
   * [0] -> array with markers
   * [1] -> object hashes
   *
   * @var array
   */
  protected static $recursionHive = array();

  /**
   * The recursion marker for the hive.
   *
   * It's also used as a unique id to identify the
   * output "windows" on the frontend.
   *
   * @var string
   */
  protected static $recursionMarker;

  /**
   * Register objects and arrays.
   *
   * Adds a variable to the hive of arrays and objects which
   * are tracked for whether they have recursive entries.
   *
   * @param array|object $bee
   *   Either array or object.
   *
   * @return array
   *   The object we are currently analysing, to prevent an error.
   */
  public Static Function &addToHive(&$bee) {

    // New bee ?
    if (is_array($bee) || is_object($bee)) {

      if (is_object($bee)) {
        // We do something else for objects.
        // Setting a recursion marker inside might trigger a magical function.
        // Some Zend Framework objects throw an error, while Varien Objects
        // redirect the recursion value to an internal array.
        $object_hash = spl_object_hash($bee);
        if (!isset(self::$recursionHive[1][$object_hash])) {
          self::$recursionHive[1][$object_hash] = 0;
        }
        self::$recursionHive[1][$object_hash]++;
      }
      else {
        // This should be an array.
        // We are only tracking the $GLOBALS arrays, so we need to check this.
        // Other array recursions are handled by the nesting level.
        $recursion_marker = self::getMarker();
        if (!isset($bee[$recursion_marker])) {
          $clean_copy = $bee;
          $bee[$recursion_marker] = 0;
          // Check if the copy has the marker.
          if (isset($clean_copy[$recursion_marker])) {
            // We have a byRef array, so we keep track of it.
            $bee[$recursion_marker]++;
            self::$recursionHive[0][] = & $bee;
          }
        }
      }
    }
    return $bee;
  }

  /**
   * Resets all Arrays inside the recursion array.
   */
  public static function cleanupHive() {
    // Remove all recursion marker inside of arrays.
    $recursion_marker = self::getMarker();
    if (isset(self::$recursionHive[0]) && count(self::$recursionHive[0])) {
      foreach (self::$recursionHive[0] as $i => $bee) {
        try {
          unset(self::$recursionHive[0][$i][$recursion_marker]);
        }
        catch (Exception $e) {
          // Do nothing.
        }
      }
    }
    // Reset the array, to get it clean for the next run.
    self::$recursionHive = array();
    // Reset the recursion marker, to get a new ID.
    self::$recursionMarker = NULL;
  }

  /**
   * Find out if our bee is already in the hive.
   *
   * @param mixed $bee
   *   The object or array we want to check for recursion.
   *
   * @return bool
   *   Boolean which shows whether we are facing a recursion.
   */
  public static function isInHive($bee) {
    // Test for references in order to
    // prevent endless recursion loops.
    $recursion_value = 0;
    $recursion_marker = self::getMarker();
    if (is_object($bee)) {
      // Retrieve a possible hash.
      $object_hash = spl_object_hash($bee);
      if (isset(self::$recursionHive[1][$object_hash])) {
        $recursion_value = self::$recursionHive[1][$object_hash];
      }
    }
    else {
      // Retrieve a possible value.
      if (isset($bee[$recursion_marker])) {
        $recursion_value = $bee[$recursion_marker];
      }
      else {
        $recursion_value = 0;
      }
    }

    $recursion_value = (int) $recursion_value;
    if ($recursion_value > 0) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Returns the recursion marker.
   *
   * The recursion marker is used to mark arrays as
   * already iterated, to prevent recursions.
   *
   * @return string
   *   The marker
   */
  public Static Function getMarker() {
    if (!isset(self::$recursionMarker)) {
      self::$recursionMarker = 'Krexx' . substr(str_shuffle(md5(microtime())), 0, 10);
    }

    return self::$recursionMarker;
  }
}
