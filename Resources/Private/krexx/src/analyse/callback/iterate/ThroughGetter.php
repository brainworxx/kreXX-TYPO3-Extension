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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

/**
 * Getter method analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Iterate
 *
 * @uses array methodList
 *   The list of all reflection methods we are analysing
 * @uses \ReflectionClass $ref
 *   A reflection class of the object we are analysing.
 * @uses object $data
 *   The object we are currently analysing
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
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        /** @var \Brainworxx\Krexx\Analyse\comment\Methods $commentAnalysis */
        $commentAnalysis = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Methods');

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($this->parameters['methodList'] as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($commentAnalysis->getComment($reflectionMethod, $this->parameters['ref']));

            /** @var Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($reflectionMethod->getName())
                ->addToJson('method comment', $comments);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic()) {
                $model->setConnectorType(Connectors::STATIC_METHOD);
            } else {
                $model->setConnectorType(Connectors::METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue($reflectionMethod, $model);
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

        if (empty($refProp)) {
            // Found nothing  :-(
            // We literally have no info. We need to tell the user.
            $noInfoMessage = 'unknown';
            $model->setType($noInfoMessage)
                ->setNormal($noInfoMessage);
            // We render this right away, without any routing.
            return $this->pool->render->renderSingleChild($model);
        }
        // We've got ourselves a possible result!
        $refProp->setAccessible(true);
        $value = $refProp->getValue($this->parameters['data']);
        $model->setData($value);
        if (is_null($value)) {
            // A NULL value might mean that the values does not
            // exist, until the getter computes it.
            $model->addToJson('hint', $this->pool->messages->getHelp('getterNull'));
        }
        return $this->pool->routing->analysisHub($model);
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

        // Get the name and remove the 'get'.
        $getterName = $reflectionMethod->getName();
        if (strpos($getterName, 'get') === 0) {
            $getterName = substr($getterName, 3);
        }
        if (strpos($getterName, '_get') === 0) {
            $getterName = substr($getterName, 4);
        }


        // myProperty
        $propertyName = lcfirst($getterName);
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // _myProperty
        $propertyName = '_' . $propertyName;
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // MyProperty
        $propertyName = ucfirst($getterName);
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // _MyProperty
        $propertyName = '_' . $propertyName;
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // myproperty
        $propertyName = strtolower($getterName);
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // _myproperty
        $propertyName = '_' . $propertyName;
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // my_property
        $propertyName = $this->convertToSnakeCase($getterName);
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // _my_property
        $propertyName = '_' . $propertyName;
        if ($classReflection->hasProperty($propertyName)) {
            return $classReflection->getProperty($propertyName);
        }

        // Time to do some deep stuff. We parse the sourcecode via regex!
        return $this->getReflectionPropertyDeep($classReflection, $reflectionMethod);
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
            if ($classReflection->hasProperty($propertyName)) {
                return $classReflection->getProperty($propertyName);
            }
            // Check if this is a method and go deeper!
            $methodName = rtrim($propertyName, '()');
            if ($classReflection->hasMethod($methodName)) {
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
