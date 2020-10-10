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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analyse\Routing;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessArray;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessBoolean;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessClosure;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessFloat;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessInteger;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessNull;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessObject;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessOther;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessResource;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessString;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessInterface;

/**
 * "Routing" for kreXX
 *
 * The analysisHub decides what to do next with the model.
 *
 * @package Brainworxx\Krexx\Analyse\Routing
 */
class Routing extends AbstractRouting
{
    /**
     * @var ProcessInterface[]
     */
    protected $processors = [];

    /**
     * Inject the pool and create all the routing classes.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->processors[ProcessString::class] = $pool->createClass(ProcessString::class);
        $this->processors[ProcessInteger::class] = $pool->createClass(ProcessInteger::class);
        $this->processors[ProcessNull::class] = $pool->createClass(ProcessNull::class);
        $this->processors[ProcessArray::class] = $pool->createClass(ProcessArray::class);
        $this->processors[ProcessClosure::class] = $pool->createClass(ProcessClosure::class);
        $this->processors[ProcessObject::class] = $pool->createClass(ProcessObject::class);
        $this->processors[ProcessBoolean::class] = $pool->createClass(ProcessBoolean::class);
        $this->processors[ProcessFloat::class] = $pool->createClass(ProcessFloat::class);
        $this->processors[ProcessResource::class] = $pool->createClass(ProcessResource::class);
        $this->processors[ProcessOther::class] = $pool->createClass(ProcessOther::class);

        $pool->routing = $this;
    }

    /**
     * Dump information about a variable.
     *
     * This function decides what functions analyse the data
     * and acts as a hub.
     *
     * @param Model $model
     *   The variable we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    public function analysisHub(Model $model): string
    {
        // Check memory and runtime.
        if ($this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            return '';
        }

        foreach ($this->processors as $processor) {
            if ($processor->canHandle($model)) {
                return $processor->handle($model);
            }
        }

        // The ProcessOther should prevent this.
        return '';
    }
}
