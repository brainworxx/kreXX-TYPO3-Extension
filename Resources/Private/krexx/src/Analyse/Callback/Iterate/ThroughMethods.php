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
use Brainworxx\Krexx\Analyse\Code\Connectors;

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
     * {@inheritdoc}
     */
    protected static $eventPrefix = 'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods';

    /**
     * Simply start to iterate through the methods.
     *
     * @return string
     *   The rendered markup.
     */
    public function callMe()
    {
        $result = $this->dispatchStartEvent();
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        $commentAnalysis = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Methods');

        // Deep analysis of the methods.
        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($this->parameters[static::PARAM_DATA] as $reflectionMethod) {
            $methodData = array();

            // Get the comment from the class, it's parents, interfaces or traits.
            $methodComment = $commentAnalysis->getComment($reflectionMethod, $reflectionClass);
            if (empty($methodComment) === false) {
                $methodData['comments'] = $methodComment;
            }

            // Get declaration place.
            $declaringClass = $reflectionMethod->getDeclaringClass();
            $methodData['declared in'] = $this->getDeclarationPlace($reflectionMethod, $declaringClass);

            // Get parameters.
            $paramList = '';
            foreach ($reflectionMethod->getParameters() as $key => $reflectionParameter) {
                ++$key;
                $paramList .= $methodData['Parameter #' . $key] = $this->pool
                    ->codegenHandler
                    ->parameterToString($reflectionParameter);
                // We add a comma to the parameter list, to separate them for a
                // better readability.
                $paramList .= ', ';
            }

            // Get declaring keywords.
            $methodData['declaration keywords'] = $this->getDeclarationKeywords(
                $reflectionMethod,
                $declaringClass,
                $reflectionClass
            );

            // Get the connector.
            if ($reflectionMethod->isStatic() === true) {
                $connectorType = Connectors::STATIC_METHOD;
            } else {
                $connectorType = Connectors::METHOD;
            }

            // Update the reflection method, so an event subscriber can do
            // something with it.
            $this->parameters[static::PARAM_REF_METHOD] = $reflectionMethod;

            // Render it!
            $result .= $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setName($reflectionMethod->name)
                        ->setType($methodData['declaration keywords'] . static::TYPE_METHOD)
                        ->setConnectorType($connectorType)
                        // Remove the ',' after the last char.
                        ->setConnectorParameters(trim($paramList, ', '))
                        ->addParameter(static::PARAM_DATA, $methodData)
                        ->setIsPublic($reflectionMethod->isPublic())
                        ->injectCallback(
                            $this->pool->createClass(
                                'Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis'
                            )
                        )
                )
            );
        }

        return $result;
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
    protected function getDeclarationPlace(\ReflectionMethod $reflectionMethod, \ReflectionClass $declaringClass)
    {
        $filename = $this->pool->fileService->filterFilePath($reflectionMethod->getFileName());
        if (empty($filename) === true) {
            return static::UNKNOWN_DECLARATION;
        }

        // If the filename of the $declaringClass and the $reflectionMethod differ,
        // we are facing a trait here.
        if ($reflectionMethod->getFileName() !== $declaringClass->getFileName() &&
            method_exists($declaringClass, 'getTraits')
        ) {
            // There is no real clean way to get the name of the trait that we
            // are looking at.
            $traitName = ':: unable to get the trait name ::';
            $trait = $this->retrieveDeclaringReflection($reflectionMethod, $declaringClass);
            if ($trait !== false) {
                $traitName = $trait->getName();
            }

            return $filename . "\n" .
                'in trait: ' . $traitName . "\n" .
                'in line: ' . $reflectionMethod->getStartLine();
        } else {
            return $filename . "\n" .
                'in class: ' . $reflectionMethod->class . "\n" .
                'in line: ' . $reflectionMethod->getStartLine();
        }
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
     *   false = unable to retrieve someting.
     *   Otherwise return a reflection class.
     */
    protected function retrieveDeclaringReflection(
        \ReflectionMethod $reflectionMethod,
        \ReflectionClass $declaringClass
    ) {
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
        \ReflectionMethod $reflectionMethod,
        \ReflectionClass $declaringClass,
        \ReflectionClass $reflectionClass
    ) {
        $result = '';

        if ($reflectionMethod->isPrivate() === true) {
            $result .= ' private';
        } elseif ($reflectionMethod->isProtected() === true) {
            $result .= ' protected';
        } elseif ($reflectionMethod->isPublic() === true) {
            $result .= ' public';
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

        return trim($result);
    }
}
