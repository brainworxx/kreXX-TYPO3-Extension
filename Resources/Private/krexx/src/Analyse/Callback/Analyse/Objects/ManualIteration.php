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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Throwable;

/**
 * The method iterator_to_array just failed.
 *
 * There may be objects or worse as array keys, so we need to do a manual iteration.
 */
class ManualIteration extends AbstractCallback implements CallbackConstInterface, ConfigConstInterface
{
    /**
     * Inject the pool.
     *
     * @param Pool $pool
     */
    public function __construct(protected Pool $pool)
    {
    }

    /**
     * Just do the analysis.
     *
     * Runtime, memory and nesting level were already checked by the
     * Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Traversable
     * class.
     *
     * @return string
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        $data = $this->parameters[static::PARAM_DATA] ?? null;
        if ($data instanceof \Traversable) {
            return $output . $this->manualIteration(data: $data);
        }

        return $output;
    }

    /**
     * The iterator_to_array just failed, so we need to do a manual iteration.
     * This may mean that we are dealing with objects or worse as array keys.
     *
     * @param \Traversable $data
     * @return string
     */
    protected function manualIteration(\Traversable $data): string
    {
        $keys = [];
        $values = [];
        $counter = 0;
        // We are not doing a simplifies version of this.
        // Having objects as keys is bad enough.
        // But having 300+ objects as keys is even worse.
        // Those are not even reachable via code generation.
        $limit = (int) $this->pool->config->getSetting(name: static::SETTING_ARRAY_COUNT_LIMIT);
        try {
            set_error_handler(callback: $this->pool->retrieveErrorCallback());
            foreach ($data as $key => $value) {
                ++$counter;
                $keys[] = $key;
                $values[] = $value;
                if ($counter >= $limit) {
                    break;
                }
            }
            restore_error_handler();
        } catch (Throwable) {
            //Restore the previous error handler, and return an empty string.
            restore_error_handler();
            return '';
        }

        return $this->renderResult(result: $keys, helpKey: 'foreachKeys') .
            $this->renderResult(result: $values, helpKey: 'foreachValues');
    }

    /**
     * Renders the result of the manual iteration.
     *
     * @param array $result
     *   The result of the manual iteration, either keys or values.
     * @param string $helpKey
     *   The help key to use for the model.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function renderResult(array $result, string $helpKey): string
    {
        $messages = $this->pool->messages;
        /** @var Model $model */
        $model = $this->pool->createClass(classname: Model::class)
            ->setType(type: $messages->getHelp(key: 'foreachType'))
            ->addParameter(name: static::PARAM_DATA, value: $result)
            ->injectCallback(object: $this->pool->createClass(classname: ThroughArray::class))
            ->setNormal(normal: $messages->getHelp(key: $helpKey))
            ->addToJson(key: $messages->getHelp(key: 'metaLength'), value: (string)count(value: $result));

        // Code generation may or may not be allowed here, but we need to make
        // sure that it is not allowed for the next step.
        $oldCodeGenSetting = $this->pool->codegenHandler->isCodegenAllowed();
        $this->pool->codegenHandler->setCodegenAllowed(false);
        $this->pool->emergencyHandler->upOneNestingLevel();

        $result =  $this->pool->render->renderExpandableChild(model:
            $this->dispatchEventWithModel(name: $helpKey, model: $model)
        );

        $this->pool->emergencyHandler->downOneNestingLevel();
        $this->pool->codegenHandler->setCodegenAllowed($oldCodeGenSetting);

        return $result;
    }
}
