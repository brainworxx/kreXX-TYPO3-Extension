<?php
/**
 * @file
 *   Object analysis functions for kreXX
 *   kreXX: Krumo eXXtended
 *
 *   This is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Analysis\Objects;

use Brainworxx\Krexx\Framework\Internals;
use Brainworxx\Krexx\Analysis\Hive;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\Framework\Toolbox;

/**
 * This class hosts the object analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Objects
{

    /**
     * Render a dump for an object.
     *
     * @param mixed $data
     *   The object we want to analyse.
     * @param string $name
     *   The name of the object.
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The generated markup.
     */
    public static function analyseObject($data, $name, $additional = '', $connector1 = '=>', $connector2 = '=')
    {
        static $level = 0;

        $output = '';
        $parameter = array($data, $name);
        $level++;

        if (Hive::isInHive($data)) {
            // Tell them, we've been here before
            // but also say who we are.
            $output .= SkinRender::renderRecursion(
                $name,
                $additional . 'class',
                get_class($data),
                Toolbox::generateDomIdFromObject($data),
                $connector1,
                $connector2
            );

            // We will not render this one, but since we
            // return to wherever we came from, we need to decrease the level.
            $level--;
            return $output;
        }
        // Remember, that we've been here before.
        Hive::addToHive($data);

        $anonFunction = function (&$parameter) {
            $data = $parameter[0];
            $name = $parameter[1];
            $output = SkinRender::renderSingeChildHr();

            $ref = new \ReflectionClass($data);

            // Dumping public properties.
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
                    $refProps[] = new Flection($value, $key);
                }
            }

            // We will dump the properties alphabetically sorted, via this callback.
            $sortingCallback = function ($a, $b) {
                return strcmp($a->name, $b->name);
            };

            if (count($refProps)) {
                usort($refProps, $sortingCallback);
                $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Public properties');
                // Adding a HR to reflect that the following stuff are not public
                // properties anymore.
                $output .= SkinRender::renderSingeChildHr();
            }

            // Dumping protected properties.
            if (Config::getConfigValue('properties', 'analyseProtected') == 'true' || Internals::isInScope()) {
                $refProps = $ref->getProperties(\ReflectionProperty::IS_PROTECTED);
                usort($refProps, $sortingCallback);

                if (count($refProps)) {
                    $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Protected properties');
                }
            }

            // Dumping private properties.
            if (Config::getConfigValue('properties', 'analysePrivate') == 'true' || Internals::isInScope()) {
                $refProps = $ref->getProperties(\ReflectionProperty::IS_PRIVATE);
                usort($refProps, $sortingCallback);
                if (count($refProps)) {
                    $output .= Properties::getReflectionPropertiesData($refProps, $ref, $data, 'Private properties');
                }
            }

            // Dumping class constants.
            if (Config::getConfigValue('properties', 'analyseConstants') == 'true') {
                $output .= Properties::getReflectionConstantsData($ref);
            }

            // Dumping all methods.
            $output .= Methods::getMethodData($data);

            // Dumping traversable data.
            if (Config::getConfigValue('properties', 'analyseTraversable') == 'true') {
                $output .= Objects::getTraversableData($data, $name);
            }

            // Dumping all configured debug functions.
            $output .= Objects::pollAllConfiguredDebugMethods($data);

            // Adding a HR for a better readability.
            $output .= SkinRender::renderSingeChildHr();
            return $output;
        };


        // Output data from the class.
        $output .= SkinRender::renderExpandableChild(
            $name,
            $additional . 'class',
            $anonFunction,
            $parameter,
            get_class($data),
            Toolbox::generateDomIdFromObject($data),
            '',
            false,
            $connector1,
            $connector2
        );
        // We've finished this one, and can decrease the level setting.
        $level--;
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
    public static function getTraversableData($data, $name)
    {
        if (is_a($data, 'Traversable')) {
            $parameter = iterator_to_array($data);
            $anonFunction = function (&$data) {
                // This should be an array. Giving it directly to the analysis hub would
                // create another useless nest.
                return Variables::iterateThrough($data);
            };
            // If we are facing a IteratorAggregate, we can not access the array
            // directly. To do this, we must get the Iterator from the class.
            // For our analysis is it not really important, because it does not
            // change anything. We need this for the automatic code generation.
            if (is_a($data, 'IteratorAggregate')) {
                $connector2 = '->getIterator()';
                // Remove the name, because this would then get added to the source
                // generation, resulting in unusable code.
                $name = '';
            } else {
                $connector2 = '';
            }
            return SkinRender::renderExpandableChild(
                $name,
                'Foreach',
                $anonFunction,
                $parameter,
                'Traversable Info',
                '',
                '',
                false,
                '',
                $connector2
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
    public static function pollAllConfiguredDebugMethods($data)
    {
        $output = '';

        $funcList = explode(',', Config::getConfigValue('methods', 'debugMethods'));
        foreach ($funcList as $funcName) {
            if (is_callable(array(
                    $data,
                    $funcName,
                )) && Config::isAllowedDebugCall($data, $funcName)
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

                if ($foundRequired == false) {
                    // Add a try to prevent the hosting CMS from doing something stupid.
                    try {
                        // We need to deactivate the current error handling to
                        // prevent the host system to do anything stupid.
                        set_error_handler(function () {
                            // Do nothing.
                        });
                        $result = $data->$funcName();
                        // Reactivate whatever error handling we had previously.
                        restore_error_handler();
                    } catch (\Exception $e) {
                        // Do nothing.
                    }
                    if (isset($result)) {
                        $anonFunction = function (&$result) {
                            return Variables::analysisHub($result);
                        };
                        $output .= SkinRender::renderExpandableChild(
                            $funcName,
                            'debug method',
                            $anonFunction,
                            $result,
                            '. . .',
                            '',
                            $funcName,
                            false,
                            '->',
                            '() ='
                        );
                        unset($result);
                    }
                }
            }
        }
        return $output;
    }

    /**
     * Analyses a closure.
     *
     * @param object $data
     *   The closure we want to analyse.
     * @param string $propName
     *   The property name
     * @param string $additional
     *   Information about the declaration in the parent class / array.
     * @param string $connector1
     *   The connector1 type to the parent class / array.
     * @param string $connector2
     *   The connector2 type to the parent class / array.
     *
     * @return string
     *   The generated markup.
     */
    public static function analyseClosure(
        $data,
        $propName = 'closure',
        $additional = '',
        $connector1 = '',
        $connector2 = ''
    ) {
        $ref = new \ReflectionFunction($data);

        $result = array();

        // Adding comments from the file.
        $result['comments'] = Variables::encodeString(Comments::prettifyComment($ref->getDocComment()), true);
        // Adding the place where it was declared.
        $result['declared in'] = htmlspecialchars($ref->getFileName()) . '<br/>';
        $result['declared in'] .= 'in line ' . htmlspecialchars($ref->getStartLine());
        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (strlen($namespace) > 0) {
            $result['namespace'] = $namespace;
        }
        // Adding the parameters.
        $parameters = $ref->getParameters();
        $paramList = '';
        foreach ($parameters as $parameter) {
            preg_match('/(.*)(?= \[ )/', $parameter, $key);
            $parameter = str_replace($key[0], '', $parameter);
            $result[$key[0]] = htmlspecialchars(trim($parameter, ' []'));
            $paramList .= trim(str_replace(array(
                    '&lt;optional&gt;',
                    '&lt;required&gt;'
                ), array('', ''), $result[$key[0]])) . ', ';
        }
        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';

        $anonFunction = function ($parameter) {
            $data = $parameter;
            $output = '';
            foreach ($data as $key => $string) {
                if ($key !== 'comments' && $key !== 'declared in') {
                    $output .= SkinRender::renderSingleChild($string, $key, $string, 'reflection', '', '', '=');
                } else {
                    $output .= SkinRender::renderSingleChild($string, $key, '. . .', 'reflection', '', '', '=');
                }
            }
            return $output;
        };

        return SkinRender::renderExpandableChild(
            $propName,
            $additional . ' closure',
            $anonFunction,
            $result,
            '',
            '',
            '',
            false,
            $connector1,
            $connector2 . '(' . $paramList . ') ='
        );

    }
}
