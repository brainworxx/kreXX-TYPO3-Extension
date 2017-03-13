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
use Brainworxx\Krexx\Service\Code\Connectors;
use Brainworxx\Krexx\Service\Misc\File;
use Brainworxx\Krexx\Service\Factory\Pool;

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
     * The file service, used for reading sourcecode.
     *
     * @var File
     */
    protected $fileService;

    /**
     * Here we momorize how deep we are inside the current deep analysis.
     *
     * @var int
     */
    protected $deep = 0;

    /**
     * Injection the pool and getting  the file service.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->fileService = $this->pool->createClass('Brainworxx\\Krexx\\Service\\Misc\\File');
    }

    /**
     * Try to get the possible result of all getter methods.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $output = '';
        /** @var \reflectionClass $ref */
        $ref = $this->parameters['ref'];

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($this->parameters['methodList'] as $reflectionMethod) {
            $refProp = $this->getReflectionProperty($ref, $reflectionMethod);

            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($this
                ->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Methods')
                ->getComment($reflectionMethod, $ref));

            /** @var \Brainworxx\Krexx\Analyse\Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($reflectionMethod->getName())
                ->addToJson('method comment', $comments);

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic()) {
                $model->setConnectorType(Connectors::STATIC_METHOD);
            } else {
                $model->setConnectorType(Connectors::METHOD);
            }

            if (empty($refProp)) {
                // Found nothing  :-(
                $value = $this->pool->messages->getHelp('unknownValue');

                // We literally have no info. We need to tell the user.
                $model->setNormal('unknown')
                    ->setType('unknown')
                    ->hasExtras();
            } else {
                // We've got ourselves a possible result!
                $refProp->setAccessible(true);
                $value = $refProp->getValue($this->parameters['data']);
            }
            $model->setData($value);

            if (empty($refProp)) {
                // We render this right away, without any routing.
                $output .= $this->pool->render->renderSingleChild($model);
            } else {
                if (is_null($value)) {
                    // A NULL value might mean that the values does not
                    // exist, until the getter computes it.
                    $model->addToJson('hint', $this->pool->messages->getHelp('getterNull'));
                }
                $output .= $this->pool
                    ->createClass('Brainworxx\\Krexx\\Analyse\\Routing\\Routing')
                    ->analysisHub($model);
            }
        }

        return $output;
    }

    /**
     * We try to coax the reflection property from the current object.
     *
     * @param \ReflectionClass $classReflection
     *   The reflection class oof the object we are analysing.
     * @param \ReflectionMethod $reflectionMethod
     *   The reflection ot the method of which we want to coax the result from
     *   the class or sourcecode.
     *
     * @return \ReflectionProperty|null
     *   Either the reflection of a possibly accosiated Property, or null to
     *   indicate that we have found nothing.
     */
    protected function getReflectionProperty(\ReflectionClass $classReflection, \ReflectionMethod $reflectionMethod)
    {
        // We may be facing different writing styles.
        // The property we want from getMyProperty() should be named myProperty,
        // but we can not rely on this.
        // Old php 4 coders sometimes add a underscore before a protectred
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

        // Still here?!?
        // Time to do some deep stuff. We parse the sourcecode via regex!
         // Read the sourcecode into a string.
        $sourcecode = $this->fileService->readFile(
            $reflectionMethod->getFileName(),
            $reflectionMethod->getStartLine(),
            $reflectionMethod->getEndLine()
        );
        // Execute our search pattern.
        // Right now, we are trying to get to properties that way.
        // Later on, we may also try to parse deeper for stuff.
        $pattern = array('return $this->', ';');
        $findings = $this->findIt($pattern, $sourcecode);

        foreach ($findings as $propertyName) {
            // Check if this is a property and return the first we find.
            if ($classReflection->hasProperty($propertyName)) {
                return $classReflection->getProperty($propertyName);
            }
            // Check if this is a method and go deeper!
            $methodName = rtrim($propertyName, '()');
            if ($classReflection->hasMethod($methodName)) {
                // We need to be carefull not to goo too deep, we might end up
                // in a loop.
                $this->deep++;
                if ($this->deep < 3) {
                    return $this->getReflectionProperty($classReflection, $classReflection->getMethod($methodName));
                }
            }
        }

        // Still nothing? Return null, to tell the main method that we were
        // unable to get any info.
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
    protected function findIt($searchArray, $haystack)
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
        $result = array();
        foreach ($findings[0] as $name) {
            $result[] =  $name;
        }
        return $result;
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
