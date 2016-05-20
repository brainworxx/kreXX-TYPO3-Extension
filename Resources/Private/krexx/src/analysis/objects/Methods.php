<?php
/**
 * @file
 *   Object method analysis functions for kreXX
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

use Brainworxx\Krexx\Framework\Config;
use Brainworxx\Krexx\View\SkinRender;
use Brainworxx\Krexx\Analysis\Variables;
use Brainworxx\Krexx\Framework\Internals;

/**
 * This class hosts the object methods analysis functions.
 *
 * @package Brainworxx\Krexx\Analysis\Objects
 */
class Methods
{

    /**
     * Decides which methods we want to analyse and then starts the dump.
     *
     * @param object $data
     *   The object we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public static function getMethodData($data)
    {
        // Dumping all methods but only if we have any.
        $public = array();
        $protected = array();
        $private = array();
        $ref = new \ReflectionClass($data);
        if (Config::getConfigValue('methods', 'analyseMethodsAtall') == 'true') {
            $public = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);

            if (Config::getConfigValue('methods', 'analyseProtectedMethods') == 'true' || Internals::isInScope()) {
                $protected = $ref->getMethods(\ReflectionMethod::IS_PROTECTED);
            }
            if (Config::getConfigValue('methods', 'analysePrivateMethods') == 'true' || Internals::isInScope()) {
                $private = $ref->getMethods(\ReflectionMethod::IS_PRIVATE);
            }
        }

        // Is there anything to analyse?
        $methods = array_merge($public, $protected, $private);
        if (count($methods)) {
            // We need to sort these alphabetically.
            $sortingCallback = function ($a, $b) {
                return strcmp($a->name, $b->name);
            };
            usort($methods, $sortingCallback);

            $parameter = array($ref, $methods);
            $anonFunction = function (&$parameter) {
                return Methods::analyseMethods($parameter[0], $parameter[1]);
            };

            return SkinRender::renderExpandableChild('Methods', 'class internals', $anonFunction, $parameter);
        }
        return '';
    }

    /**
     * Render a dump for the methods of an object.
     *
     * @param mixed $ref
     *   A reflection of the original class.
     * @param array $data
     *   An array with the reflection methods.
     *
     * @return string
     *   The generated markup.
     */
    public static function analyseMethods(\ReflectionClass $ref, $data)
    {
        $parameter = array($ref, $data);

        $analysis = function ($parameter) {
            $ref = $parameter[0];
            $data = $parameter[1];
            $result = '';

            // Deep analysis of the methods.
            foreach ($data as $reflection) {
                $methodData = array();
                /* @var \ReflectionMethod $reflection */
                $method = $reflection->name;
                // Get the comment from the class, it's parents, interfaces or traits.
                $comments = trim($reflection->getDocComment());
                if ($comments != '') {
                    $methodData['comments'] = Comments::prettifyComment($comments);
                    $methodData['comments'] = Comments::getParentalComment($methodData['comments'], $ref, $method);
                    $methodData['comments'] = Comments::getInterfaceComment($methodData['comments'], $ref, $method);
                    $methodData['comments'] = Variables::encodeString($methodData['comments'], true);
                }
                // Get declaration place.
                $declaringClass = $reflection->getDeclaringClass();
                if (is_null($declaringClass->getFileName()) || $declaringClass->getFileName() == '') {
                    $methodData['declared in'] =
                        ':: unable to determine declaration ::<br/><br/>Maybe this is a predeclared class?';
                } else {
                    $methodData['declared in'] = htmlspecialchars($declaringClass->getFileName()) . '<br/>';
                    $methodData['declared in'] .= htmlspecialchars($declaringClass->getName()) . ' ';
                    $methodData['declared in'] .= 'in line ' . htmlspecialchars($reflection->getStartLine());
                }

                // Get parameters.
                $parameters = $reflection->getParameters();
                foreach ($parameters as $parameter) {
                    preg_match('/(.*)(?= \[ )/', $parameter, $key);
                    $parameter = str_replace($key[0], '', $parameter);
                    $methodData[$key[0]] = htmlspecialchars(trim($parameter, ' []'));
                }
                // Get visibility.
                $methodData['declaration keywords'] = '';
                if ($reflection->isPrivate()) {
                    $methodData['declaration keywords'] .= ' private';
                }
                if ($reflection->isProtected()) {
                    $methodData['declaration keywords'] .= ' protected';
                }
                if ($reflection->isPublic()) {
                    $methodData['declaration keywords'] .= ' public';
                }
                if ($reflection->isStatic()) {
                    $methodData['declaration keywords'] .= ' static';
                }
                if ($reflection->isFinal()) {
                    $methodData['declaration keywords'] .= ' final';
                }
                if ($reflection->isAbstract()) {
                    $methodData['declaration keywords'] .= ' abstract';
                }
                $methodData['declaration keywords'] = trim($methodData['declaration keywords']);
                $result .= Methods::dumpMethodInfo($methodData, $method);
            }
            return $result;

        };

        return $analysis($parameter);
    }

    /**
     * Render a dump for method info.
     *
     * @param array $data
     *   The method analysis results in an array.
     * @param string $name
     *   The name of the object.
     *
     * @return string
     *   The generated markup.
     */
    public static function dumpMethodInfo(array $data, $name)
    {
        $parameter = array($data);
        $anonFunction = function ($parameter) {
            $data = $parameter[0];
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

        $paramList = '';
        $connector1 = '->';
        foreach ($data as $key => $string) {
            // Getting the parameter list.
            if (strpos($key, 'Parameter') === 0) {
                $paramList .= trim(str_replace(array(
                        '&lt;optional&gt;',
                        '&lt;required&gt;',
                    ), array('', ''), $string)) . ', ';
            }
            if (strpos($data['declaration keywords'], 'static') !== false) {
                $connector1 = '::';
            }
        }
        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';
        return SkinRender::renderExpandableChild(
            $name,
            $data['declaration keywords'] . ' method',
            $anonFunction,
            $parameter,
            '',
            '',
            '',
            false,
            $connector1,
            '(' . $paramList . ')'
        );
    }
}
