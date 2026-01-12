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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Analyse\Getter\AbstractGetter;
use Brainworxx\Krexx\Analyse\Getter\ByMethodName;
use Brainworxx\Krexx\Analyse\Getter\ByRegExContainer;
use Brainworxx\Krexx\Analyse\Getter\ByRegExDelegate;
use Brainworxx\Krexx\Analyse\Getter\ByRegExProperty;
use Brainworxx\Krexx\Analyse\Model;
use ReflectionMethod;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Analyse\Code\ConnectorsConstInterface;

/**
 * Getter method analysis methods.
 *
 * @uses array normalGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'get'
 * @uses array isGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'is'
 * @uses array hasGetter
 *   The list of all reflection methods we are analysing, hosting the
 *   get methods starting with 'has'
 * @uses \Brainworxx\Krexx\Service\Reflection\ReflectionClass ref
 *   A reflection class of the object we are analysing.
 * @uses object data
 *   The object we are currently analysing
 * @uses string currentPrefix
 *   The current prefix we are analysing (get, is, has).
 *   Does not get set from the outside.
 * @uses mixed value
 *   Store the retrieved value from the getter analysis here and give
 *   event subscribers the opportunity to do something with it.
 */
class ThroughGetter extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * The parameter name of the prefix we ara analysing.
     *
     * @var string
     */
    public const CURRENT_PREFIX = 'currentPrefix';

    /**
     * Here we memorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected int $deep = 0;

    /**
     * These analysers will take a look at the getter.
     *
     * @var \Brainworxx\Krexx\Analyse\Getter\AbstractGetter[]
     */
    protected array $getterAnalyser;

    /**
     * Class for the comment analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected Methods $commentAnalysis;

    /**
     * The method declaration retriever.
     *
     * @var \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration
     */
    protected MethodDeclaration $methodDeclaration;

    /**
     * Injects the pool and initializes the comment analysis.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->commentAnalysis = $this->pool->createClass(Methods::class);
        $this->getterAnalyser = [
            $this->pool->createClass(ByMethodName::class),
            $this->pool->createClass(ByRegExProperty::class),
            $this->pool->createClass(ByRegExContainer::class),
            $this->pool->createClass(ByRegExDelegate::class)
        ];
        $this->methodDeclaration = $this->pool->createClass(MethodDeclaration::class);
    }

    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        if (!empty($this->parameters[static::PARAM_NORMAL_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'get';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_NORMAL_GETTER]);
        }

        if (!empty($this->parameters[static::PARAM_IS_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'is';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_IS_GETTER]);
        }

        if (!empty($this->parameters[static::PARAM_HAS_GETTER])) {
            $this->parameters[static::CURRENT_PREFIX] = 'has';
            $output .= $this->goThroughMethodList($this->parameters[static::PARAM_HAS_GETTER]);
        }

        return $output;
    }

    /**
     * Iterating through a list of reflection methods.
     *
     * @param \ReflectionMethod[] $methodList
     *   The list of methods we are going through, consisting of \ReflectionMethod
     *
     * @return string
     *   The generated DOM.
     */
    protected function goThroughMethodList(array $methodList): string
    {
        $output = '';
        foreach ($methodList as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            /** @var Model $model */
            $model = $this->pool->createClass(Model::class)
                ->setName($reflectionMethod->getName())
                ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC);
            $this->assignMetaDataToJson($model, $reflectionMethod);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic()) {
                $model->setConnectorType(static::CONNECTOR_STATIC_METHOD);
            } else {
                $model->setConnectorType(static::CONNECTOR_METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue(
                $reflectionMethod,
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $model
                )
            );
        }

        return $output;
    }

    /**
     * We assign the metadata (comments and declaration) to the model.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     * @param \ReflectionMethod $reflectionMethod
     *   Reflection of the method that we are analysing.
     */
    protected function assignMetaDataToJson(Model $model, ReflectionMethod $reflectionMethod): void
    {
        $comments = $this->commentAnalysis
            ->getComment($reflectionMethod, $this->parameters[static::PARAM_REF]);
        $declaration = nl2br($this->methodDeclaration->retrieveDeclaration($reflectionMethod));
        $messages = $this->pool->messages;
        $model->addToJson($messages->getHelp('metaMethodComment'), nl2br($comments))
            ->addToJson($messages->getHelp('metaDeclaredIn'), $declaration);
    }

    /**
     * Try to get a possible return value and render the result.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   A reflection ot the method we are analysing
     * @param Model $model
     *   The model so far.
     *
     * @return string
     *   The rendered markup.
     */
    protected function retrievePropertyValue(ReflectionMethod $reflectionMethod, Model $model): string
    {
        $this->resetParameters($reflectionMethod);
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        $currentPrefix = $this->parameters[static::CURRENT_PREFIX];
        foreach ($this->getterAnalyser as $analyser) {
            $value = $analyser->retrieveIt($reflectionMethod, $reflectionClass, $currentPrefix);
            if ($analyser->hasResult()) {
                $this->prepareParameters($value, $analyser, $reflectionMethod);
                $this->prepareModel($model, $value);
                break;
            }
        }

        $this->dispatchEventWithModel(__FUNCTION__ . '::resolving', $model);

        if ($this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND]) {
            $messages = $this->pool->messages;
            // Found nothing  :-(
            // We literally have no info. We need to tell the user.
            // We render this right away, without any routing.
            return $this->pool->render->renderExpandableChild($this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $model->setType($messages->getHelp('getterValueUnknown'))
                    ->setNormal($messages->getHelp('getterValueUnknown'))
                    ->addToJson($messages->getHelp('metaHint'), $messages->getHelp('getterUnknown'))
            ));
        }

        return $this->pool->routing->analysisHub(
            $this->dispatchEventWithModel(__FUNCTION__ . static::EVENT_MARKER_END, $model)
        );
    }

    /**
     * Prepare the model with the retrieved value.
     *
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model, so far.
     * @param mixed $value
     *   The retrieved possible value. Can be anything.
     */
    protected function prepareModel(Model $model, $value): void
    {
        $model->setData($value);
        if ($value === null) {
            // A NULL value might mean that the values does not
            // exist, until the getter computes it.
            $model->addToJson(
                $this->pool->messages->getHelp('metaHint'),
                $this->pool->messages->getHelp('getterNull')
            );
        }
    }

    /**
     * @param mixed $value
     *   The possible value that we retrieved.
     * @param \Brainworxx\Krexx\Analyse\Getter\AbstractGetter $analyser
     *   The analyser that we used.
     * @param \ReflectionMethod $reflectionMethod
     *   Reflection of the method that we are analysing.
     */
    protected function prepareParameters($value, AbstractGetter $analyser, ReflectionMethod $reflectionMethod): void
    {
        $this->parameters[static::PARAM_ADDITIONAL] = [
            static::PARAM_NOTHING_FOUND => false,
            static::PARAM_VALUE => $value,
            static::PARAM_REFLECTION_PROPERTY => $analyser->getReflectionProperty(),
            static::PARAM_REFLECTION_METHOD => $reflectionMethod
        ];
    }

    /**
     * Reset the parameters for every getter.
     *
     * We do this for the eventsystem, so a listener can gete additional data
     * from the current analysis process. Or the listener can inject stuff
     * here.
     *
     * @param \ReflectionMethod $reflectionMethod
     * @return void
     */
    protected function resetParameters(ReflectionMethod $reflectionMethod)
    {
        $this->parameters[static::PARAM_ADDITIONAL] = [
            static::PARAM_NOTHING_FOUND => true,
            static::PARAM_VALUE => null,
            static::PARAM_REFLECTION_PROPERTY => null,
            static::PARAM_REFLECTION_METHOD => $reflectionMethod
        ];
    }
}
