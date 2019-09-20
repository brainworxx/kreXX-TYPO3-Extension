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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Displaying the meta stuff from the class analysis.
 *
 * @uses array data
 *   An array of the meta data we need to iterate.
 *   Might contain strings or another array.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 */
class ThroughMeta extends AbstractCallback
{

    /**
     * Renders the meta data of a class, which is actually the same as the
     * method analysis rendering.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();
        $reflectionStuff = [static::META_INHERITED_CLASS, static::META_INTERFACES, static::META_TRAITS];

        foreach ($this->parameters[static::PARAM_DATA] as $key => $metaData) {
            if (in_array($key, $reflectionStuff)) {
                $output .= $this->pool->render->renderExpandableChild(
                    $this->dispatchEventWithModel(
                        $key,
                        $this->pool->createClass(Model::class)
                            ->setName($key)
                            ->setType(static::TYPE_INTERNALS)
                            ->addParameter(static::PARAM_DATA, $metaData)
                            ->injectCallback(
                                $this->pool->createClass(ThroughMetaReflections::class)
                            )
                    )
                );
            } elseif (empty($metaData) === false) {
                $output .= $this->handleNoneReflections($key, $metaData);
            }
        }

        return $output;
    }

    /**
     * The info is already here. We just need to output them.
     *
     * @param string $key
     *   The key in the output list.
     * @param string $meta
     *   The text to display.
     *
     * @return string
     *   The rendered html.
     */
    protected function handleNoneReflections($key, $meta)
    {
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setData($meta)
            ->setName($key)
            ->setType(static::TYPE_REFLECTION);

        if ($key === static::META_COMMENT ||
            $key === static::META_DECLARED_IN ||
            $key === static::META_SOURCE
        ) {
            $model->setNormal(static::UNKNOWN_VALUE);
            $model->setHasExtra(true);
        } else {
            $model->setNormal($meta);
        }

        // Render a single data point.
        return $this->pool->render->renderSingleChild(
            $this->dispatchEventWithModel(
                __FUNCTION__ . $key . static::EVENT_MARKER_END,
                $model
            )
        );
    }
}
