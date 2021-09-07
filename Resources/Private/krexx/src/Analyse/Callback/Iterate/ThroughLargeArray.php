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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Routing\Process\ProcessConstInterface;

/**
 * Going through an array with 2000 objects can create more than 1GB of
 * Output. Afaik, there is no browser that can actually display this kind
 * of garbage. Our solution is simple:
 * We only display the name and the type of the object. Everything else
 * will be omitted.
 * We also do not use recursion handling, because assigning 2000 recursions
 * to the frontend would slow down the browser considerately. Also, the info
 * we are providing here should be as huge as the info about a recursion,
 * measured in MB.
 *
 * @uses array data
 *   The array want to iterate.
 * @uses bool multiline
 *   Do we need a multiline code generation?
 */
class ThroughLargeArray extends AbstractCallback implements
    CodegenConstInterface,
    CallbackConstInterface,
    ConnectorsConstInterface,
    ProcessConstInterface
{

    /**
     * Renders the expendable around the array analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        $recursionMarker = $this->pool->recursionHandler->getMarker();
        $output .= $this->pool->render->renderSingeChildHr();

        // Iterate through.
        foreach ($this->parameters[static::PARAM_DATA] as $key => &$value) {
            // We will not output our recursion marker.
            // Meh, the only reason for the recursion marker
            // in arrays is because of the $GLOBAL array, which
            // we will only render once.
            if ($key === $recursionMarker) {
                continue;
            }

            /** @var Model $model */
            $model = $this->pool->createClass(Model::class)->setCodeGenType(
                $this->parameters[static::PARAM_MULTILINE] === true ?
                    static::CODEGEN_TYPE_ITERATOR_TO_ARRAY : static::CODEGEN_TYPE_PUBLIC
            );

            // Handling string keys of the array.
            $this->handleKey($key, $model);
            // Handling of the value and add some output.
            $output .= $this->handleValue($value, $model);
        }

        return $output . $this->pool->render->renderSingeChildHr();
    }

    /**
     * Adding quotation marks and a connector, depending on the type
     * of the key.
     *
     * @param int|string $key
     *   The key (or name) of what we are analysing.
     * @param Model $model
     *   The so far prepared model we are preparing further.
     */
    protected function handleKey($key, Model $model)
    {
        if (is_string($key) === true) {
            $model->setName($this->pool->encodingService->encodeString($key))
                ->setConnectorType(static::CONNECTOR_ASSOCIATIVE_ARRAY)
                ->setKeyType(static::TYPE_STRING);

            return;
        }

        $model->setName($key)->setConnectorType(static::CONNECTOR_NORMAL_ARRAY);
    }

    /**
     * Starting the analysis of the value.
     *
     * @param mixed $value
     *   The value from the current array position.
     * @param Model $model
     *   The so far prepared model.
     * @return string
     *   The generated markup
     */
    protected function handleValue(&$value, Model $model): string
    {
        if (is_object($value) === true) {
            // We will not go too deep here, and say only what it is.
            $model->setType(static::TYPE_SIMPLE_CLASS)
                ->setNormal(get_class($value));

            return $this->pool->render->renderExpandableChild($model);
        }

        if (is_array($value) === true) {
            // Adding another array to the output may be as bad as a
            // complete object analysis.
            $model->setType(static::TYPE_SIMPLE_ARRAY)
                ->setNormal('count: ' . count($value));

                return $this->pool->render->renderExpandableChild($model);
        }

        // We handle the simple type normally with the analysis hub.
        return $this->pool->routing->analysisHub($model->setData($value));
    }
}
