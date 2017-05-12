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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Flection;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;

/**
 * Object analysis methods.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analysis
 *
 * @uses object data
 *   The class we are analysing.
 * @uses string name
 *   The key of the class from the object/array holding this one.
 */
class Objects extends AbstractCallback
{
    /**
     * Starts the dump of an object.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $data = $this->parameters['data'];
        $name = $this->parameters['name'];
        $output = $this->pool->render->renderSingeChildHr();

        $ref = new \ReflectionClass($data);

        // Dumping public properties.
        $output .= $this->getPublicProperties($ref);

        // Dumping getter methods.
        if ($this->pool->config->getSetting('analyseGetter')) {
            $output .= $this->getAllGetterData($ref, $data);
        }

        // Dumping protected properties.
        if ($this->pool->config->getSetting('analyseProtected') ||
            $this->pool->scope->isInScope()) {
            $output .= $this->getProtectedProperties($ref);
        }

        // Dumping private properties.
        if ($this->pool->config->getSetting('analysePrivate') ||
            $this->pool->scope->isInScope()) {
            $output .= $this->getPrivateProperties($ref);
        }

        // Dumping class constants.
        if ($this->pool->config->getSetting('analyseConstants')) {
            $output .= $this->getReflectionConstantsData($ref);
        }

        // Dumping all methods.
        $output .= $this->getMethodData($ref);

        // Dumping traversable data.
        if ($this->pool->config->getSetting('analyseTraversable')) {
            $output .= $this->getTraversableData($data, $name);
        }

        // Dumping all configured debug functions.
        $output .= $this->pollAllConfiguredDebugMethods($data);

        // Adding a HR for a better readability.
        $output .= $this->pool->render->renderSingeChildHr();
        return $output;
    }

    /**
     * Dumping all private properties.
     *
     * @param \ReflectionClass $ref
     *   The reflection of the class we are currently analysing.
     * @return string
     *   The generated HTML markup
     */
    protected function getPrivateProperties(\ReflectionClass $ref)
    {
        $output = '';
        $data = $this->parameters['data'];
        $refProps = array();
        $reflectionClass = $ref;

        // The main problem here is, that you only get the private properties of
        // the current class, but not the inherited private properties.
        // We need to get all parent classes and then poll them for private
        // properties to get the whole picture.
        do {
            $refProps = array_merge($refProps, $reflectionClass->getProperties(\ReflectionProperty::IS_PRIVATE));
            // And now for the parent class.
            // Inherited private properties are not accessible from inside
            // the class. We will only dump them, if we are analysing private
            // properties.
            if ($this->pool->config->getSetting('analysePrivate')) {
                $reflectionClass = $reflectionClass->getParentClass();
            } else {
                // This should break the do while.
                $reflectionClass = false;
            }
        } while (is_object($reflectionClass));

        usort($refProps, array($this, 'sortingCallback'));
        if (!empty($refProps)) {
            $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Private properties');
        }

        return $output;
    }

    /**
     * Dump all protected properties.
     *
     * @param \ReflectionClass $ref
     *   A reflection of the class we are analysing
     *
     * @return string
     *   The generated HTML markup
     */
    protected function getProtectedProperties(\ReflectionClass $ref)
    {
        $output = '';
        $data = $this->parameters['data'];

        $refProps = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
        usort($refProps, array($this, 'sortingCallback'));

        if (!empty($refProps)) {
            $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Protected properties');
        }

        return $output;
    }

    /**
     * Dump all public properties.
     *
     * @param \ReflectionClass $ref
     *   A reflection of the class we are analysing
     *
     * @return string
     *   The generated HTML markup.
     */
    protected function getPublicProperties(\ReflectionClass $ref)
    {
        $output = '';
        $data = $this->parameters['data'];

        $refProps = $ref->getProperties(\ReflectionProperty::IS_PUBLIC);

        // Adding undeclared public properties to the dump.
        // Those are properties which are not visible with
        // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        // but are in get_object_vars();
        // 1. Make a list of all properties
        // 2. Remove those that are listed in
        // $ref->getProperties(\ReflectionProperty::IS_PUBLIC);
        // What is left are those special properties that were dynamically
        // set during runtime, but were not declared in the class.
        foreach ($refProps as $refProp) {
            $publicProps[$refProp->name] = $refProp->name;
        }
        foreach (get_object_vars($data) as $key => $value) {
            if (!isset($publicProps[$key])) {
                $refProps[] = new Flection($value, $key, $ref);
            }
        }

        if (!empty($refProps)) {
            usort($refProps, array($this, 'sortingCallback'));
            $output .= $this->getReflectionPropertiesData($refProps, $ref, $data, 'Public properties');
            // Adding a HR to reflect that the following stuff are not public
            // properties anymore.
            $output .= $this->pool->render->renderSingeChildHr();
        }

        return $output;
    }

