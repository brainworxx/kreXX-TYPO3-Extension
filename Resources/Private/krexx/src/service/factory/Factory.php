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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Factory;

/**
 * Simple factory, nothing special. Offers a overwrite method.
 *
 * @package Brainworxx\Krexx\Service\Factory
 */
class Factory
{

    /**
     * Rewrite mapping for the getter.
     *
     * The get method will deliver these classes instead of the
     * requested classes.
     * key = original classname
     * value = the one we will deliver in that case.
     *
     * @var array
     */
    public static $rewrite = array();

    /**
     * Create objects and returns them. Singletons are handled by the pool.
     *
     * @param string $classname
     *
     * @return mixed
     *   The requested object.
     */
    public function createClass($classname)
    {
        // Check for possible overwrite.
        if (isset(static::$rewrite[$classname]) === true) {
            $classname = static::$rewrite[$classname];
        }

        return new $classname($this);
    }

    /**
     * Return a part the superglobal $GLOBALS.
     *
     * @param string $what
     *   The part of the globals we want to access.
     *
     * @return array
     *   The part we are requesting.
     */
    public function &getGlobals($what)
    {
        if (empty($what) === true) {
            return $GLOBALS;
        }

        return $GLOBALS[$what];
    }

    /**
     * Returns the superglobal $_SERVER.
     *
     * @return array
     *   The superglobal $_SERVER
     */
    public function &getServer()
    {
        return $_SERVER;
    }

    /**
     * Create the pool, but only if it is not alredy there.
     *
     * @internal
     */
    public static function createPool()
    {
        if (\Krexx::$pool !== null) {
            // The pool is there, do nothing.
            return;
        }

        // Create a new pool where we store all our classes.
        // We also need to check if we have an overwrite for the pool.
        if (empty(static::$rewrite['Brainworxx\\Krexx\\Service\\Factory\\Pool']) === true) {
            \Krexx::$pool = new Pool();
            return;
        }
        $classname = static::$rewrite['Brainworxx\\Krexx\\Service\\Factory\\Pool'];
        \Krexx::$pool = new $classname();
    }
}
