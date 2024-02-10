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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

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
     * Stuff we need to escape in a regex.
     *
     * @deprecated
     *   Since 5.0.0. Will be removed.
     *
     * @var string[]
     */
    protected $regexEscapeFind = ['.', '/', '(', ')', '<', '>', '$'];

    /**
     * Stuff the escaped regex stuff.
     *
     * @deprecated
     *   Since 5.0.0. Will be removed.
     *
     * @var string[]
     */
    protected $regexEscapeReplace = ['\.', '\/', '\(', '\)', '\<', '\>', '\$'];

    /**
     * Here we memorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected $deep = 0;

    /**
     * Class for the comment analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\Comment\Methods
     */
    protected $commentAnalysis;

    /**
     * Injects the pool and initializes the comment analysis.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        $this->commentAnalysis = $this->pool->createClass(Methods::class);
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
            $comments = nl2br($this->commentAnalysis->getComment(
                $reflectionMethod,
                $this->parameters[static::PARAM_REF]
            ));

            /** @var Model $model */
            $model = $this->pool->createClass(Model::class)
                ->setName($reflectionMethod->getName())
                ->setCodeGenType(static::CODEGEN_TYPE_PUBLIC)
                ->addToJson($this->pool->messages->getHelp('metaMethodComment'), $comments);

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
        /** @var \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass */
        $reflectionClass = $this->parameters[static::PARAM_REF];
        try {
            $refProp = $this->getReflectionProperty($reflectionClass, $reflectionMethod);
        } catch (ReflectionException $e) {
            // We ignore this one.
            return '';
        }

        $this->prepareResult($reflectionClass, $reflectionMethod, $refProp, $model);
        $this->dispatchEventWithModel(__FUNCTION__ . '::resolving', $model);

        if ($this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND]) {
            // Found nothing  :-(
            // We literally have no info. We need to tell the user.
            // We render this right away, without any routing.
            return $this->pool->render->renderExpandableChild(
                $this->dispatchEventWithModel(
                    __FUNCTION__ . static::EVENT_MARKER_END,
                    $model->setType(static::TYPE_UNKNOWN)->setNormal(static::TYPE_UNKNOWN)
                )
            );
        }

        return $this->pool->routing->analysisHub(
            $this->dispatchEventWithModel(
                __FUNCTION__ . static::EVENT_MARKER_END,
                $model
            )
        );
    }

    /**
     * Prepare the retrieved result for output.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $reflectionClass
     *   The reflection class of the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection of the getter where we want to retrieve the return value
     * @param \ReflectionProperty|null $refProp
     *   The reflection of the property that it may return.
     * @param \Brainworxx\Krexx\Analyse\Model $model
     *   The model so far.
     */
    protected function prepareResult(
        ReflectionClass $reflectionClass,
        ReflectionMethod $reflectionMethod,
        ?ReflectionProperty $refProp,
        Model $model
    ): void {
        $this->parameters[static::PARAM_ADDITIONAL] = [
            static::PARAM_NOTHING_FOUND => true,
            static::PARAM_VALUE => null,
            static::PARAM_REFLECTION_PROPERTY => null,
            static::PARAM_REFLECTION_METHOD => $reflectionMethod
        ];

        if ($refProp === null) {
            return;
        }

        // We've got ourselves a possible result.
        $value = $reflectionClass->retrieveValue($refProp);
        // If we are handling a getter, we retrieve the value itself
        // If we are handling an is'er of has'er, we return a boolean.
        if ($this->parameters[static::CURRENT_PREFIX] !== 'get' && !is_bool($value)) {
            $value = $value !== null;
        }
        $model->setData($value);

        if ($value === null) {
            // A NULL value might mean that the values does not
            // exist, until the getter computes it.
            $model->addToJson(
                $this->pool->messages->getHelp('metaHint'),
                $this->pool->messages->getHelp('getterNull')
            );
        }

        // Give the plugins the opportunity to do something with the value, or
        // try to resolve it, if nothing was found.
        // We also add the stuff, that we were able to do so far.
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_NOTHING_FOUND] = false;
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_VALUE] = $value;
        $this->parameters[static::PARAM_ADDITIONAL][static::PARAM_REFLECTION_PROPERTY] = $refProp;
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * We try to guess the corresponding property in the class.
     *
     * @param ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionProperty(
        ReflectionClass $classReflection,
        ReflectionMethod $reflectionMethod
    ): ?ReflectionProperty {
        // We may be facing different writing styles.
        // The property we want from getMyProperty() should be named myProperty,
        // but we can not rely on this.
        // Old php 4 coders sometimes add an underscore before a protected
        // property.

        // We will check:
        $names = [
            // myProperty
            $propertyName = $this->preparePropertyName($reflectionMethod),
            // _myProperty
            '_' . $propertyName,
            // MyProperty
            ucfirst($propertyName),
            // _MyProperty
            '_' . ucfirst($propertyName),
            // myproperty
            strtolower($propertyName),
            // _myproperty
            '_' . strtolower($propertyName),
            // my_property
            $this->convertToSnakeCase($propertyName),
            // _my_property
            '_' . $this->convertToSnakeCase($propertyName)
        ];

        foreach ($names as $name) {
            if ($classReflection->hasProperty($name)) {
                return $classReflection->getProperty($name);
            }
        }

        // Time to do some deep stuff. We parse the sourcecode via regex!
        return $reflectionMethod->isInternal() ? null :
            $this->getReflectionPropertyDeep($classReflection, $reflectionMethod);
    }

    /**
     * Get a first impression ot the possible property name for the getter.
     *
     * @param \ReflectionMethod $reflectionMethod
     *   A reflection of the getter method we are analysing.
     *
     * @return string
     *   The first impression of the property name.
     */
    protected function preparePropertyName(ReflectionMethod $reflectionMethod): string
    {
        $currentPrefix = $this->parameters[static::CURRENT_PREFIX];

         // Get the name and remove the 'get' . . .
        $getterName = $reflectionMethod->getName();
        if (strpos($getterName, $currentPrefix) === 0) {
            return lcfirst(substr($getterName, strlen($currentPrefix)));
        }

        // . . .  or the '_get'.
        if (strpos($getterName, '_' . $currentPrefix) === 0) {
            return lcfirst(substr($getterName, strlen($currentPrefix) + 1));
        }

        // Still here?!? At least make the first letter lowercase.
        return lcfirst($getterName);
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * This time we are analysing the source code itself!
     *
     * @param ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionPropertyDeep(
        ReflectionClass $classReflection,
        ReflectionMethod $reflectionMethod
    ): ?ReflectionProperty {
        // Read the sourcecode into a string.
        $sourcecode = $this->pool->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );

        // Execute our search pattern.
        // Right now, we are trying to get to properties that way.
        // Later on, we may also try to parse deeper for stuff.
        $result = null;
        foreach ($this->findIt(['return $this->', ';'], $sourcecode) as $propertyName) {
            // Check if this is a property and return the first we find.
            $result = $this->analyseRegexResult($propertyName, $classReflection);
            if ($result !== null) {
                break;
            }
        }

        // Nothing?
        return $result;
    }

    /**
     * Analyse tone of the regex findings.
     *
     * @param string $propertyName
     *   The name of the property.
     * @param ReflectionClass $classReflection
     *   The current class reflection
     *
     * @throws \ReflectionException
     *
     * @return \ReflectionProperty|null
     *   The reflection of the property, or null if we found nothing.
     */
    protected function analyseRegexResult(string $propertyName, ReflectionClass $classReflection): ?ReflectionProperty
    {
        // Check if this is a property and return the first we find.
        $result = $this->retrievePropertyByName($propertyName, $classReflection);
        if ($result !== null) {
            return $result;
        }

        // Check if this is a method and go deeper!
        $methodName = rtrim($propertyName, '()');
        if ($classReflection->hasMethod($methodName) && ++$this->deep < 3) {
            // We need to be careful not to goo too deep, we might end up
            // in a loop.
            return $this->getReflectionProperty($classReflection, $classReflection->getMethod($methodName));
        }

        return null;
    }

    /**
     * Retrieve the property by name from a reflection class.
     *
     * @param string $propertyName
     *   The name of the property.
     * @param \ReflectionClass $parentClass
     *   The class where it may be located.
     *
     * @return \ReflectionProperty|null
     *   The reflection property, if found.
     */
    protected function retrievePropertyByName(string $propertyName, \ReflectionClass $parentClass): ?ReflectionProperty
    {
        while ($parentClass !== false) {
            // Check if it was declared somewhere deeper in the
            // class structure.
            if ($parentClass->hasProperty($propertyName)) {
                return $parentClass->getProperty($propertyName);
            }
            $parentClass = $parentClass->getParentClass();
        }

        return null;
    }

    /**
     * Converts a camel case string to snake case.
     *
     * @author Syone
     * @see https://stackoverflow.com/questions/1993721/how-to-convert-camelcase-to-camel-case/35719689#35719689
     *
     * @param string $string
     *   The string we want to transform into snake case
     *
     * @return string
     *   The de-camelized string.
     */
    protected function convertToSnakeCase(string $string): string
    {
        return strtolower(preg_replace(['/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'], '$1_$2', $string));
    }

    /**
     * Searching for stuff via regex.
     * Yay, dynamic regex stuff for fun and profit!
     *
     * @param string[] $searchArray
     *   The search definition.
     * @param string $haystack
     *   The haystack, obviously.
     *
     * @return string[]|int[]
     *   The findings.
     */
    protected function findIt(array $searchArray, string $haystack): array
    {
        $findings = [];
        preg_match_all(
            str_replace(
                ['###0###', '###1###'],
                [preg_quote($searchArray[0]), preg_quote($searchArray[1])],
                '/(?<=###0###).*?(?=###1###)/'
            ),
            $haystack,
            $findings
        );

        // Return the file name as well as stuff from the path.
        return $findings[0];
    }

    /**
     * Escapes a string for regex usage.
     *
     * @param string $string
     *   The string we want to escape.
     *
     * @deprecated
     *   Since 5.0.0. Will be removed. Use preg_quote().
     *
     * @codeCoverageIgnore
     *   We do not test deprecated methods.
     *
     * @return string
     *   The escaped string.
     */
    protected function regexEscaping(string $string): string
    {
        return str_replace(
            $this->regexEscapeFind,
            $this->regexEscapeReplace,
            $string
        );
    }
}
