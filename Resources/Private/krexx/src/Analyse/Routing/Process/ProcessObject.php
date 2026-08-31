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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Processing of objects.
 */
class ProcessObject extends AbstractProcessNoneScalar implements CallbackConstInterface, ProcessConstInterface
{
    /**
     * The model we are currently working on.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Is this one an object?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this an object?
     */
    public function canHandle(Model $model): bool
    {
        $this->model = $model;
        return is_object(value: $model->getData());
    }

    /**
     * Render a dump for an object.
     *
     * @return string
     *   The generated markup.
     */
    protected function handleNoneScalar(): string
    {
        $this->pool->emergencyHandler->upOneNestingLevel();

        // Remember that we've been here before.
        $this->pool->recursionHandler->addToHive(bee: $this->model->getData());

        $object = $this->model->getData();
        // Output data from the class.
        $result = $this->pool->render->renderExpandableChild(
            model: $this->dispatchProcessEvent(
                model: $this->model->setType(type: static::TYPE_CLASS)
                    ->addParameter(name: static::PARAM_DATA, value: $object)
                    ->addParameter(name: static::PARAM_NAME, value: $this->model->getName())
                    ->setNormal(normal: '\\' . $object::class)
                    ->setDomid(domid: $this->generateDomIdFromObject(data: $object))
                    ->injectCallback(object: $this->pool->createClass(classname: Objects::class))
            )
        );

        $this->pool->emergencyHandler->downOneNestingLevel();
        return $result;
    }
}
