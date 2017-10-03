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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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
    protected $rewrite = array();

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
        if (isset($this->rewrite[$classname])) {
            $classname = $this->rewrite[$classname];
        }

        return new $classname($this);
    }

    /**
     * Adds another value to the overwrite.
     *
     * @param string $originalClassName
     *   The original class name, we want to overwrite this one.
     * @param string $newClassName
     *   The new class name, the factory will then return this class via get();
     *
     * @return $this
     *   Return $this, for chaining.
     */
    public function addRewrite($originalClassName, $newClassName)
    {
        $this->rewrite[$originalClassName] = $newClassName;
        return $this;
    }

    /**
     * Resets the rewrite info and reloads it from the globals.
     */
    public function flushRewrite()
    {
        $overwrites = $this->getGlobals('kreXXoverwrites');

        if (isset($overwrites) && isset($overwrites['classes'])) {
            $this->rewrite = $overwrites['classes'];
        } else {
            $this->rewrite = array();
        }
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
        if (empty($what)) {
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
}
