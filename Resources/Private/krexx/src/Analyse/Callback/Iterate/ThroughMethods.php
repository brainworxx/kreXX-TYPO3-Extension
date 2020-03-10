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
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Comment\Methods;
use Brainworxx\Krexx\Analyse\Model;
use ReflectionClass;
use ReflectionMethod;

/**
 * Methods analysis methods. :rolleyes:
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array data
 *   Array of reflection methods.
 * @uses \reflectionClass ref
 *   Reflection of the class we are analysing.
 */
class ThroughMethods extends AbstractCallback
{

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
        /** @var Methods $commentAnalysis */
        $commentAnalysis = $this->pool->createClass(Methods::class);

        // Deep analysis of the methods.
        /** @var \ReflectionMethod $refMethod */
        foreach ($this->parameters[static::PARAM_DATA] as $refMethod) {
            $methodData = [];

            // Get the comment from the class, it's parents, interfaces or traits.
            if (empty($methodComment = $commentAnalysis->getComment($refMethod, $refClass)) === false) {
                $methodData[static::META_COMMENT] = $methodComment;
            }

            // Get declaration place.
            $declaringClass = $refMethod->getDeclaringClass();
            $methodData[static::META_DECLARED_IN] = $this->getDeclarationPlace($refMethod, $declaringClass);

            // Update the reflection method, so an event subscriber can do
            // something with it.
            $this->parameters[static::PARAM_REF_METHOD] = $refMethod;

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
                    ->setIsPublic($refMethod->isPublic())
                    ->injectCallback($this->pool->createClass(ThroughMeta::class))
            ));
        }

        return $result;
    }

    /**
     * Retrieve the connector type.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection method.
     *
     * @return int
     *   The connector type,
     */
    protected function retrieveConnectorType(ReflectionMethod $reflectionMethod): int
    {
        if ($reflectionMethod->isStatic() === true) {
            return Connectors::STATIC_METHOD;
        }

        return Connectors::METHOD;
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
     *   The human readable parameter list.
     */
    protected function retrieveParameters(ReflectionMethod $reflectionMethod, array &$methodData): string
    {
        $paramList = '';
        foreach ($reflectionMethod->getParameters() as $key => $reflectionParameter) {
            ++$key;
            $paramList .= $methodData[static::META_PARAM_NO . $key] = $this->pool
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
     * @return string
     *   The analysis result.
     */
    protected function getDeclarationPlace(ReflectionMethod $reflectionMethod, ReflectionClass $declaringClass): string
    {
        if ($declaringClass->isInternal() === true) {
            return static::META_PREDECLARED;
        }

        $filename = $this->pool->fileService->filterFilePath($reflectionMethod->getFileName());
        if (empty($filename) === true) {
            // Not sure, if this is possible.
            return $this->pool->messages->getHelp(static::UNKNOWN_DECLARATION);
        }

        // If the filename of the $declaringClass and the $reflectionMethod differ,
        // we are facing a trait here.
        if ($reflectionMethod->getFileName() !== $declaringClass->getFileName()) {
            // There is no real clean way to get the name of the trait that we
            // are looking at.
            $traitName = ':: unable to get the trait name ::';
            $trait = $this->retrieveDeclaringReflection($reflectionMethod, $declaringClass);
            if ($trait !== false) {
                $traitName = $trait->getName();
            }

            return $filename . "\n" .
                static::META_IN_TRAIT . $traitName . "\n" .
                static::META_IN_LINE . $reflectionMethod->getStartLine();
        }

        return $filename . "\n" .
            static::META_IN_CLASS . $reflectionMethod->class . "\n" .
            static::META_IN_LINE . $reflectionMethod->getStartLine();
    }

    /**
     * Retrieve the declaration class reflection from traits.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the method we are analysing.
     * @param \ReflectionClass $declaringClass
     *   The original declaring class, the one with the traits.
     *
     * @return bool|\ReflectionClass
     *   false = unable to retrieve something.
     *   Otherwise return a reflection class.
     */
    protected function retrieveDeclaringReflection(ReflectionMethod $reflectionMethod, ReflectionClass $declaringClass)
    {
        // Get a first impression.
        if ($reflectionMethod->getFileName() === $declaringClass->getFileName()) {
            return $declaringClass;
        }

        // Go through the first layer of traits.
        // No need to recheck the availability for traits. This is done above.
        foreach ($declaringClass->getTraits() as $trait) {
            $result = $this->retrieveDeclaringReflection($reflectionMethod, $trait);
            if ($result !== false) {
                return $result;
            }
        }

        return false;
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
