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

namespace Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers;

use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Simply change some info text, in case we are dumping magical Aimeos mathods.
 *
 * @package Brainworxx\Includekrexx\Plugins\AimeosMagic\EventHandlers
 */
class MagicalFunctionsDumping  implements EventHandlerInterface
{
    /**
     * Our pool, what else?
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
     * Replacing some stuff, in case we are rendering Aimeos Magical Methods.
     *
     * @param array $params
     *   The parameters from the analyse class
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available, so far.
     *
     * @throws \ReflectionException
     *
     * @return string
     *   The generated markup.
     */
    public function handle(array $params, Model $model = null)
    {
        if (empty($params['aimeos object'])) {
            // Normal analysis stuff will not get changed.
            return '';
        }

        // We need to adjust the already dumped methods, because we have
        // a hirarchy in there.
        $modelParameters = $model->getParameters();
        $methodsToDo = $modelParameters['data'];
        $lookupArray = array_flip($params['methods done']);
        if (is_array($methodsToDo) || count($methodsToDo) > 0) {
            foreach ($methodsToDo as $key => $rethodRef) {
                if (isset($lookupArray[$rethodRef->name])) {
                    unset($methodsToDo[$key]);
                }
            }

            $model->setName($params['aimeos name'])
                ->addToJson('hint', 'Aimeos magical methods will get passed to the reciever object.')
                ->addToJson('reciever object', $params['aimeos object'])
                ->addParameter('data', $methodsToDo);
        }

        return '';
    }
}