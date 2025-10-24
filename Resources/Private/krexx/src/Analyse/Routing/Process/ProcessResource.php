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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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
        return is_resource($model->getData()) || gettype($model->getData()) === 'resource (closed)';
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
        $typeString = $this->pool->messages->getHelp('resource') . ' (' . get_resource_type($resource) . ')';
        $transRes = $this->pool->messages->getHelp('resource');

        switch ($typeString) {
            case $transRes . ' (stream)':
                $meta = stream_get_meta_data($resource);
                break;

            case $transRes . ' (curl)':
                // No need to check for a curl installation, because we are
                // facing a curl instance right here.
                $meta = curl_getinfo($resource);
                break;

            case $transRes . ' (process)':
                $meta = proc_get_status($resource);
                break;

            default:
                return $this->renderUnknownOrClosed($this->model, $resource);
        }

        // Output metadata from the class.
        return $this->pool->render->renderExpandableChild(
            $this->dispatchProcessEvent(
                $this->model->setType(static::TYPE_RESOURCE)
                    ->addParameter(static::PARAM_DATA, $meta)
                    ->setNormal($typeString)
                    ->injectCallback($this->pool->createClass(ThroughResource::class))
            )
        );
    }

    /**
     * Retrieve the ressource type string.
     *
     * @param resource|object $resource
     *   The ressource
     *
     * @deprecated
     *   Since 6.0.0 will be removed.
     * @codeCoverageIgnore
     *   We will not test deprecated stuff.
     *
     * @return string
     *   The type string.
     */
    protected function retrieveTypeString($resource): string
    {
        if (is_object($resource)) {
            return get_class($resource);
        }

        return $this->pool->messages->getHelp('resource') . ' (' . get_resource_type($resource) . ')';
    }

    /**
     * Render an unknown or closed resource.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     * @param resource $resource
     *   The resource, that we are analysing.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderUnknownOrClosed(Model $model, $resource): string
    {
        return $this->pool->render->renderExpandableChild(
            $this->dispatchNamedEvent(
                __FUNCTION__,
                $model->setNormal(gettype($resource))
                    ->setType(static::TYPE_RESOURCE)
            )
        );
    }
}
