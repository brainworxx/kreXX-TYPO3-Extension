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
 *   kreXX Copyright (C) 2014-2024 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Tests\Fixtures;

/**
 * A fixture class to test configured debug methods.
 *
 * @package Brainworxx\Krexx\Tests\Fixtures
 */
class DebugMethodFixture
{
    public $callWithParameter = false;

    public $callMagicMethod = [];

    /**
     * Simple return some string.
     *
     * @return string
     */
    public function goodDebugMethod()
    {
        return __FUNCTION__;
    }

    /**
     * Throw a catchable.
     *
     * @throws \Exception
     *   We expect our debugger to catch this one.
     */
    public function badDebugMethod()
    {
        throw new TestException('some message', 123);
    }

    /**
     * Ugly debug method, triggering a warning.
     */
    public function uglyDebugMethod()
    {
        trigger_error('some message', E_USER_WARNING);

        return __FUNCTION__;
    }

    /**
     * Must not be called, because we can not provide a value for it.
     *
     * @param $variable
     */
    public function parameterizedDebugMethod($variable)
    {
        // Do something with the $variable to prevent a code smell.
        trigger_error($variable, E_WARNING);

        $this->callWithParameter = true;
    }

    /**
     * Add some magic to the mix.
     *
     * @param $name
     * @param $arguments
     *
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        // Do something with the $arguments to prevent a code smell.
        trigger_error($arguments, E_WARNING);

        $this->callMagicMethod[] = '__call was called for ' . $name;
    }
}
