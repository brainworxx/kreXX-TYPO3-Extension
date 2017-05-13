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

namespace Brainworxx\Krexx\Analyse\Routing\Process;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Processing of closures.
 *
 * @package Brainworxx\Krexx\Analyse\Routing\Process
 */
class ProcessClosure extends AbstractProcess
{
    /**
     * Analyses a closure.
     *
     * @param Model $model
     *   The closure we want to analyse.
     *
     * @return string
     *   The generated markup.
     */
    public function process(Model $model)
    {
        $ref = new \ReflectionFunction($model->getData());

        $result = array();

        // Adding comments from the file.
        $result['comments'] =  $this->pool
            ->createClass('Brainworxx\\Krexx\\Analyse\\Comment\\Functions')
            ->getComment($ref);

        // Adding the sourcecode
        $highlight = $ref->getStartLine() -1;
        $from = $highlight - 3;
        $to = $ref->getEndLine() -1;
        $file = $ref->getFileName();
        $result['source'] = $this->pool
            ->createClass('Brainworxx\\Krexx\\Service\\Misc\\File')
            ->readSourcecode($file, $highlight, $from, $to);

        // Adding the place where it was declared.
        $result['declared in'] = $ref->getFileName() . "\n";
        $result['declared in'] .= 'in line ' . $ref->getStartLine();

        // Adding the namespace, but only if we have one.
        $namespace = $ref->getNamespaceName();
        if (!empty($namespace)) {
            $result['namespace'] = $namespace;
        }

        // Adding the parameters.
        $paramList = '';

        foreach ($ref->getParameters() as $key => $reflectionParameter) {
            ++$key;
            $reflectionParameterWrapper = $this->pool
                ->createClass('Brainworxx\\Krexx\\Analyse\\Code\\ReflectionParameterWrapper')
                ->setReflectionParameter($reflectionParameter);

            $result['Parameter #' . $key] = $reflectionParameterWrapper;
            $paramList .= $reflectionParameterWrapper . ', ';
        }

        // Remove the ',' after the last char.
        $paramList = '<small>' . trim($paramList, ', ') . '</small>';
        $model->setType('closure')
            ->setNormal('. . .')
            ->setConnectorParameters($paramList)
            ->setDomid($this->generateDomIdFromObject($model->getData()))
            ->addParameter('data', $result)
            ->injectCallback(
                $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughMethodAnalysis')
            );

        return $this->pool->render->renderExpandableChild($model);

    }
}
