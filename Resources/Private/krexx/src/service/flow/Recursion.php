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

namespace Brainworxx\Krexx\Service\Flow;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Recursion handler, formerly known as Hive.
 *
 * We are tracking objects via object hash.
 * Arrays are stored here only for the sake of
 * the $GLOBALS array.
 *
 *
 * @package Brainworxx\Krexx\Service\Flow
 */
class Recursion
{

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * pool for arrays ans objects, to prevent recursions.
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
     * Generate the recursion marker during class construction.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->recursionMarker = 'Krexx' . substr(str_shuffle(md5(microtime())), 0, 10);
        $this->pool = $pool;
    }

    /**
     * Resets all Arrays inside the recursion array.
     */
    public function __destruct()
    {
        // Remove all recursion marker inside of arrays.
        // Should only be the $GLOBALS array.
        if (!empty($this->recursionHive[0])) {
            foreach ($this->recursionHive[0] as $i => $bee) {
                if (isset($this->recursionHive[0][$i][$this->recursionMarker])) {
                    unset($this->recursionHive[0][$i][$this->recursionMarker]);
                }
            }
        }
    }

    /**
     * Register objects and arrays.
     *
     * Adds a variable to the hive of arrays and objects which
     * are tracked for whether they have recursive entries.
     *
     * @param mixed $bee
     *   Either array or object.
     */
    public function addToHive(&$bee)
    {
        if (is_array($bee)) {
            // We are only tracking the $GLOBALS arrays, so we need to check this.
            if (!isset($bee[$this->recursionMarker])) {
                $cleanCopy = $bee;
                $bee[$this->recursionMarker] = 1;
                // Check if the copy has the marker.
                if (isset($cleanCopy[$this->recursionMarker])) {
                    // We have a byRef array, so we keep track of it.
                    $this->recursionHive[0][] = &$bee;
                }
            }
        }
        if (is_object($bee)) {
            // We do something else for objects.
            // Setting a recursion marker inside might trigger a magical function.

            $objectHash = spl_object_hash($bee);
            if (!isset($this->recursionHive[1][$objectHash])) {
                $this->recursionHive[1][$objectHash] = 1;
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
        // Check objects.
        if (is_object($bee)) {
            // Retrieve a possible hash.
            $objectHash = spl_object_hash($bee);
            if (isset($this->recursionHive[1][$objectHash])) {
                return true;
            }
        }

        // Check arrays (only the $GLOBAL array may apply).
        if (is_array($bee)) {
            // Retrieve a possible value.
            if (isset($bee[$this->recursionMarker])) {
                return true;
            }
        }
        // Still here? This is either not a recursion, or it is
        // something we do not track.
        return false;
    }

    /**
     * Returns the recursion marker.
     *
     * The recursion marker is used to mark arrays as
     * already iterated, to prevent recursions.
     *
     * @return string
     *   The marker.
     */
    public function getMarker()
    {
        return $this->recursionMarker;
    }
}
