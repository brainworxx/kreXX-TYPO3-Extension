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

namespace Brainworxx\Krexx\Analyse\Caller;

use Brainworxx\Krexx\Service\Factory\Pool;

abstract class AbstractCaller
{
    /**
     * Our pool where we keep al relevant classes.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Pattern that we use to identify the caller.
     * This is normally 'krexx'. With our direct integration
     * into the debug() method of the TYPO3 core, this may as
     * well be 'debug' or something else entirely, depending
     * on the system used and it's internal debug call.
     *
     * @var string
     */
    protected $pattern = 'krexx';

    /**
     * Injects the pool.
     *
     * @param Pool $pool
     *   The pool, where we store the classes we need.
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Setter for the identifier pattern.
     *
     * @param $pattern
     *   The pattern, duh!
     *
     * @return $this
     *   Return this for chaining.
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Getter for the current recognition pattern.
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * We will remove the $_SERVER['DOCUMENT_ROOT'] from the absolute
     * path of the calling file.
     * Return the original path, in case we can not determine the
     * $_SERVER['DOCUMENT_ROOT']
     *
     * @param $path
     *   The path we want to filter
     *
     * @return string
     *   The filtered path to the calling file.
     */
    protected function filterFilePath($path)
    {
        // There may or may not be a trailing '/'.
        // We remove it, just in case, to make sure that we remove the doc root
        // completely from the $path variable.
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        if (isset($docRoot) && strpos($path, $docRoot) === 0) {
            // Found it on position 0.
            $path = '. . ./' . substr($path, strlen($docRoot) + 1);
        }

        return $path;
    }

    /**
     * Finds the place in the code from where krexx was called.
     *
     * @return array
     *   The code, from where krexx was called.
     *   array(
     *     'file' => 'someFile.php',
     *     'line' => 123,
     *     'varname' => '$myVar'
     *   );
     */
    abstract public function findCaller();
}
