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
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Analyse\Comment\ReturnType;
use Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use ReflectionClass;
use ReflectionMethod;

/**
 * Methods analysis methods. Pun not intended.
 *
 * @uses array data
 *   Array of reflection methods.
 * @uses \reflectionClass ref
 *   Reflection of the class we are analysing.
 */
class ThroughMethods extends AbstractCallback implements
    CallbackConstInterface,
    CodegenConstInterface,
    ConnectorsConstInterface
{
    /**
     * Analysis class for method comments.
     *
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected $commentAnalysis;

    /**
     * @var \Brainworxx\Krexx\Analyse\Declaration\MethodDeclaration
     */
    protected $methodDeclaration;

    /**
     * Inject the pool and get the comment analysis online.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->commentAnalysis = $this->pool->createClass(Methods::class);
        $this->methodDeclaration = $this->pool->createClass(MethodDeclaration::class);
    }

    /**
     * Simply start to iterate through the methods.
     *
     * @return string
     *   The rendered markup.
     */
    public function callMe(): string
    {
        $result = $this->dispatchStartEvent();
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $refClass */
        $refClass = $this->parameters[static::PARAM_REF];

        // Deep analysis of the methods.
        /** @var \ReflectionMethod $refMethod */
        foreach ($this->parameters[static::PARAM_DATA] as $refMethod) {
            $declaringClass = $refMethod->getDeclaringClass();
            $methodData = $this->retrieveMethodData($refMethod, $refClass);

            // Update the reflection method, so an event subscriber can do
            // something with it.
            $this->parameters[static::PARAM_REFLECTION_METHOD] = $refMethod;

            // Render it!
            $result .= $this->pool->render->renderExpandableChild($this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $this->pool->createClass(Model::class)
                    ->setName($refMethod->name)
                    // Remove the ',' after the last char.
                    ->setConnectorParameters(rtrim($this->retrieveParameters($refMethod, $methodData), ', '))
                    ->setType(
                        $this->getDeclarationKeywords($refMethod, $declaringClass, $refClass) . static::TYPE_METHOD
                    )->setConnectorType($this->retrieveConnectorType($refMethod))
                    ->addParameter(static::PARAM_DATA, $methodData)
                    ->setCodeGenType($refMethod->isPublic() ? static::CODEGEN_TYPE_PUBLIC : '')
                    ->setReturnType($methodData[$this->pool->messages->getHelp('metaReturnType')])
                    ->injectCallback($this->pool->createClass(ThroughMeta::class))
            ));
        }

        return $result;
    }

    /**
     * Retrieve the method analysis data.
     *
     * @param \ReflectionMethod $refMethod
     *   Reflection of the method that we are analysing.
     * @param \ReflectionClass $refClass
     *   Reflection of the class that we are analysing right now.
     *
     * @return array
     *   The collected method data.
     */
    protected function retrieveMethodData(
        ReflectionMethod $refMethod,
        ReflectionClass $refClass
    ): array {
        $methodData = [];
        $messages = $this->pool->messages;

        // Get the comment from the class, it's parents, interfaces or traits.
        $methodComment = $this->commentAnalysis->getComment($refMethod, $refClass);
        if (empty($methodComment) === false) {
            $methodData[$messages->getHelp('metaComment')] = $methodComment;
        }

        // Get declaration place.
        $methodData[$messages->getHelp('metaDeclaredIn')] = $this->methodDeclaration
            ->retrieveDeclaration($refMethod);

        // Get the return type.
        /** @var ReturnType $returnType */
        $returnType = $this->pool->createClass(ReturnType::class);
        $methodData[$messages->getHelp('metaReturnType')] = $returnType->getComment($refMethod, $refClass);

        return $methodData;
    }

    /**
     * Retrieve the connector type.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection method.
     *
     * @return string
     *   The connector type,
     */
    protected function retrieveConnectorType(ReflectionMethod $reflectionMethod): string
    {
        if ($reflectionMethod->isStatic() === true) {
            return static::CONNECTOR_STATIC_METHOD;
        }

        return static::CONNECTOR_METHOD;
    }

    /**
     * Retrieve the parameter data from the reflection method.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection method.
     * @param array $methodData
     *   The method data so far.
     *
     * @return string
     *   The human-readable parameter list.
     */
    protected function retrieveParameters(ReflectionMethod $reflectionMethod, array &$methodData): string
    {
        $paramList = '';
        foreach ($reflectionMethod->getParameters() as $key => $reflectionParameter) {
            ++$key;
            $paramList .= $methodData[$this->pool->messages->getHelp('metaParamNo') . $key] = $this->pool
                ->codegenHandler
                ->parameterToString($reflectionParameter);
            // We add a comma to the parameter list, to separate them for a
            // better readability.
            $paramList .= ', ';
        }

        return $paramList;
    }

    /**
     * Get the declaration place of this method.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   Reflection of the method we are analysing.
     * @param \ReflectionClass $declaringClass
     *   Reflection of the class we are analysing
     *
     * @deprecated since 5.0.0
     *   Will be removed. Use the MethodDeclaration instead.
     * @codeCoverageIgnore
     *   We do not test deprecated methods.
     *
     * @return string
     *   The analysis result.
     */
    protected function getDeclarationPlace(ReflectionMethod $reflectionMethod, ReflectionClass $declaringClass): string
    {
        return $this->methodDeclaration->retrieveDeclaration($reflectionMethod);
    }

    /**
     * Getting the declaring keywords (and other stuff).
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the method that we are analysing.
     * @param \ReflectionClass $declaringClass
     *   The class in witch this method was declared.
     * @param \ReflectionClass $reflectionClass
     *   The class that we are currently analysing.
     *
     * @return string
     *   All declaring keywords + the info if this method was inherited.
     */
    protected function getDeclarationKeywords(
        ReflectionMethod $reflectionMethod,
        ReflectionClass $declaringClass,
        ReflectionClass $reflectionClass
    ): string {
        if ($reflectionMethod->isPublic() === true) {
            $result = ' public';
        } elseif ($reflectionMethod->isProtected() === true) {
            $result = ' protected';
        } else {
            $result = ' private';
        }

        if ($declaringClass->getName() !== $reflectionClass->getName()) {
            $result .= ' inherited';
        }

        if ($reflectionMethod->isStatic() === true) {
            $result .= ' static';
        }

        if ($reflectionMethod->isFinal() === true) {
            $result .= ' final';
        }

        return ltrim($result);
    }
}