    /**
     * Sorting callback for usort utilizing reflection properties.
     *
     * @param \ReflectionProperty $a
     *   A string we want to sort.
     * @param \ReflectionProperty $b
     *   Another string for comparison
     *
     * @return int
     */
    protected function sortingCallback($a, $b)
    {
        return strcmp($a->name, $b->name);
    }

    /**
     * Decides which methods we want to analyse and then starts the dump.
     *
     * @param \ReflectionClass $ref
     *   The object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected function getMethodData(\ReflectionClass $ref)
    {
        // Dumping all methods but only if we have any.
        $protected = array();
        $private = array();

        $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        if ($this->pool->config->getSetting('analyseProtectedMethods') ||
            $this->pool->scope->isInScope()) {
            $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
        }

        if ($this->pool->config->getSetting('analysePrivateMethods') ||
            $this->pool->scope->isInScope()) {
            $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
        }

        // Is there anything to analyse?
        $methods = array_merge($public, $protected, $private);
        if (!empty($methods)) {
            // We need to sort these alphabetically.
            $sortingCallback = function ($a, $b) {
                return strcmp($a->name, $b->name);
            };
            usort($methods, $sortingCallback);

            return $this->pool->render->renderExpandableChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName('Methods')
                ->setType('class internals')
                ->addParameter('data', $methods)
                ->addParameter('ref', $ref)
                ->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethods')
                )
            );
        }
        return '';
    }

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of cause, not stop a possible fatal in the function
     * itself.
     *
     * @param object $data
     *   The object we are analysing.
     *
     * @return string
     *   The generated markup.
     */
    protected function pollAllConfiguredDebugMethods($data)
    {
        $output = '';

        $funcList = explode(',', $this->pool->config->getSetting('debugMethods'));
        foreach ($funcList as $funcName) {
            if (is_callable(array(
                    $data,
                    $funcName,
                )) && $this->pool->config->security->isAllowedDebugCall($data, $funcName)
            ) {
                $foundRequired = false;
                // We need to check if this method actually exists. Just because it is
                // callable does not mean it exists!
                if (method_exists($data, $funcName)) {
                    // We need to check if the callable function requires any parameters.
                    // We will not call those, because we simply can not provide them.
                    // Interestingly, some methods of a class are callable, but are not
                    // implemented. This means, that when I try to get a reflection,
                    // it will result in a WSOD.
                    $ref = new \ReflectionMethod($data, $funcName);
                    $params = $ref->getParameters();
                    foreach ($params as $param) {
                        if (!$param->isOptional()) {
                            // We've got a required parameter!
                            // We will not call this one.
                            $foundRequired = true;
                        }
                    }
                    unset($ref);
                } else {
                    // It's callable, but does not exist. Looks like a __call fallback.
                    // We will not poll it for data.
                    $foundRequired = true;
                }

                if (!$foundRequired) {
                    // Add a try to prevent the hosting CMS from doing something stupid.
                    try {
                        // We need to deactivate the current error handling to
                        // prevent the host system to do anything stupid.
                        set_error_handler(function () {
                            // Do nothing.
                        });
                        $result = $data->$funcName();
                    } catch (\Exception $e) {
                        // Do nothing.
                    }

                    // Reactivate whatever error handling we had previously.
                    restore_error_handler();

                    if (isset($result)) {
                        $output .= $this->pool->render->renderExpandableChild(
                            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                                ->setName($funcName)
                                ->setType('debug method')
                                ->setNormal('. . .')
                                ->setHelpid($funcName)
                                ->setConnectorType(Connectors::METHOD)
                                ->addParameter('data', $result)
                                ->injectCallback(
                                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Debug')
                                )
                        );
                        unset($result);
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Dumps all available traversable data.
     *
     * @param \Iterator $data
     *   The object we are analysing.
     * @param string $name
     *   The name of the object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    protected function getTraversableData($data, $name)
    {
        if (is_a($data, 'Traversable')) {
            // Special Array Access here, resulting in multiline source generation.
            // We need to generate something like:
            // $kresult = iterator_to_array($data);
            // $kresult = $kresult[5];
            // So we tell the callback to to that.
            $multiline = true;

            // Normal ArrayAccess, direct access to the array. Nothing special
            if (is_a($data, 'ArrayAccess')) {
                $multiline = false;
            }

            // SplObject pool use the object as keys, so we need some
            // multiline stuff!
            if (is_a($data, 'SplObjectStorage')) {
                $multiline = true;
            }

            // Add a try to prevent the hosting CMS from doing something stupid.
            try {
                // We need to deactivate the current error handling to
                // prevent the host system to do anything stupid.
                set_error_handler(function () {
                    // Do nothing.
                });
                $parameter = iterator_to_array($data);
            } catch (\Exception $e) {
                // Do nothing.
            }

            // Reactivate whatever error handling we had previously.
            restore_error_handler();

            if (isset($parameter)) {
                // Check memory and runtime.
                if (!$this->pool->emergencyHandler->checkEmergencyBreak()) {
                    return '';
                }
                // Check nesting level
                $this->pool->emergencyHandler->upOneNestingLevel();
                if ($this->pool->emergencyHandler->checkNesting()) {
                    return '';
                }
                $result = $this->pool->render->renderExpandableChild(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setName($name)
                        ->setType('Foreach')
                        ->setNormal('Traversable Info')
                        ->addParameter('data', $parameter)
                        ->addParameter('multiline', $multiline)
                        ->injectCallback(
                            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray')
                        )
                );
                $this->pool->emergencyHandler->downOneNestingLevel();
                return $result;
            }
        }
        // Still here?!? Return an empty string.
        return '';
    }

    /**
     * Dumps the constants of a class,
     *
     * @param \ReflectionClass $ref
     *   The already generated reflection of said class
     *
     * @return string
     *   The generated markup.
     */
    protected function getReflectionConstantsData(\ReflectionClass $ref)
    {
        // This is actually an array, we ara analysing. But We do not want to render
        // an array, so we need to process it like the return from an iterator.
        $refConst = $ref->getConstants();

        if (!empty($refConst)) {
            // We need to set al least one connector here to activate
            // code generation, even if it is a space.
            // We've got some values, we will dump them.
            $classname = $ref->getName();

            return $this->pool->render->renderExpandableChild(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                    ->setName('Constants')
                    ->setType('class internals')
                    ->addParameter('data', $refConst)
                    ->addParameter('classname', $classname)
                    ->injectCallback(
                        $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughConstants')
                    )
            );
        }

        // Nothing to see here, return an empty string.
        return '';
    }

    /**
     * Gets the properties from a reflection property of the object.
     *
     * @param array $refProps
     *   The list of the reflection properties.
     * @param \ReflectionClass $ref
     *   The reflection of the object we are currently analysing.
     * @param object $data
     *   The object we are currently analysing.
     * @param string $label
     *   The additional part of the template file.
     *
     * @return string
     *   The generated markup.
     */
    protected function getReflectionPropertiesData(array $refProps, \ReflectionClass $ref, $data, $label)
    {
        // We are dumping public properties direct into the main-level, without
        // any "abstraction level", because they can be accessed directly.
        /** @var Model $model */
        $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
            ->addParameter('data', $refProps)
            ->addParameter('ref', $ref)
            ->addParameter('orgObject', $data)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughProperties')
            );

        if (strpos(strtoupper($label), 'PUBLIC') === false) {
            // Protected or private properties.
            $model->setName($label)
                ->setType('class internals');
            return $this->pool->render->renderExpandableChild($model);
        } else {
            // Public properties.
            // We render them directly in the object "root", so we call
            // the render directly.
            // $model->setAdditional($label);
            return $model->renderMe();
        }
    }

    /**
     * Dump the possible result of all getter methods
     *
     * @param \ReflectionClass $ref
     *
     * @param object $data
     *   The object we are currently analysing.
     *
     * @return string
     *   The generated markup.
     */
    protected function getAllGetterData(\ReflectionClass $ref, $data)
    {
        // Get all public methods.
        $methodList = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

        if ($this->pool->scope->isInScope()) {
            // Looks like we also need the protected and private methods.
            $methodList = array_merge(
                $methodList,
                $ref->getMethods(\ReflectionMethod::IS_PRIVATE | \ReflectionMethod::IS_PROTECTED)
            );
        }

        if (!empty($methodList)) {
            // Filter them.
            foreach ($methodList as $key => $method) {
                if (strpos($method->getName(), 'get') === 0) {
                    // We only dump those that have no parameters.
                    $parameters = $method->getParameters();
                    if (!empty($parameters)) {
                        unset($methodList[$key]);
                    }
                } else {
                    unset($methodList[$key]);
                }
            }

            if (!empty($methodList)) {
                // Got some getters right here.

                // We need to set al least one connector here to activate
                // code generation, even if it is a space.
                return $this->pool->render->renderExpandableChild(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                        ->setName('Getter')
                        ->setType('class internals')
                        ->setHelpid('getterHelpInfo')
                        ->addParameter('ref', $ref)
                        ->addParameter('methodList', $methodList)
                        ->addParameter('data', $data)
                        ->injectCallback(
                            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughGetter')
                        )
                );
            }
        }

        // There are no getter methods in here.
        return '';

    }
}
