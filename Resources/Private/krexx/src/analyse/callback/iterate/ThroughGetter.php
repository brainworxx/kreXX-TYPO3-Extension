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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Getter method analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
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
 * @uses \ReflectionClass $ref
 *   A reflection class of the object we are analysing.
 * @uses object $data
 *   The object we are currently analysing
 * @uses string $currentPrefix
 *   The current prefix we are analysing (get, is, has).
 *   Does not get set from the outside.
 */
class ThroughGetter extends AbstractCallback
{

    /**
     * Here we memorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected $deep = 0;

    /**
     * Class for the comment analysis.
     *
     * @var \Brainworxx\Krexx\Analyse\comment\Methods
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
        $this->commentAnalysis = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Methods');
    }

    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = $this->dispatchStartEvent();

        $this->parameters['currentPrefix'] = 'get';
        $output .= $this->goThroughMethodList($this->parameters['normalGetter']);

        $this->parameters['currentPrefix'] = 'is';
        $output .= $this->goThroughMethodList($this->parameters['isGetter']);

        $this->parameters['currentPrefix'] = 'has';
        return $output . $this->goThroughMethodList($this->parameters['hasGetter']);
    }

    /**
     * Iterating through a list of reflection methods.
     *
     * @param array $methodList
     *   The list of methods we are going through, consisting of \ReflectionMethod
     *
     * @return string
     *   The generated DOM.
     */
    protected function goThroughMethodList(array $methodList)
    {
        $output = '';

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($methodList as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($this->commentAnalysis->getComment($reflectionMethod, $this->parameters['ref']));

            /** @var Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($reflectionMethod->getName())
                ->addToJson('method comment', $comments);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic() === true) {
                $model->setConnectorType(Connectors::STATIC_METHOD);
            } else {
                $model->setConnectorType(Connectors::METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue(
                $reflectionMethod,
                $this->dispatchEventWithModel(
                    __FUNCTION__ . '::end',
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
    protected function retrievePropertyValue(\ReflectionMethod $reflectionMethod, Model $model)
    {
        $refProp = $this->getReflectionProperty($this->parameters['ref'], $reflectionMethod);
        $nothingFound = true;
        $value = null;

        if (empty($refProp) === false) {
            // We've got ourselves a possible result!
            $nothingFound = false;
            $refProp->setAccessible(true);
            $value = $refProp->getValue($this->parameters['data']);
            $model->setData($value);
            if ($value === null) {
                // A NULL value might mean that the values does not
                // exist, until the getter computes it.
                $model->addToJson('hint', $this->pool->messages->getHelp('getterNull'));
            }
        }

        // Give the plugins the opportunity to do something with the value, or
        // try to resolve it,  if nothing was found.
        // We also add the suff, that we were able to do so far.
        $this->parameters['additional'] = array(
            'nothingFound' => $nothingFound,
            'value' => $value,
            'refProperty' => $refProp,
            'refMethod' => $reflectionMethod
        );
        $this->dispatchEventWithModel(__FUNCTION__ . '::resolving', $model);

        if ($this->parameters['additional']['nothingFound'] === true) {
            // Found nothing  :-(
            // We literally have no info. We need to tell the user.
            $noInfoMessage = 'unknown';
            $model->setType($noInfoMessage)
                ->setNormal($noInfoMessage);
            // We render this right away, without any routing.
            return $this->pool->render->renderSingleChild($model);
        }

        return $this->pool->routing->analysisHub(
            $this->dispatchEventWithModel(
                __FUNCTION__ . '::end',
                $model
            )
        );
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * We try to guess the corresponding property in the class.
     *
     * @param \ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionProperty(\ReflectionClass $classReflection, \ReflectionMethod $reflectionMethod)
    {
        // We may be facing different writing styles.
        // The property we want from getMyProperty() should be named myProperty,
        // but we can not rely on this.
        // Old php 4 coders sometimes add a underscore before a protected
        // property.

        // We will check:
        // - myProperty
        // - _myProperty
        // - MyProperty
        // - _MyProperty
        // - myproperty
        // - _myproperty
        // - my_property
        // - _my_property

        // Get a first impression.
        $propertyName = $this->preparePropertyName($reflectionMethod);

        // myProperty
        // Most should be handled here.
        if ($classReflection->hasProperty($propertyName) === true) {
            return $classReflection->getProperty($propertyName);
        }

        // _myProperty
        $testName = '_' . $propertyName;
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // MyProperty
        $testName = ucfirst($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // _MyProperty
        $testName = '_' . ucfirst($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // myproperty
        $testName = strtolower($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // _myproperty
        $testName = '_' . strtolower($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // my_property
        $testName = $this->convertToSnakeCase($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // _my_property
        $testName = '_' . $this->convertToSnakeCase($propertyName);
        if ($classReflection->hasProperty($testName) === true) {
            return $classReflection->getProperty($testName);
        }

        // Time to do some deep stuff. We parse the sourcecode via regex!
        return $this->getReflectionPropertyDeep($classReflection, $reflectionMethod);
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
    protected function preparePropertyName(\ReflectionMethod $reflectionMethod)
    {
        $currentPrefix = $this->parameters['currentPrefix'];

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
     * @param \ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly associated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionPropertyDeep(\ReflectionClass $classReflection, \ReflectionMethod $reflectionMethod)
    {
        // Read the sourcecode into a string.
        $sourcecode = $this->pool->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );

        // Execute our search pattern.
        // Right now, we are trying to get to properties that way.
        // Later on, we may also try to parse deeper for stuff.
        foreach ($this->findIt(array('return $this->', ';'), $sourcecode) as $propertyName) {
            // Check if this is a property and return the first we find.
            $parentClass = $classReflection;
            while ($parentClass !== false) {
                // Chack if it was declared somewhere deeper in the
                // class stucture.
                if ($parentClass->hasProperty($propertyName) === true) {
                    return $parentClass->getProperty($propertyName);
                }
                $parentClass = $parentClass->getParentClass();
            }

            // Check if this is a method and go deeper!
            $methodName = rtrim($propertyName, '()');
            if ($classReflection->hasMethod($methodName) === true) {
                // We need to be careful not to goo too deep, we might end up
                // in a loop.
                ++$this->deep;
                if ($this->deep < 3) {
                    return $this->getReflectionProperty($classReflection, $classReflection->getMethod($methodName));
                }
            }
        }

        // Nothing?
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
    protected function convertToSnakeCase($string)
    {
        return strtolower(preg_replace(array('/([a-z\d])([A-Z])/', '/([^_])([A-Z][a-z])/'), '$1_$2', $string));
    }

    /**
     * Searching for stuff via regex.
     * Yay, dynamic regex stuff for fun and profit!
     *
     * @param array $searchArray
     *   The search definition.
     * @param string $haystack
     *   The haystack, obviously.
     *
     * @return array
     *   The findings.
     */
    protected function findIt(array $searchArray, $haystack)
    {

        // Defining our regex.
        $regex = '/(?<=###0###).*?(?=###1###)/';

        // Regex escaping our search stuff
        $searchArray[0] = $this->regexEscaping($searchArray[0]);
        $searchArray[1] = $this->regexEscaping($searchArray[1]);

        // Add the search stuff to the regex
        $regex = str_replace('###0###', $searchArray[0], $regex);
        $regex = str_replace('###1###', $searchArray[1], $regex);

        // Trigger the search.
        preg_match_all($regex, $haystack, $findings);

        // Return the file name as well as stuff from the path.
        return $findings[0];
    }

    /**
     * Escapes a string for regex usage.
     *
     * @param string $string
     *   The string we want to escape.
     *
     * @return string
     *   The escaped string.
     */
    protected function regexEscaping($string)
    {
        return str_replace(
            array('.', '/', '(', ')', '<', '>', '$'),
            array('\.', '\/', '\(', '\)', '\<', '\>', '\$'),
            $string
        );
    }
}
