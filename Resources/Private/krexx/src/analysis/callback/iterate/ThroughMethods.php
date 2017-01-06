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

namespace Brainworxx\Krexx\Analyse\Callback\Iterate;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;

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
    public function callMe()
    {
        $result = '';
        /** @var \ReflectionClass $ref */
        $ref = $this->parameters['ref'];

        // Deep analysis of the methods.
        foreach ($this->parameters['data'] as $reflection) {
            $methodData = array();
            /* @var \ReflectionMethod $reflection */
            $method = $reflection->name;
            // Get the comment from the class, it's parents, interfaces or traits.
            $methodComment = $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Methods')
                ->getComment($reflection, $ref);
            if (!empty($methodComment)) {
                $methodData['comments'] = $methodComment;
            }

            // Get declaration place.
            $declaringClass = $reflection->getDeclaringClass();
            $filename = $declaringClass->getFileName();
            if (is_null($filename) || empty($filename)) {
                $methodData['declared in'] =
                    ":: unable to determine declaration ::\n\nMaybe this is a predeclared class?";
            } else {
                $methodData['declared in'] = $filename . "\n";
                $methodData['declared in'] .= 'in class: ' .$declaringClass->getName() . "\n";
                $methodData['declared in'] .= 'in line: ' . $reflection->getStartLine();
            }

            // Get parameters.
            $parameters = $reflection->getParameters();
            foreach ($parameters as $parameter) {
                preg_match('/(.*)(?= \[ )/', $parameter, $key);
                $parameter = str_replace($key[0], '', $parameter);
                $methodData[$key[0]] = trim($parameter, ' []');
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
            if ($reflection->getDeclaringClass()->getName() !== $ref->getName()) {
                $methodData['declaration keywords'] .= ' inherited';
                // We need to recheck our scope.
                // Private inherited methods are never within the scope.
                if (strpos($methodData['declaration keywords'], 'private') !== false &&
                    $this->pool->config->getSetting('analysePrivate') === false) {
                    // Do nothing. We ignore this one.
                    continue;
                }
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
            $result .= $this->dumpMethodInfo($methodData, $method);
        }
        return $result;

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
    protected function dumpMethodInfo(array $data, $name)
    {
        $paramList = '';
        $connector1 = '->';
        foreach ($data as $key => $string) {
            // Getting the parameter list.
            if (strpos($key, 'Parameter') === 0) {
                $paramList .= trim($string) . ', ';
            }
            if (strpos($data['declaration keywords'], 'static') !== false) {
                $connector1 = '::';
            }
        }

        $paramList = str_replace(
            array('&lt;required&gt; ', '&lt;optional&gt; '),
            '',
            $this->pool->encodeString($paramList)
        );
        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';
        $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
        ->setName($name)
            ->setType($data['declaration keywords'] . ' method')
            ->setConnector1($connector1)
            ->setConnector2('(' . $paramList . ')')
            ->addParameter('data', $data)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis')
            );

        return $this->pool->render->renderExpandableChild($model);
    }
}
