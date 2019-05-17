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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Includekrexx\Plugins\FluidDebugger\Rewrites\Code\Codegen;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Class VhsMethods
 *
 * @event Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughMethods::callMe::end
 * @package Brainworxx\Includekrexx\Plugins\FluidCodeGen\EventHandlers
 */
class VhsMethods implements EventHandlerInterface, ConstInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * We set the multiline code generation to VHS, and we add the name of the
     * parameter for the VHS code generation into the 'paramArray'
     *
     * @param AbstractCallback $callback
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model so far.
     *
     * @return string
     *   Return an empty string.
     */
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $params = $callback->getParameters();
        /** @var \ReflectionMethod $reflectionMethod */
        $reflectionMethod = $params[static::PARAM_REF_METHOD];

        $paramArray = [];
        foreach ($reflectionMethod->getParameters() as $reflectionParameter) {
            $paramArray[] = $reflectionParameter->getName();
        }

        // Switch to VHS Viewhelper
        $model->setMultiLineCodeGen(Codegen::VHS_CALL_VIEWHELPER)
            ->addParameter('paramArray', $paramArray);

        return '';
    }
}
