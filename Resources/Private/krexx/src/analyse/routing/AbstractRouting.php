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

namespace Brainworxx\Krexx\Analyse\Routing;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Abstract class for further processing of found class properties.
 *
 * @package Brainworxx\Krexx\Analyse\Routing
 */
abstract class AbstractRouting
{
    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray
     */
    protected $processArray;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean
     */
    protected $processBoolean;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure
     */
    protected $processClosure;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat
     */
    protected $processFloat;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger
     */
    protected $processInteger;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull
     */
    protected $processNull;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject
     */
    protected $processObject;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource
     */
    protected $processResource;

    /**
     * @var \Brainworxx\Krexx\Analyse\Routing\Process\ProcessString
     */
    protected $processString;

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
     * Generates a id for the DOM.
     *
     * This is used to jump from a recursion to the object analysis data.
     * The ID is the object hash as well as the kruXX call number, to avoid
     * collisions (even if they are unlikely).
     *
     * @param mixed $data
     *   The object from which we want the ID.
     *
     * @return string
     *   The generated id.
     */
    protected function generateDomIdFromObject($data)
    {
        return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_' . spl_object_hash($data);
    }
}
