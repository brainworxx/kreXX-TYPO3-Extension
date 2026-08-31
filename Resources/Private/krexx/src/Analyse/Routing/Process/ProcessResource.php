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

use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughResource;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\AbstractRouting;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Processing of resources.
 */
class ProcessResource extends AbstractRouting implements ProcessInterface, CallbackConstInterface, ProcessConstInterface
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
     * Is this one a resource?
     *
     * @param Model $model
     *   The value we are analysing.
     *
     * @return bool
     *   Well, is this a resource?
     */
    public function canHandle(Model $model): bool
    {
        $this->model = $model;
        return is_resource($model->getData()) || gettype(value: $model->getData()) === 'resource (closed)';
    }

    /**
     * Analyses a resource.
     *
     * @return string
     *   The rendered markup.
     */
    public function handle(): string
    {
        $resource = $this->model->getData();
        $typeString = $this->pool->messages->getHelp(key: 'resource') . ' (' .
            get_resource_type(resource: $resource) . ')';
        $transRes = $this->pool->messages->getHelp(key: 'resource');

        switch ($typeString) {
            case $transRes . ' (stream)':
                $meta = stream_get_meta_data(stream: $resource);
                break;

            case $transRes . ' (curl)':
                // No need to check for a curl installation, because we are
                // facing a curl instance right here.
                $meta = curl_getinfo(handle: $resource);
                break;

            case $transRes . ' (process)':
                $meta = proc_get_status(process: $resource);
                break;

            default:
                return $this->renderUnknownOrClosed(model: $this->model, resource: $resource);
        }

        // Output metadata from the class.
        return $this->pool->render->renderExpandableChild(
            model: $this->dispatchProcessEvent(
                model: $this->model->setType(type: static::TYPE_RESOURCE)
                    ->addParameter(name: static::PARAM_DATA, value: $meta)
                    ->setNormal(normal: $typeString)
                    ->injectCallback(object: $this->pool->createClass(classname: ThroughResource::class))
            )
        );
    }

    /**
     * Render an unknown or closed resource.
     *
     * @param Model $model
     *   The model, so far.
     * @param resource $resource
     *   The resource, that we are analysing.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderUnknownOrClosed(Model $model, mixed $resource): string
    {
        return $this->pool->render->renderExpandableChild(
            model: $this->dispatchNamedEvent(
                name: __FUNCTION__,
                model: $model->setNormal(normal: gettype(value: $resource))
                    ->setType(type: static::TYPE_RESOURCE)
            )
        );
    }
}
