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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Factory;

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Closure;

/**
 * Simple factory, nothing special. Offers an overwrite method.
 */
abstract class AbstractFactory
{
    /**
     * Rewrite mapping for the getter.
     *
     * The get method will deliver these classes instead of the
     * requested classes.
     * key = original classname
     * value = the one we will deliver in that case.
     *
     * @var string[]
     */
    public array $rewrite = [];

    /**
     * @var Closure
     */
    protected Closure $errorCallback;

    /**
     * Set the error callback.
     */
    public function __construct()
    {
        $this->errorCallback = function (
            int $errno,
            string $errstr,
            ?string $errfile = null,
            ?int $errline = null,
            ?array $errcontext = null
        ): bool {
            // Do nothing.
            return true;
        };
    }

    /**
     * Create objects and returns them. Singletons are handled by the pool.
     *
     * @param string $classname
     *   Name of the class we want to create.
     *
     * @return object
     *   The requested object.
     */
    public function createClass(string $classname): object
    {
        // Check for possible overwrite.
        if (isset($this->rewrite[$classname])) {
            $classname = $this->rewrite[$classname];
        }

        return new $classname($this);
    }

    /**
     * Return a part the superglobal $GLOBALS.
     *
     * @param string|int $what
     *   The part of the globals we want to access.
     *
     * @return array
     *   The part we are requesting.
     */
    public function &getGlobals($what = ''): array
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
    public function &getServer(): array
    {
        return $_SERVER;
    }

    /**
     * Retrieve the do-nothing-error-handler callback.
     *
     * @return \Closure
     */
    public function retrieveErrorCallback(): Closure
    {
        return $this->errorCallback;
    }

    /**
     * Create the pool, but only if it is not already there.
     */
    public static function createPool(): void
    {
        if (isset(Krexx::$pool)) {
            // The pool is there, do nothing.
            return;
        }

        $rewrite = SettingsGetter::getRewriteList();

        // Create a new pool where we store all our classes.
        // We also need to check if we have an overwrite for the pool.
        if (empty($rewrite[Pool::class])) {
            Krexx::$pool = new Pool($rewrite);
        } else {
            $classname = $rewrite[Pool::class];
            Krexx::$pool = new $classname($rewrite);
        }
    }
}
