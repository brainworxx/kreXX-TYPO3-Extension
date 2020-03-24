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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Code\Codegen;
use Brainworxx\Krexx\Analyse\ConstInterface;
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
     * These keys are rendered with an extra.
     *
     * @var array
     */
    protected $keysWithExtra = [
        ConstInterface::META_COMMENT,
        ConstInterface::META_DECLARED_IN,
        ConstInterface::META_SOURCE,
        ConstInterface::META_PRETTY_PRINT,
        ConstInterface::META_CONTENT
    ];

    /**
     * Renders the meta data of a class, which is actually the same as the
     * method analysis rendering.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
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
     * @param mixed $meta
     *   The text to display.
     *
     * @return string
     *   The rendered html.
     */
    protected function handleNoneReflections(string $key, $meta): string
    {
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setData($meta)
            ->setName($key)
            ->setType($key === static::META_PRETTY_PRINT ? $key : static::TYPE_REFLECTION);

        if (in_array($key, $this->keysWithExtra)) {
            $model->setNormal(static::UNKNOWN_VALUE)->setHasExtra(true);
        } else {
            $model->setNormal($meta);
        }

        if ($key === static::META_DECODED_JSON) {
            // Prepare the json code generation.
            return $this->pool->routing->analysisHub($model->setMultiLineCodeGen(Codegen::JSON_DECODE));
        }

        // Sorry, no code generation for you guys.
        $this->pool->codegenHandler->setAllowCodegen(false);
        if (is_string($meta)) {
            // Render a single data point.
            $result = $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . $key . static::EVENT_MARKER_END,
                    $model
                )
            );
        } else {
            // Fallback to whatever-rendering.
            $result = $this->pool->routing->analysisHub($model);
        }

        $this->pool->codegenHandler->setAllowCodegen(true);
        return $result;
    }
}
