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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\Analyse\Objects\Meta;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Displaying the meta stuff from the class analysis.
 *
 * @uses array data
 *   An array of the metadata we need to iterate.
 *   Might contain strings or another array.
 * @uses string codeGenType
 *   The code generation constants we want to use for none meta stuff.
 */
class ThroughMeta extends AbstractCallback implements CallbackConstInterface
{
    /**
     * These keys are rendered with an extra.
     *
     * @var string[]
     */
    protected array $keysWithExtra = [];

    /**
     * Normal meta rendering without extra.
     *
     * @var string[]
     */
    protected array $stuffToProcess = [];

    /**
     * We pass these to the routing.
     *
     * @var array
     */
    protected array $simpleAnalysisRouting = [];

    /**
     * Inject the pool and init the workflow.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $messages = $pool->messages;

        $this->keysWithExtra = [
            $messages->getHelp('metaComment'),
            $messages->getHelp('metaDeclaredIn'),
            $messages->getHelp('metaSource'),
            $messages->getHelp('metaPrettyPrint'),
            $messages->getHelp('metaContent'),
            $messages->getHelp('metaAttributes'),
        ];

        $this->stuffToProcess = [
            $messages->getHelp('metaInheritedClass'),
            $messages->getHelp('metaInterfaces'),
            $messages->getHelp('metaTraits'),
        ];

        $this->simpleAnalysisRouting = [
            $messages->getHelp('metaDecodedJson'),
            $messages->getHelp('metaDecodedBase64'),
        ];
    }

    /**
     * Renders the metadata of a class, which is actually the same as the
     * method analysis rendering.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        foreach ($this->parameters[static::PARAM_DATA] as $key => $metaData) {
            if (in_array($key, $this->stuffToProcess, true)) {
                $output .= $this->pool->render->renderExpandableChild(
                    $this->dispatchEventWithModel(
                        $key,
                        $this->pool->createClass(Model::class)
                            ->setName($key)
                            ->setType($this->pool->messages->getHelp('classInternals'))
                            ->addParameter(static::PARAM_DATA, $metaData)
                            ->injectCallback(
                                $this->pool->createClass(ThroughMetaReflections::class)
                            )
                    )
                );
            } elseif (!empty($metaData)) {
                $output .= $this->handleNoneReflections($this->prepareModel($key, $metaData));
            }
        }

        return $output;
    }

    /**
     * Prepare the model for the no reflection rendering.
     *
     * @param string $key
     *   The key in the output list.
     * @param mixed $meta
     *   The data to display.
     *
     * @return \Brainworxx\Krexx\Analyse\Model
     *   The prepared model.
     */
    protected function prepareModel(string $key, $meta): Model
    {
        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setData($meta)
            ->setName($key)
            ->setType(
                $key === $this->pool->messages->getHelp('metaPrettyPrint') ? $key : static::TYPE_REFLECTION
            );

        if (isset($this->parameters[static::PARAM_CODE_GEN_TYPE])) {
            $model->setCodeGenType($this->parameters[static::PARAM_CODE_GEN_TYPE]);
        }

        if (in_array($key, $this->keysWithExtra, true)) {
            $model->setNormal(static::UNKNOWN_VALUE)->setHasExtra(true);
        } else {
            $model->setNormal($meta);
        }

        return $model;
    }

    /**
     * The info is already here. We just need to output them.
     *
     * @param Model $model
     *   THe model so far.
     *
     * @return string
     *   The rendered html.
     */
    protected function handleNoneReflections(Model $model): string
    {
        $key = $model->getName();

        if (in_array($key, $this->simpleAnalysisRouting, true)) {
            // Prepare the json/ base64 code generation.
            return $this->pool->routing->analysisHub($model);
        }

        if ($key === $this->pool->messages->getHelp('metaReflection')) {
            return $this->pool->createClass(Meta::class)
                ->setParameters([static::PARAM_REF => $model->getNormal()])
                ->callMe();
        }

        // Sorry, no code generation for you guys.
        $this->pool->codegenHandler->setCodegenAllowed(false);
        if (is_string($model->getData())) {
            // Render a single data point.
            $result = $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(__FUNCTION__ . $key . static::EVENT_MARKER_END, $model)
            );
        } else {
            // Fallback to whatever-rendering.
            $result = $this->pool->routing->analysisHub($model);
        }

        $this->pool->codegenHandler->setCodegenAllowed(true);
        return $result;
    }
}
