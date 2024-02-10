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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Scalar;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Abstract class for the additional scalar analysis like strings and integers.
 */
abstract class AbstractScalar
{
    /**
     * The pool, like the variable name says.
     *
     * @var \Brainworxx\Krexx\Service\Factory\Pool
     */
    protected $pool;

    /**
     * Inject hte pool.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Generate a DOM id for recursion handling.
     *
     * The meta analysis should always be the same.
     *
     * @param string $string
     *   The string we are analysing.
     * @param string $className
     *   The class name of the callback.
     *
     * @return string
     *   The generated DOM id.
     */
    protected function generateDomId(string $string, string $className): string
    {
        return 'k' . $this->pool->emergencyHandler->getKrexxCount() . '_scalar_' . md5($string . '_' . $className);
    }

    /**
     * Doing special analysis with scalar types.
     *
     * @param Model $model
     *   The model, so far.
     * @param string $originalData
     *   The unescaped original data of the analysis.
     *
     * @return \Brainworxx\Krexx\Analyse\Model
     *   The adjusted model.
     */
    abstract public function handle(Model $model, string $originalData): Model;
}
