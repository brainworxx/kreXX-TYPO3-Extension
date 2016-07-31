<?php
/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
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

/**
 * Recursion handler.
 *
 * @package Brainworxx\Krexx\Analysis
 */
class RecursionHandler
{

    /**
     * Storage for arrays ans objects, to prevent recursions.
     *
     * Layout:
     * [0] -> array with markers
     * [1] -> object hashes
     *
     * @var array
     */
    protected $recursionHive = array();

    /**
     * The recursion marker for the hive.
     *
     * It's also used as a unique id to identify the
     * output "windows" on the frontend.
     *
     * @var string
     */
    protected $recursionMarker;

    /**
     * Register objects and arrays.
     *
     * Adds a variable to the hive of arrays and objects which
     * are tracked for whether they have recursive entries.
     *
     * @param mixed $bee
     *   Either array or object.
     *
     * @return array
     *   The object we are currently analysing, to prevent an error.
     */
    public function &addToHive(&$bee)
    {
        if (is_array($bee)) {
            // We are only tracking the $GLOBALS arrays, so we need to check this.
            // Other array recursions are handled by the nesting level.
            $recursionMarker = self::getMarker();
            if (!isset($bee[$recursionMarker])) {
                $cleanCopy = $bee;
                $bee[$recursionMarker] = 0;
                // Check if the copy has the marker.
                if (isset($cleanCopy[$recursionMarker])) {
                    // We have a byRef array, so we keep track of it.
                    $bee[$recursionMarker]++;
                    $this->recursionHive[0][] = &$bee;
                }
            }
        }

        if (is_object($bee)) {
            // We do something else for objects.
            // Setting a recursion marker inside might trigger a magical function.
            // Some Zend Framework objects throw an error, while Varien Objects
            // redirect the recursion value to an internal array.
            $objectHash = spl_object_hash($bee);
            if (!isset($this->recursionHive[1][$objectHash])) {
                $this->recursionHive[1][$objectHash] = 0;
            }
            $this->recursionHive[1][$objectHash]++;
        }

        return $bee;
    }

    /**
     * Resets all Arrays inside the recursion array.
     */
    public function __destruct()
    {
        // Remove all recursion marker inside of arrays.
        $recursionMarker = self::getMarker();
        if (!empty($this->recursionHive[0])) {
            foreach ($this->recursionHive[0] as $i => $bee) {
                if (isset($this->recursionHive[0][$i][$recursionMarker])) {
                    unset($this->recursionHive[0][$i][$recursionMarker]);
                }
            }
        }
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
    public function isInHive($bee)
    {
        // Test for references in order to
        // prevent endless recursion loops.
        $recursionValue = 0;
        $recursionMarker = self::getMarker();
        if (is_object($bee)) {
            // Retrieve a possible hash.
            $objectHash = spl_object_hash($bee);
            if (isset($this->recursionHive[1][$objectHash])) {
                $recursionValue = $this->recursionHive[1][$objectHash];
            }
        } else {
            // Retrieve a possible value.
            if (isset($bee[$recursionMarker])) {
                $recursionValue = $bee[$recursionMarker];
            } else {
                $recursionValue = 0;
            }
        }

        $recursionValue = (int)$recursionValue;
        if ($recursionValue > 0) {
            return true;
        } else {
            return false;
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
    public function getMarker()
    {
        if (!isset($this->recursionMarker)) {
            $this->recursionMarker = 'Krexx' . substr(str_shuffle(md5(microtime())), 0, 10);
        }

        return $this->recursionMarker;
    }
}
