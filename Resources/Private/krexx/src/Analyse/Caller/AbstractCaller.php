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

namespace Brainworxx\Krexx\Analyse\Caller;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Abstract defining what a CallerFinder class must implement.
 *
 * @package Brainworxx\Krexx\Analyse\Caller
 */
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
     *
     * We use this one to identify the line from which kreXX was called.
     *
     * @var string
     */
    protected $pattern;

    /**
     * Here we store a more sophisticated list of calls.
     *
     * We use his list to identify the variable name of the call.
     *
     * @var array
     */
    protected $callPattern;

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
     * @param string $pattern
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
     * Finds the place in the code from where krexx was called.
     *
     * @var string $headline
     *   The headline from the call.
     * @var mixed $data
     *   The variable we are currently analysing.
     *
     * @return array
     *   The code, from where krexx was called.
     *   array(
     *     'file' => 'someFile.php',
     *     'line' => 123,
     *     'varname' => '$myVar',
     *     'type' => 'Analysis of $myString, string'
     *   );
     */
    abstract public function findCaller($headline, $data);

    /**
     * Get the analysis type for the metadata and the page title.
     *
     * @param string $headline
     *   The headline from the call. We will use this one, if not empty.
     * @param string $varname
     *   The name of the variable that we were able to determine.
     * @param mixed $data
     *   The variable tht we are analysing.
     *
     * @return string
     *   The analysis type.
     */
    protected function getType($headline, $varname, $data)
    {
        if (empty($headline) === true) {
            if (is_object($data) === true) {
                $type = get_class($data);
            } else {
                $type = gettype($data);
            }
            return 'Analysis of ' . $varname . ', ' . $type;
        }

        // We already have a headline and will not touch it.
        return $headline;
    }
}
